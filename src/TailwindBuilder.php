<?php

/*
 * This file is part of the SymfonyCasts TailwindBundle package.
 * Copyright (c) SymfonyCasts <https://symfonycasts.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfonycasts\TailwindBundle;

use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Manages the process of executing Tailwind on the input file.
 *
 * @author Ryan Weaver <ryan@symfonycasts.com>
 *
 * @final
 */
class TailwindBuilder
{
    private ?SymfonyStyle $output = null;
    private readonly array $inputPaths;

    public function __construct(
        private readonly string $projectRootDir,
        array $inputPaths,
        private readonly string $tailwindVarDir,
        private CacheInterface $cache,
        private readonly ?string $binaryPath = null,
        private readonly ?string $binaryVersion = null,
        private readonly string $configPath = 'tailwind.config.js'
    ) {
        $paths = [];
        foreach ($inputPaths as $inputPath) {
            $paths[] = $this->validateInputFile($inputPath);
        }

        $this->inputPaths = $paths;
    }

    public function runBuild(
        bool $watch,
        bool $poll,
        bool $minify,
        ?string $inputFile = null,
    ): Process {
        $binary = $this->createBinary();

        $inputPath = $this->validateInputFile($inputFile ?? $this->inputPaths[0]);
        if (!\in_array($inputPath, $this->inputPaths)) {
            throw new \InvalidArgumentException(sprintf('The input CSS file "%s" is not one of the configured input files.', $inputPath));
        }

        $arguments = ['-c', $this->configPath, '-i', $inputPath, '-o', $this->getInternalOutputCssPath($inputPath)];
        if ($watch) {
            $arguments[] = '--watch';
            if ($poll) {
                $arguments[] = '--poll';
            }
        }
        if ($minify) {
            $arguments[] = '--minify';
        }
        $process = $binary->createProcess($arguments);
        if ($watch) {
            $process->setTimeout(null);
            // setting an input stream causes the command to "wait" for the watch
            $inputStream = new InputStream();
            $process->setInput($inputStream);
        }

        $this->output?->note('Executing Tailwind (pass -v to see more details).');
        if ($this->output?->isVerbose()) {
            $this->output->writeln([
                '  Command:',
                '    '.$process->getCommandLine(),
            ]);
        }
        $process->start();

        return $process;
    }

    public function runInit(): Process
    {
        $binary = $this->createBinary();
        $process = $binary->createProcess(['init']);
        if ($this->output->isVerbose()) {
            $this->output->writeln([
                '  Command:',
                '    '.$process->getCommandLine(),
            ]);
        }
        $process->start();

        return $process;
    }

    public function setOutput(SymfonyStyle $output): void
    {
        $this->output = $output;
    }

    public function getInternalOutputCssPath(string $inputPath): string
    {
        $inputFileName = pathinfo($inputPath, \PATHINFO_FILENAME);

        return "{$this->tailwindVarDir}/{$inputFileName}.built.css";
    }

    public function getInputCssPaths(): array
    {
        return $this->inputPaths;
    }

    public function getConfigFilePath(): string
    {
        return $this->configPath;
    }

    public function getOutputCssContent(string $inputFile): string
    {
        if (!is_file($this->getInternalOutputCssPath($inputFile))) {
            throw new \RuntimeException('Built Tailwind CSS file does not exist: run "php bin/console tailwind:build" to generate it');
        }

        return file_get_contents($this->getInternalOutputCssPath($inputFile));
    }

    private function validateInputFile(string $inputPath): string
    {
        if (is_file($inputPath)) {
            return realpath($inputPath);
        }

        if (is_file($this->projectRootDir.'/'.$inputPath)) {
            return realpath($this->projectRootDir.'/'.$inputPath);
        }

        throw new \InvalidArgumentException(sprintf('The input CSS file "%s" does not exist.', $inputPath));
    }

    private function createBinary(): TailwindBinary
    {
        return new TailwindBinary($this->tailwindVarDir, $this->projectRootDir, $this->binaryPath, $this->binaryVersion, $this->cache, $this->output);
    }
}
