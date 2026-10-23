<?php

/*
 * This file is part of the SymfonyCasts TailwindBundle package.
 * Copyright (c) SymfonyCasts <https://symfonycasts.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfonycasts\TailwindBundle\Tests\Command;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfonycasts\TailwindBundle\Tests\fixtures\TailwindTestKernel;

abstract class BaseCommandTestCase extends KernelTestCase
{
    protected const FIXTURES_DIR = __DIR__.'/../fixtures';

    protected Filesystem $filesystem;
    protected string $tempDir;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->tempDir = sys_get_temp_dir().'/tailwind-bundle-test-'.uniqid();
        $this->filesystem->mkdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        $i = 0;
        // Sometimes a file is still locked on Windows right after a process used it,
        // so try to clean up the dir a few times
        while (true) {
            try {
                $this->filesystem->remove($this->tempDir);
                break;
            } catch (IOException $e) {
                if ($i++ > 5) {
                    // Every test has its own temp dir, so a leftover doesn't affect the other tests
                    break;
                }
                // Try again in a second
                sleep(1);
            }
        }
    }

    protected static function createKernel(array $options = []): KernelInterface
    {
        return new TailwindTestKernel(
            $options['environment'] ?? 'test',
            $options['debug'] ?? false,
            $options['tailwind_config'] ?? [],
            $options['project_dir'] ?? null,
            $options['version_finder_releases'] ?? null,
        );
    }

    protected function commandTester(string $name): CommandTester
    {
        $application = new Application(self::$kernel);

        return new CommandTester($application->find($name));
    }
}
