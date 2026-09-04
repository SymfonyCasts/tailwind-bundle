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

        foreach ($inputFiles as $file) {
            $processes[] = $this->tailwindBuilder->runBuild(
                watch: $input->getOption('watch'),
                poll: $input->getOption('poll'),
                minify: $input->getOption('minify'),
                inputFile: $file,
                postCssConfigFile: $input->getOption('postcss'),
            );
        }

        $failed = false;

        foreach ($processes as $process) {
            $process->wait(static function ($type, $buffer) use ($io) {
                $io->write($buffer);
            });

            $failed = $failed || !$process->isSuccessful();
        }

        if ($failed) {
            $io->error('Tailwind CSS build failed: see output above.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
