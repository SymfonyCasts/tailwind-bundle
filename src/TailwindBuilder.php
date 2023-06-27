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
    )
    {
    }

    public function runTailwind(bool $watch): Process
    {
        $arguments = [];
        if ($watch) {
            $arguments[] = '--watch';
        }
        $binary = new TailwindBinary($this->tailwindVarDir, $this->output);
        $process = $binary->createProcess(
            $this->inputPath,
            $this->getInternalOutputCssPath(),
            $arguments,
        );
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
}
