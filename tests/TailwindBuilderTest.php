<?php

/*
 * This file is part of the SymfonyCasts TailwindBundle package.
 * Copyright (c) SymfonyCasts <https://symfonycasts.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfonycasts\TailwindBundle\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfonycasts\TailwindBundle\TailwindBuilder;

class TailwindBuilderTest extends TestCase
{
    protected function setUp(): void
    {
        $fs = new Filesystem();
        if (file_exists(__DIR__.'/fixtures/var/tailwind')) {
            $fs->remove(__DIR__.'/fixtures/var/tailwind');
        }
        $fs->mkdir(__DIR__.'/fixtures/var/tailwind');
    }

    protected function tearDown(): void
    {
        $finder = new Finder();
        $finder->in(__DIR__.'/fixtures/var/tailwind')->files();
        foreach ($finder as $file) {
            unlink($file->getRealPath());
        }
    }

    public function testIntegration(): void
    {
        $builder = new TailwindBuilder(
            __DIR__.'/fixtures',
            __DIR__.'/fixtures/assets/styles/app.css',
            __DIR__.'/fixtures/var/tailwind'
        );
        $process = $builder->runBuild(false);
        $process->wait();

        $this->assertTrue($process->isSuccessful());
        $this->assertFileExists(__DIR__.'/fixtures/var/tailwind/tailwind.built.css');
    }
}
