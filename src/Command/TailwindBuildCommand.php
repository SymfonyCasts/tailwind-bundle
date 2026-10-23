<?php

/*
 * This file is part of the SymfonyCasts TailwindBundle package.
 * Copyright (c) SymfonyCasts <https://symfonycasts.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfonycasts\TailwindBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;
use Symfonycasts\TailwindBundle\TailwindBuilder;

/**
 * @internal
 */
#[AsCommand(
    name: 'tailwind:build',
    description: 'Builds the Tailwind CSS assets.',
)]
final class TailwindBuildCommand extends Command
{
    public function __construct(
        private TailwindBuilder $tailwindBuilder,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('input_css', InputArgument::OPTIONAL, 'The input CSS file to compile')
            ->addOption('watch', 'w', null, 'Watch for changes and rebuild automatically')
            ->addOption('poll', null, null, 'Use polling instead of filesystem events when watching')
            ->addOption('minify', 'm', InputOption::VALUE_NONE, 'Minify the output CSS')
            ->addOption('postcss', null, InputOption::VALUE_REQUIRED, 'Load custom PostCSS configuration')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->tailwindBuilder->setOutput($io);

        $inputFile = $input->getArgument('input_css');
        // Tailwind takes a single input per run, so each configured file gets its own process.
        // They are started together, which is also what makes --watch work for all of them.
        $inputFiles = null !== $inputFile ? [$inputFile] : $this->tailwindBuilder->getInputCssPaths();

        $processes = [];
        $lastWritten = null;

        if ($input->getOption('watch')) {
            // the watchers never exit, so they all run at the same time
            foreach ($inputFiles as $i => $file) {
                $processes[$i] = $this->startBuild($input, $file);
            }

            $this->readOutput($processes, $inputFiles, $io, $lastWritten);
        } else {
            // a plain build has no reason to run the processes concurrently
            foreach ($inputFiles as $i => $file) {
                $processes[$i] = $this->startBuild($input, $file);

                $this->readOutput([$i => $processes[$i]], $inputFiles, $io, $lastWritten);
            }
        }

        $failed = false;

        foreach ($processes as $process) {
            $failed = $failed || !$process->isSuccessful();
        }

        if ($failed) {
            $io->error('Tailwind CSS build failed: see output above.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function startBuild(InputInterface $input, string $inputFile): Process
    {
        return $this->tailwindBuilder->runBuild(
            watch: $input->getOption('watch'),
            poll: $input->getOption('poll'),
            minify: $input->getOption('minify'),
            inputFile: $inputFile,
            postCssConfigFile: $input->getOption('postcss'),
        );
    }

    /**
     * Polls the processes until they all exit: with --watch they never do, and a process
     * whose output is not read ends up blocked on a full pipe.
     *
     * @param array<int, Process> $processes
     * @param list<string>        $inputFiles
     * @param int|null            $lastWritten the index of the input whose output was written last
     */
    private function readOutput(array $processes, array $inputFiles, SymfonyStyle $io, ?int &$lastWritten): void
    {
        do {
            $running = false;

            foreach ($processes as $i => $process) {
                $process->checkTimeout();
                $running = $running || $process->isRunning();
                $buffer = $process->getIncrementalOutput().$process->getIncrementalErrorOutput();

                if ('' === $buffer) {
                    continue;
                }

                if (\count($inputFiles) > 1 && $lastWritten !== $i) {
                    $io->writeln(\sprintf('<comment>[%s]</comment>', $this->tailwindBuilder->getRelativeInputPath($inputFiles[$i])));
                    $lastWritten = $i;
                }

                $io->write($buffer);
            }

            if ($running) {
                usleep(100000);
            }
        } while ($running);
    }
}
