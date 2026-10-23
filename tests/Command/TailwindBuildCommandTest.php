<?php

/*
 * This file is part of the SymfonyCasts TailwindBundle package.
 * Copyright (c) SymfonyCasts <https://symfonycasts.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfonycasts\TailwindBundle\Tests\Command;

use Symfony\Component\Console\Command\Command;
use Symfonycasts\TailwindBundle\Tests\SharedTailwindBinary;

class TailwindBuildCommandTest extends BaseCommandTestCase
{
    public function testBuild(): void
    {
        self::bootKernel([
            'project_dir' => $this->tempDir,
            'tailwind_config' => [
                'input_css' => [self::FIXTURES_DIR.'/assets/styles/v4.css'],
                'binary' => SharedTailwindBinary::path('v4.0.7'),
            ],
        ]);

        $builtCss = $this->tempDir.'/var/tailwind/v4.built.css';
        $this->assertFileDoesNotExist($builtCss);

        $tester = $this->commandTester('tailwind:build');
        $tester->execute([]);

        $tester->assertCommandIsSuccessful();
        $this->assertFileExists($builtCss);
    }

    public function testBuildEveryConfiguredInput(): void
    {
        self::bootKernel([
            'project_dir' => $this->tempDir,
            'tailwind_config' => [
                'input_css' => [
                    self::FIXTURES_DIR.'/assets/styles/v4.css',
                    self::FIXTURES_DIR.'/assets/styles/v4-second.css',
                ],
                'binary' => SharedTailwindBinary::path('v4.0.7'),
            ],
        ]);

        $this->assertFileDoesNotExist($this->tempDir.'/var/tailwind/v4.built.css');
        $this->assertFileDoesNotExist($this->tempDir.'/var/tailwind/v4-second.built.css');

        $tester = $this->commandTester('tailwind:build');
        $tester->execute([]);

        $tester->assertCommandIsSuccessful();
        $this->assertFileExists($this->tempDir.'/var/tailwind/v4.built.css');
        $this->assertFileExists($this->tempDir.'/var/tailwind/v4-second.built.css');
    }

    /**
     * @dataProvider provideInputsWithAnInvalidOne
     */
    public function testBuildFailsWhenAnInputIsInvalid(array $inputs): void
    {
        self::bootKernel([
            'project_dir' => $this->tempDir,
            'tailwind_config' => [
                'input_css' => $inputs,
                'binary' => SharedTailwindBinary::path('v4.0.7'),
            ],
        ]);

        $tester = $this->commandTester('tailwind:build');
        $tester->execute([]);

        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
        $this->assertStringContainsString('Cannot apply unknown utility class', $tester->getDisplay());
        // the valid input is still built
        $this->assertFileExists($this->tempDir.'/var/tailwind/v4.built.css', $tester->getDisplay());
    }

    public static function provideInputsWithAnInvalidOne(): iterable
    {
        yield 'invalid input last' => [[
            self::FIXTURES_DIR.'/assets/styles/v4.css',
            self::FIXTURES_DIR.'/assets/styles/v4-invalid.css',
        ]];

        yield 'invalid input first' => [[
            self::FIXTURES_DIR.'/assets/styles/v4-invalid.css',
            self::FIXTURES_DIR.'/assets/styles/v4.css',
        ]];
    }
}
