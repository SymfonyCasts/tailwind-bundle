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

#[AsCommand(
    name: 'tailwind:build',
    description: 'Builds the Tailwind CSS assets.',
)]
class TailwindBuildCommand extends Command
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
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->tailwindBuilder->setOutput($io);

        $process = $this->tailwindBuilder->runBuild(
            watch: $input->getOption('watch'),
            poll: $input->getOption('poll'),
            minify: $input->getOption('minify'),
            inputFile: $input->getArgument('input_css'),
        );
        $process->wait(function ($type, $buffer) use ($io) {
            $io->write($buffer);
        });

        if (!$process->isSuccessful()) {
            $io->error('Tailwind CSS build failed: see output above.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
