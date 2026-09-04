<?php

/*
 * This file is part of the SymfonyCasts TailwindBundle package.
 * Copyright (c) SymfonyCasts <https://symfonycasts.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfonycasts\TailwindBundle\Tests\Command;

class TailwindBuildCommandTest extends BaseCommandTestCase
{
    public function testBuild(): void
    {
        self::bootKernel([
            'project_dir' => $this->tempDir,
            'tailwind_config' => [
                'input_css' => [self::FIXTURES_DIR.'/assets/styles/v4.css'],
                'binary_version' => 'v4.0.7',
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
                    self::FIXTURES_DIR.'/assets/styles/second.css',
                ],
                'binary_version' => 'v4.0.7',
            ],
        ]);

        $this->assertFileDoesNotExist($this->tempDir.'/var/tailwind/v4.built.css');
        $this->assertFileDoesNotExist($this->tempDir.'/var/tailwind/second.built.css');

        $tester = $this->commandTester('tailwind:build');
        $tester->execute([]);

        $tester->assertCommandIsSuccessful();
        $this->assertFileExists($this->tempDir.'/var/tailwind/v4.built.css');
        $this->assertFileExists($this->tempDir.'/var/tailwind/second.built.css');
    }
}
