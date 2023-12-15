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
 */
class TailwindBinary
{
    private const DEFAULT_VERSION = 'v3.3.5';
    private HttpClientInterface $httpClient;

    public function __construct(
        private string $binaryDownloadDir,
        private string $cwd,
        private ?string $binaryPath,
        private ?SymfonyStyle $output = null,
        HttpClientInterface $httpClient = null,
    ) {
        $this->httpClient = $httpClient ?? HttpClient::create();
    }

    public function createProcess(array $arguments = []): Process
    {
        if (null === $this->binaryPath) {
            $binary = $this->binaryDownloadDir.'/'.self::getBinaryName();
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
        $url = sprintf('https://github.com/tailwindlabs/tailwindcss/releases/download/%s/%s', $this->getLatestVersion(), self::getBinaryName());

        $this->output?->note(sprintf('Downloading TailwindCSS binary from %s', $url));

        if (!is_dir($this->binaryDownloadDir)) {
            mkdir($this->binaryDownloadDir, 0777, true);
        }

        $targetPath = $this->binaryDownloadDir.'/'.self::getBinaryName();
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

    private function getLatestVersion(): string
    {
        try {
            $response = $this->httpClient->request('GET', 'https://api.github.com/repos/tailwindlabs/tailwindcss/releases/latest');

            return $response->toArray()['name'] ?? self::DEFAULT_VERSION;
        } catch (\Throwable) {
            return self::DEFAULT_VERSION;
        }
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
