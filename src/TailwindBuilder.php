<?php

namespace Symfonycasts\TailwindBundle;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

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

    public function setOutput(SymfonyStyle $output)
    {
        $this->output = $output;
    }

    private function getInternalOutputCssPath()
    {
        return $this->tailwindVarDir . '/tailwind.css';
    }
}
