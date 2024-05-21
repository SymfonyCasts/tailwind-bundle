<?php

/*
 * This file is part of the SymfonyCasts TailwindBundle package.
 * Copyright (c) SymfonyCasts <https://symfonycasts.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfonycasts\TailwindBundle;

use Psr\Cache\CacheItemInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Process\Process;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Wraps and downloads the tailwindcss binary.
 *
 * @author Ryan Weaver <ryan@symfonycasts.com>
 */
class TailwindBinary
{
    private HttpClientInterface $httpClient;
    private ?string $cachedVersion = null;

    public function __construct(
        private string $binaryDownloadDir,
        private string $cwd,
        private ?string $binaryPath,
        private ?string $binaryVersion,
        private CacheInterface $cache,
        private ?SymfonyStyle $output = null,
        ?HttpClientInterface $httpClient = null
    ) {
        $this->httpClient = $httpClient ?? HttpClient::create();
    }

    public function createProcess(array $arguments = []): Process
    {
        if (null === $this->binaryPath) {
            $binary = $this->binaryDownloadDir.'/'.$this->getVersion().'/'.self::getBinaryName();
            if (!is_file($binary)) {
                $this->downloadExecutable();
            }
        } else {
            $binary = $this->binaryPath;
        }

        // add $binary to the front of the $arguments array
        array_unshift($arguments, $binary);

        return new Process($arguments, $this->cwd);
    }

    private function downloadExecutable(): void
    {
        $url = sprintf('https://github.com/tailwindlabs/tailwindcss/releases/download/%s/%s', $this->getVersion(), self::getBinaryName());

        $this->output?->note(sprintf('Downloading TailwindCSS binary from %s', $url));

        if (!is_dir($this->binaryDownloadDir.'/'.$this->getVersion())) {
            mkdir($this->binaryDownloadDir.'/'.$this->getVersion(), 0777, true);
        }

        $targetPath = $this->binaryDownloadDir.'/'.$this->getVersion().'/'.self::getBinaryName();
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
    }

    private function getVersion(): string
    {
        return $this->cachedVersion ??= $this->binaryVersion ?? $this->getLatestVersion();
    }

    private function getLatestVersion(): string
    {
        return $this->cache->get('latestVersion', function (CacheItemInterface $item) {
            $item->expiresAfter(3600);
            try {
                $response = $this->httpClient->request('GET', 'https://api.github.com/repos/tailwindlabs/tailwindcss/releases/latest');

                return $response->toArray()['name'] ?? throw new \Exception('Cannot get the latest version name from response JSON.');
            } catch (\Throwable $e) {
                throw new \Exception('Cannot determine latest Tailwind CLI binary version. Please specify a version in the configuration.', previous: $e);
            }
        });
    }

    /**
     * @internal
     */
    public static function getBinaryName(): string
    {
        $os = strtolower(\PHP_OS);
        $machine = strtolower(php_uname('m'));

        if (str_contains($os, 'darwin')) {
            if ('arm64' === $machine) {
                return 'tailwindcss-macos-arm64';
            }
            if ('x86_64' === $machine) {
                return 'tailwindcss-macos-x64';
            }

            throw new \Exception(sprintf('No matching machine found for Darwin platform (Machine: %s).', $machine));
        }

        if (str_contains($os, 'linux')) {
            if ('arm64' === $machine || 'aarch64' === $machine) {
                return 'tailwindcss-linux-arm64';
            }
            if ('armv7' === $machine) {
                return 'tailwindcss-linux-armv7';
            }
            if ('x86_64' === $machine) {
                return 'tailwindcss-linux-x64';
            }

            throw new \Exception(sprintf('No matching machine found for Linux platform (Machine: %s).', $machine));
        }

        if (str_contains($os, 'win')) {
            if ('arm64' === $machine) {
                return 'tailwindcss-windows-arm64.exe';
            }
            if ('x86_64' === $machine || 'amd64' === $machine) {
                return 'tailwindcss-windows-x64.exe';
            }

            throw new \Exception(sprintf('No matching machine found for Windows platform (Machine: %s).', $machine));
        }

        throw new \Exception(sprintf('Unknown platform or architecture (OS: %s, Machine: %s).', $os, $machine));
    }
}
