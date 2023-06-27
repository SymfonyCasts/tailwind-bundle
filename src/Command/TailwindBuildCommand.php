<?php

namespace Symfonycasts\TailwindBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;
use Symfonycasts\TailwindBundle\TailwindBinary;
use Symfonycasts\TailwindBundle\TailwindBuilder;

#[AsCommand(
    name: 'tailwind:build',
    description: 'Builds the Tailwind CSS assets.',
)]
class TailwindBuildCommand extends Command
{
    public function __construct(
        private TailwindBuilder $tailwindBuilder,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('watch', 'w', null, 'Watch for changes and rebuild automatically');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->tailwindBuilder->setOutput($io);

        $process = $this->tailwindBuilder->runTailwind(
            $input->getOption('watch'),
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
