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
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfonycasts\TailwindBundle\TailwindBuilder;

class TailwindBuilderTest extends TestCase
{

    protected function setUp(): void
    {
        $fs = new Filesystem();
        $fs->mkdir(__DIR__.'/fixtures/var/tailwind');
    }

    protected function tearDown(): void
    {
        $fs = new Filesystem();
        $i = 0;
        // Sometimes "Permission denied" error happens on Windows
        // so try to clean up the dir a few times
        while (true) {
            try {
                $fs->remove(__DIR__.'/fixtures/var/tailwind');
                break;
            } catch (IOException $e) {
                if ($i++ > 5) {
                    // Still not able to delete it? Throw
                    throw $e;
                }
                // Try again in a second
                sleep(1);
            }
        }
    }

    protected function isTailwindBinaryEqualOrGreaterThan4(): bool
    {
        $versionBuilder = new TailwindBuilder(
            '',
            [],
            '',
            new ArrayAdapter(),
        );
        return $versionBuilder->isBinaryVersionEqualOrGreaterThan4();
    }

    public function testIntegrationWithDefaultOptions(): void
    {
        $cssFilesSuffix = $this->isTailwindBinaryEqualOrGreaterThan4() ? '-v4' : '';

        $builder = new TailwindBuilder(
            __DIR__.'/fixtures',
            [__DIR__.'/fixtures/assets/styles/app'.$cssFilesSuffix.'.css'],
            __DIR__.'/fixtures/var/tailwind',
            new ArrayAdapter(),
            null,
            null,
            __DIR__.'/fixtures/tailwind.config.js'
        );
        $process = $builder->runBuild(watch: false, poll: false, minify: false);
        $process->wait();

        $this->assertTrue($process->isSuccessful());
        $this->assertFileExists(__DIR__.'/fixtures/var/tailwind/app'.$cssFilesSuffix.'.built.css');

        $outputFileContents = file_get_contents(__DIR__.'/fixtures/var/tailwind/app'.$cssFilesSuffix.'.built.css');
        $this->assertStringContainsString("body {\n  background-color: #ef4444;\n}", $outputFileContents, 'The output file should contain non-minified CSS.');
    }

    public function testIntegrationWithMinify(): void
    {
        $cssFilesSuffix = $this->isTailwindBinaryEqualOrGreaterThan4() ? '-v4' : '';

        $builder = new TailwindBuilder(
            __DIR__.'/fixtures',
            [__DIR__.'/fixtures/assets/styles/app'.$cssFilesSuffix.'.css'],
            __DIR__.'/fixtures/var/tailwind',
            new ArrayAdapter(),
            null,
            null,
            __DIR__.'/fixtures/tailwind.config.js'
        );
        $process = $builder->runBuild(watch: false, poll: false, minify: true);
        $process->wait();

        $this->assertTrue($process->isSuccessful());
        $this->assertFileExists(__DIR__.'/fixtures/var/tailwind/app'.$cssFilesSuffix.'.built.css');

        $outputFileContents = file_get_contents(__DIR__.'/fixtures/var/tailwind/app'.$cssFilesSuffix.'.built.css');
        $this->assertStringContainsString('body{background-color:#ef4444}', $outputFileContents, 'The output file should contain minified CSS.');
    }

    public function testBuildProvidedInputFile(): void
    {
        $cssFilesSuffix = $this->isTailwindBinaryEqualOrGreaterThan4() ? '-v4' : '';

        $builder = new TailwindBuilder(
            __DIR__.'/fixtures',
            [__DIR__.'/fixtures/assets/styles/app'.$cssFilesSuffix.'.css', __DIR__.'/fixtures/assets/styles/second'.$cssFilesSuffix.'.css'],
            __DIR__.'/fixtures/var/tailwind',
            new ArrayAdapter(),
            null,
            null,
            __DIR__.'/fixtures/tailwind.config.js'
        );
        $process = $builder->runBuild(watch: false, poll: false, minify: true, inputFile: 'assets/styles/second'.$cssFilesSuffix.'.css');
        $process->wait();

        $this->assertTrue($process->isSuccessful());
        $this->assertFileExists(__DIR__.'/fixtures/var/tailwind/second'.$cssFilesSuffix.'.built.css');

        $outputFileContents = file_get_contents(__DIR__.'/fixtures/var/tailwind/second'.$cssFilesSuffix.'.built.css');
        $this->assertStringContainsString('body{background-color:#3b82f6}', $outputFileContents, 'The output file should contain minified CSS.');
    }

    public function testIntegrationWithPostcss(): void
    {
        $cssFilesSuffix = $this->isTailwindBinaryEqualOrGreaterThan4() ? '-v4' : '';
        if ($cssFilesSuffix) {
            $this->markTestSkipped('Postcss seems not compatible with Tailwind CLI v4!');
        }

        $builder = new TailwindBuilder(
            __DIR__.'/fixtures',
            [__DIR__.'/fixtures/assets/styles/app'.$cssFilesSuffix.'.css'],
            __DIR__.'/fixtures/var/tailwind',
            new ArrayAdapter(),
            null,
            null,
            __DIR__.'/fixtures/tailwind.config.js',
            __DIR__.'/fixtures/postcss.config.js',
        );
        $process = $builder->runBuild(watch: false, poll: false, minify: false);
        $process->wait();

        $this->assertTrue($process->isSuccessful());
        $this->assertFileExists(__DIR__.'/fixtures/var/tailwind/app'.$cssFilesSuffix.'.built.css');

        $outputFileContents = file_get_contents(__DIR__.'/fixtures/var/tailwind/app'.$cssFilesSuffix.'.built.css');
        $this->assertStringContainsString('.dummy {}', $outputFileContents, 'The output file should contain the dummy CSS added by the dummy plugin.');
    }
}
