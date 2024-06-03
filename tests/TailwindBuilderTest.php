<?php

/*
 * This file is part of the SymfonyCasts TailwindBundle package.
 * Copyright (c) SymfonyCasts <https://symfonycasts.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfonycasts\TailwindBundle\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
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

    public function testIntegrationWithDefaultOptions(): void
    {
        $builder = new TailwindBuilder(
            __DIR__.'/fixtures',
            [__DIR__.'/fixtures/assets/styles/app.css'],
            __DIR__.'/fixtures/var/tailwind',
            new ArrayAdapter(),
            null,
            null,
            __DIR__.'/fixtures/tailwind.config.js'
        );
        $process = $builder->runBuild(watch: false, poll: false, minify: false);
        $process->wait();

        $this->assertTrue($process->isSuccessful());
        $this->assertFileExists(__DIR__.'/fixtures/var/tailwind/app.built.css');

        $outputFileContents = file_get_contents(__DIR__.'/fixtures/var/tailwind/app.built.css');
        $this->assertStringContainsString("body {\n  background-color: red;\n}", $outputFileContents, 'The output file should contain non-minified CSS.');
    }

    public function testIntegrationWithMinify(): void
    {
        $builder = new TailwindBuilder(
            __DIR__.'/fixtures',
            [__DIR__.'/fixtures/assets/styles/app.css'],
            __DIR__.'/fixtures/var/tailwind',
            new ArrayAdapter(),
            null,
            null,
            __DIR__.'/fixtures/tailwind.config.js'
        );
        $process = $builder->runBuild(watch: false, poll: false, minify: true);
        $process->wait();

        $this->assertTrue($process->isSuccessful());
        $this->assertFileExists(__DIR__.'/fixtures/var/tailwind/app.built.css');

        $outputFileContents = file_get_contents(__DIR__.'/fixtures/var/tailwind/app.built.css');
        $this->assertStringContainsString('body{background-color:red}', $outputFileContents, 'The output file should contain minified CSS.');
    }

    public function testBuildProvidedInputFile(): void
    {
        $builder = new TailwindBuilder(
            __DIR__.'/fixtures',
            [__DIR__.'/fixtures/assets/styles/app.css', __DIR__.'/fixtures/assets/styles/second.css'],
            __DIR__.'/fixtures/var/tailwind',
            new ArrayAdapter(),
            null,
            null,
            __DIR__.'/fixtures/tailwind.config.js'
        );
        $process = $builder->runBuild(watch: false, poll: false, minify: true, inputFile: 'assets/styles/second.css');
        $process->wait();

        $this->assertTrue($process->isSuccessful());
        $this->assertFileExists(__DIR__.'/fixtures/var/tailwind/second.built.css');

        $outputFileContents = file_get_contents(__DIR__.'/fixtures/var/tailwind/second.built.css');
        $this->assertStringContainsString('body{background-color:blue}', $outputFileContents, 'The output file should contain minified CSS.');
    }
}
