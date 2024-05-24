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
    private readonly string $inputPath;

    public function __construct(
        private readonly string $projectRootDir,
        string $inputPath,
        private readonly string $tailwindVarDir,
        private CacheInterface $cache,
        private readonly ?string $binaryPath = null,
        private readonly ?string $binaryVersion = null,
        private readonly string $configPath = 'tailwind.config.js'
    ) {
        if (is_file($inputPath)) {
            $this->inputPath = $inputPath;
        } else {
            $this->inputPath = $projectRootDir.'/'.$inputPath;

            if (!is_file($this->inputPath)) {
                throw new \InvalidArgumentException(sprintf('The input CSS file "%s" does not exist.', $inputPath));
            }
        }
    }

    public function runBuild(
        bool $watch,
        bool $poll,
        bool $minify,
    ): Process {
        $binary = $this->createBinary();
        $arguments = ['-c', $this->configPath, '-i', $this->inputPath, '-o', $this->getInternalOutputCssPath()];
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

    public function runInit()
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

    public function getInternalOutputCssPath(): string
    {
        return $this->tailwindVarDir.'/tailwind.built.css';
    }

    public function getInputCssPath(): string
    {
        return $this->inputPath;
    }

    public function getConfigFilePath(): string
    {
        return $this->configPath;
    }

    public function getOutputCssContent(): string
    {
        if (!is_file($this->getInternalOutputCssPath())) {
            throw new \RuntimeException('Built Tailwind CSS file does not exist: run "php bin/console tailwind:build" to generate it');
        }

        return file_get_contents($this->getInternalOutputCssPath());
    }

    private function createBinary(): TailwindBinary
    {
        return new TailwindBinary($this->tailwindVarDir, $this->projectRootDir, $this->binaryPath, $this->binaryVersion, $this->cache, $this->output);
    }
}
