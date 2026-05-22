<?php

/*
 * This file is part of the SymfonyCasts TailwindBundle package.
 * Copyright (c) SymfonyCasts <https://symfonycasts.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfonycasts\TailwindBundle;

use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Process\Process;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfonycasts\TailwindBundle\Model\TailwindBinaries;
use Symfonycasts\TailwindBundle\Model\TailwindBinary as TailwindBinaryAsset;

/**
 * Wraps and downloads the tailwindcss binary.
 *
 * @author Ryan Weaver <ryan@symfonycasts.com>
 */
class TailwindBinary
{
    private const DEFAULT_VERSION = 'v3.4.17';

    private HttpClientInterface $httpClient;

    public function __construct(
        private string $binaryDownloadDir,
        private string $cwd,
        private ?string $binaryPath,
        private ?string $binaryVersion,
        private ?SymfonyStyle $output = null,
        ?HttpClientInterface $httpClient = null,
        private string $binaryPlatform = 'auto',
    ) {
        $this->httpClient = $httpClient ?? HttpClient::create();

        if (!$this->binaryVersion && !$this->binaryPath) {
            trigger_deprecation(
                'symfonycasts/tailwind-bundle',
                '0.8',
                'Not specifying a "binary" or "binary_version" is deprecated. %s is being used.',
                self::DEFAULT_VERSION,
            );

            $this->binaryVersion = self::DEFAULT_VERSION;
        }

        if ($this->binaryVersion && $this->binaryPath) {
            $this->binaryVersion = null;
        }
    }

    public function createProcess(array $arguments = []): Process
    {
        // add binary to the front of the $arguments array
        array_unshift($arguments, $this->getBinaryPath());

        return new Process($arguments, $this->cwd);
    }

    public function getVersion(): string
    {
        if ($this->binaryVersion) {
            return $this->binaryVersion;
        }

        $process = $this->createProcess(['--help']);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException('Could not determine the tailwindcss version.');
        }

        if (!preg_match('#(v\d+\.\d+\.\d+)#', $process->getOutput(), $matches)) {
            throw new \RuntimeException('Could not determine the tailwindcss version.');
        }

