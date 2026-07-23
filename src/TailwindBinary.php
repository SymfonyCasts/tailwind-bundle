<?php

/*
 * This file is part of the SymfonyCasts TailwindBundle package.
 * Copyright (c) SymfonyCasts <https://symfonycasts.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfonycasts\TailwindBundle;

use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Process\Process;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Wraps and downloads the tailwindcss binary.
 *
 * @author Ryan Weaver <ryan@symfonycasts.com>
 *
 * @internal
 */
final class TailwindBinary
{
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

        if (!$this->binaryVersion) {
            throw new \LogicException('You must specify a "binary" or "binary_version" in your symfonycasts_tailwind config (or run "tailwind:init").');
        }

        $this->binaryPath = $this->binaryDownloadDir.'/'.$this->getVersion().'/'.self::getBinaryName($this->getRawVersion(), $this->binaryPlatform);

        if (!is_file($this->binaryPath) || 0 === filesize($this->binaryPath)) {
            $this->downloadExecutable();
        }

        return $this->binaryPath;
    }

    private function downloadExecutable(): void
    {
        $binaryName = self::getBinaryName($this->getRawVersion(), $this->binaryPlatform);
        $url = \sprintf('https://github.com/tailwindlabs/tailwindcss/releases/download/%s/%s', $this->getVersion(), $binaryName);

        // fetch the expected SHA256 hash upfront so a corrupt download can be
        // detected and removed (see https://github.com/SymfonyCasts/tailwind-bundle/issues/115)
        $expectedHash = $this->fetchExpectedHash($binaryName);

        $this->output?->note(\sprintf('Downloading TailwindCSS binary from %s', $url));

        if (!is_dir($this->binaryDownloadDir.'/'.$this->getVersion())) {
            mkdir($this->binaryDownloadDir.'/'.$this->getVersion(), 0777, true);
        }

        $targetPath = $this->binaryDownloadDir.'/'.$this->getVersion().'/'.$binaryName;
        $progressBar = null;

        $response = $this->httpClient->request('GET', $url, [
            'on_progress' => function (int $dlNow, int $dlSize, array $info) use (&$progressBar): void {
                // dlSize is not known at the start
                if (0 === $dlSize) {
                    return;
                }

                if (!$progressBar) {
                    $progressBar = $this->output?->createProgressBar($dlSize);
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
        // make file executable
        chmod($targetPath, 0777);

        // getBinaryPath() may have cached this path's size (0) before the re-download
        clearstatcache(true, $targetPath);
        if (0 === filesize($targetPath)) {
            unlink($targetPath);

            throw new \RuntimeException(\sprintf('The downloaded TailwindCSS binary at "%s" is empty (0 bytes). On Windows this is usually antivirus software blocking or quarantining the download; add an exclusion for the binary and try again. The empty file has been removed.', $targetPath));
        }

        if (null !== $expectedHash && !hash_equals($expectedHash, $actualHash = hash_file('sha256', $targetPath))) {
            unlink($targetPath);

            throw new \RuntimeException(\sprintf('Downloaded binary failed integrity check (expected hash: %s, actual hash: %s). The corrupt file has been removed. Please try again.', $expectedHash, $actualHash));
        }
    }

    /**
     * Look up the expected SHA256 hash for the given binary in the release's
     * "sha256sums.txt" asset. Served from the same (non rate-limited) download
     * host as the binary itself. Returns null when the checksums file or the
     * binary's entry is not available (e.g. older releases), in which case the
     * integrity check is skipped.
     */
    private function fetchExpectedHash(string $binaryName): ?string
    {
        $url = \sprintf('https://github.com/tailwindlabs/tailwindcss/releases/download/%s/sha256sums.txt', $this->getVersion());

        try {
            $response = $this->httpClient->request('GET', $url);

            if (200 !== $response->getStatusCode()) {
                return null;
            }

            $content = $response->getContent();
        } catch (\Throwable) {
            return null;
        }

        foreach (explode("\n", $content) as $line) {
            // each line is "<sha256>  <filename>" (filename may be prefixed with "*" or "./")
            $parts = preg_split('/\s+/', trim($line), 2);
            if (2 !== \count($parts)) {
                continue;
            }

            [$hash, $file] = $parts;
            if (ltrim($file, '*./') === $binaryName) {
                return strtolower($hash);
            }
        }

        return null;
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
            throw new \Exception(\sprintf('Unknown platform or architecture (OS: %s, Machine: %s).', $os, $machine));
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
