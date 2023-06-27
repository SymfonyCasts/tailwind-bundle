<?php

namespace Symfonycasts\TailwindBundle;

use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

/**
 * Manages the process of executing Tailwind on the input file.
 */
class TailwindBuilder
{
    private SymfonyStyle $output;

    public function __construct(
        private readonly string $inputPath,
        private readonly string $tailwindVarDir,
        private readonly ?string $binaryPath,
    )
    {
    }

    public function runBuild(bool $watch): Process
    {
        $binary = $this->createBinary();
        $arguments = ['-i', $this->inputPath, '-o', $this->getInternalOutputCssPath()];
        if ($watch) {
            $arguments[] = '--watch';
        }
        $process = $binary->createProcess($arguments);
        if ($watch) {
            $process->setTimeout(null);
            $process->setPty(true);
        }
        $this->output?->note('Executing Tailwind (pass -v to see more details).');
        if ($this->output->isVerbose()) {
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
                '    ' . $process->getCommandLine(),
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
        return $this->tailwindVarDir . '/tailwind.built.css';
    }

    public function getInputCssPath(): string
    {
        return $this->inputPath;
    }

    public function getOutputCssContent(): string
    {
        if (!is_file($this->getInternalOutputCssPath())) {
            throw new \RuntimeException('Built Tailwind CSS file does not exist: run "php bin/console tailwind:build" to generate it');
        }

        return file_get_contents($this->getInternalOutputCssPath());
    }

    /**
     * @return TailwindBinary
     */
    private function createBinary(): TailwindBinary
    {
        return new TailwindBinary($this->tailwindVarDir, $this->binaryPath, $this->output);
    }
}
