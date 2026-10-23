<?php

/*
 * This file is part of the SymfonyCasts TailwindBundle package.
 * Copyright (c) SymfonyCasts <https://symfonycasts.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfonycasts\TailwindBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfonycasts\TailwindBundle\TailwindBuilder;
use Symfonycasts\TailwindBundle\TailwindVersionFinder;

/**
 * @internal
 */
#[AsCommand(
    name: 'tailwind:init',
    description: 'Initializes Tailwind CSS for your project',
)]
final class TailwindInitCommand extends TailwindConfigCommand
{
    public function __construct(
        private TailwindVersionFinder $versionFinder,
        private array $inputCss,
        string $rootDir,
    ) {
        parent::__construct($rootDir);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$input->isInteractive()) {
            throw new \RuntimeException('tailwind:init command must be run interactively.');
        }

        $bundleConfig = $this->bundleConfig();

        if ($io->confirm('Are you managing your own Tailwind CSS binary (if unsure, choose no and one will be downloaded)?', false)) {
            $binaryPath = $io->ask('Enter the path to your Tailwind CSS binary:', 'node_modules/.bin/tailwindcss');
            $bundleConfig['symfonycasts_tailwind']['binary'] = $binaryPath;
        } else {
            $majorVersion = $io->ask('Which major version do you wish to use?', '4');
            $latestVersion = $this->versionFinder->latestVersionFor($majorVersion);
            $bundleConfig['symfonycasts_tailwind']['binary_version'] = $latestVersion;
        }

        $this->writeBundleConfig($bundleConfig);

        $builder = new TailwindBuilder(
            $this->rootDir,
            $this->inputCss,
            $this->rootDir.'/var/tailwind',
            binaryPath: $bundleConfig['symfonycasts_tailwind']['binary'] ?? null,
            binaryVersion: $bundleConfig['symfonycasts_tailwind']['binary_version'] ?? null,
        );

        if (!$this->createTailwindConfig($io, $builder)) {
            return self::FAILURE;
        }

        $this->addTailwindDirectives($io, $builder);

        $io->success('Tailwind CSS is ready to use!');
        $io->warning('Run "php bin/console cache:clear" so the updated configuration is picked up.');

        return self::SUCCESS;
    }

    private function createTailwindConfig(SymfonyStyle $io, TailwindBuilder $builder): bool
    {
        if ($builder->createBinary()->isV4()) {
            $io->note('Tailwind v4 detected: skipping config file creation.');

            return true;
        }

        $configFile = $builder->getConfigFilePath();

        if (file_exists($configFile)) {
            $io->note(\sprintf('Tailwind config file already exists in "%s"', $configFile));

            return true;
        }

        $builder->setOutput($io);
        $process = $builder->runInit();
        $process->wait(static function ($type, $buffer) use ($io) {
            $io->write($buffer);
        });

        if (!$process->isSuccessful()) {
            $io->error('Tailwind CSS init failed: see output above.');

            return false;
        }

        $io->note('Updating tailwind.config.js for Symfony paths...');

        $tailwindConfig = <<<EOF
        /** @type {import('tailwindcss').Config} */
        module.exports = {
          content: [
            "./assets/**/*.js",
            "./templates/**/*.html.twig",
          ],
          theme: {
            extend: {},
          },
          plugins: [],
        }

        EOF;

        file_put_contents($configFile, $tailwindConfig);

        return true;
    }

    private function addTailwindDirectives(SymfonyStyle $io, TailwindBuilder $builder): void
    {
        $inputFile = $builder->getInputCssPaths()[0];
        $contents = is_file($inputFile) ? file_get_contents($inputFile) : '';
        if (str_contains($contents, '@tailwind base') || str_contains($contents, '@import "tailwindcss"')) {
            $io->note(\sprintf('Tailwind directives already exist in "%s"', $inputFile));

            return;
        }

        $io->note(\sprintf('Adding Tailwind directives to "%s"', $inputFile));
        $tailwindDirectives = <<<EOF
        @tailwind base;
        @tailwind components;
        @tailwind utilities;
        EOF;

        if ($builder->createBinary()->isV4()) {
            $tailwindDirectives = <<<EOF
            @import "tailwindcss";
            EOF;
        }

        file_put_contents($inputFile, $tailwindDirectives."\n\n".$contents);
    }
}