        return $this->binaryVersion = $matches[1];
    }

    public function isV4(): bool
    {
        return version_compare($this->getRawVersion(), '4.0.0', '>=');
    }

    public function getRawVersion(): string
    {
        return ltrim($this->getVersion(), 'v');
    }

    private function getBinaryPath(): string
    {
        if ($this->binaryPath) {
            return $this->binaryPath;
        }

        $this->binaryPath = Path::canonicalize(
            $this->binaryDownloadDir.'/'.$this->getVersion().'/'.self::getBinaryName(
                $this->getRawVersion(),
                $this->binaryPlatform
            )
        );

        if (!is_file($this->binaryPath) || 0 === filesize($this->binaryPath)) {
            if (is_file($this->binaryPath)) {
                unlink($this->binaryPath);
            }
            $this->downloadExecutable();
        }

        return $this->binaryPath;
    }

    private function requestBinariesByVersion(string $version): TailwindBinaries
    {
        $url = \sprintf('https://api.github.com/repos/tailwindlabs/tailwindcss/releases/tags/v%s', $version);

        $response = $this->httpClient->request('GET', $url);

        $content = json_decode($response->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        $assets = [];
        foreach ($content['assets'] as $asset) {
            if ('text/plain' === $asset['content_type']) {
                continue;
            }

            if (version_compare($version, '4.1.9', '<=')) {
                $asset['digest'] = '';
            }

            $assets[] = new TailwindBinaryAsset(
                name: $asset['name'],
                contentType: $asset['content_type'],
                size: $asset['size'],
                digest: $asset['digest'],
                createdAt: $asset['created_at'],
                downloadUrl: $asset['browser_download_url'],
            );
        }

        return new TailwindBinaries(
            name: $content['name'],
            publishedAt: $content['published_at'],
            assets: $assets,
        );
    }

    private function downloadExecutable(): void
    {
        $releases = $this->requestBinariesByVersion($this->getRawVersion());
        $binaryName = self::getBinaryName($this->getRawVersion(), $this->binaryPlatform);

        $releaseToDownload = $releases->getAssetByBinaryName($binaryName);
        if (null === $releaseToDownload) {
            $availableAssets = implode(', ', array_map(
                static fn (TailwindBinaryAsset $a) => $a->getName(),
                $releases->getAssets()
            ));
            throw new \RuntimeException(\sprintf('Could not find binary "%s" in the release. Available assets: %s', $binaryName, $availableAssets));
        }

        $url = $releaseToDownload->getDownloadUrl();

        $this->output?->note(\sprintf('Downloading TailwindCSS binary from %s', $url));

        if (!is_dir($this->binaryDownloadDir.'/'.$this->getVersion())) {
            mkdir($this->binaryDownloadDir.'/'.$this->getVersion(), 0777, true);
        }

        $targetPath = $this->binaryDownloadDir.'/'.$this->getVersion().'/'.$binaryName;
        $progressBar = null;

        $response = $this->httpClient->request('GET', $url, [
            'on_progress' => function (int $dlNow, int $dlSize, array $info) use (
                $releaseToDownload,
                &$progressBar
            ): void {
                if (0 === $dlSize) {
                    return;
                }

                if (!$progressBar) {
                    $progressBar = $this->output?->createProgressBar($releaseToDownload->getSize());
                }

                $progressBar?->setProgress($dlNow);
            },
        ]);

        $fileHandler = fopen($targetPath, 'w');
        foreach ($this->httpClient->stream($response) as $chunk) {
            fwrite($fileHandler, $chunk->getContent());
        }
        fclose($fileHandler);

        $progressBar?->finish();
        $this->output?->writeln('');
        chmod($targetPath, 0777);

        $digest = $releaseToDownload->getDigest();
        if ('' !== $digest) {
            $downloadedFileHash = hash_file('sha256', $targetPath);
            if (!hash_equals($digest, $downloadedFileHash)) {
                unlink($targetPath);
                throw new \RuntimeException(\sprintf('Downloaded binary failed integrity check (expected hash: %s, actual hash: %s). The corrupt file has been removed. Please try again.', $digest, $downloadedFileHash));
            }
        }
    }

    /**
     * @internal
     */
    public static function getBinaryName(string $version, string $platform = 'auto'): string
    {
        $system = self::getBinarySystem($version, $platform);
        $isWindows = str_contains($system, 'windows');

        return "tailwindcss-{$system}".(($isWindows) ? '.exe' : '');
    }

    private static function getBinarySystem(string $version, string $platform): string
    {
        if ('auto' !== $platform) {
            return $platform;
        }
        $os = strtolower(\PHP_OS);
        $machine = strtolower(php_uname('m'));

        $systems = [
            'linux' => 'linux',
            'darwin' => 'macos',
            'win' => 'windows',
        ];

        $architectures = [
            'arm64' => 'arm64',
            'aarch64' => 'arm64',
            'armv7' => 'armv7',
            'x86_64' => 'x64',
            'amd64' => 'x64',
        ];

        // Detect the current system
        $system = null;
        foreach ($systems as $key => $name) {
            if (str_contains($os, $key)) {
                $system = $name;
                break;
            }
        }

        // Detect the current architecture
        $arch = $architectures[$machine] ?? null;

        if (!$system || !$arch) {
            throw new \UnexpectedValueException(\sprintf('Unknown platform or architecture (OS: %s, Machine: %s).', $os, $machine));
        }

        // Detect MUSL only when version >= 4.0.0
        if ('linux' === $system && version_compare($version, '4.0.0', '>=')) {
            return "{$system}-{$arch}".(self::isMusl() ? '-musl' : '');
        }

        return "{$system}-{$arch}";
    }

    private static function isMusl(): bool
    {
        static $isMusl = null;

        if (null !== $isMusl) {
            return $isMusl;
        }

        if (!\function_exists('phpinfo')) {
            return $isMusl = false;
        }

        ob_start();
        phpinfo(\INFO_GENERAL);

        return $isMusl = 1 === preg_match('/--build=.*?-linux-musl/', ob_get_clean() ?: '');
    }
}
