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
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Process\Process;
use Symfonycasts\TailwindBundle\TailwindBinary;

class TailwindBinaryTest extends TestCase
{
    /**
     * @dataProvider platformAndVersionProvider
     */
    public function testBinaryIsDownloadedAndProcessCreated(string $version, string $platform, string $expectedBinaryName)
    {
        $binaryDownloadDir = __DIR__.'/fixtures/download';
        $fs = new Filesystem();
        if (file_exists($binaryDownloadDir)) {
            $fs->remove($binaryDownloadDir);
        }
        $fs->mkdir($binaryDownloadDir);

        $client = new MockHttpClient([
            new MockResponse('fake binary contents'),
        ]);

        $binary = new TailwindBinary($binaryDownloadDir, __DIR__, null, 'fake-version', null, $client, $platform);
        $process = $binary->createProcess(['-i', 'fake.css']);
        $binaryFile = $binaryDownloadDir.'/fake-version/'.$expectedBinaryName;
        $this->assertFileExists($binaryFile);

        $this->assertSame(
            (new Process([$binaryFile, '-i', 'fake.css'], __DIR__))->getCommandLine(),
            $process->getCommandLine()
        );
    }

    /**
     * @dataProvider versionProvider
     */
    public function testGetVersionFromBinary(string $version)
    {
        $binaryDownloadDir = __DIR__.'/fixtures/download';
        $fs = new Filesystem();
        if (file_exists($binaryDownloadDir)) {
            $fs->remove($binaryDownloadDir);
        }
        $fs->mkdir($binaryDownloadDir);
        $binaryFile = $binaryDownloadDir.'/'.$version.'/'.TailwindBinary::getBinaryName(ltrim($version, 'v'));

        $binary1 = new TailwindBinary($binaryDownloadDir, __DIR__, null, $version);

        $binary1->createProcess();
        $this->assertFileExists($binaryFile);
        $this->assertSame($version, $binary1->getVersion());

        // add both the binary path and invalid version to ensure version isn't used
        $binary2 = new TailwindBinary($binaryDownloadDir, __DIR__, $binaryFile, 'v2.2.2');

        $this->assertSame($version, $binary2->getVersion());
    }

    public static function versionProvider(): iterable
    {
        yield ['v3.4.17'];
        yield ['v4.0.7'];
    }

    public function testCustomBinaryUsed()
    {
        $client = new MockHttpClient();

        $binary = new TailwindBinary('', __DIR__, 'custom-binary', null, null, $client);
        $process = $binary->createProcess(['-i', 'fake.css']);
        // on windows, arguments are not wrapped in quotes
        $expected = '\\' === \DIRECTORY_SEPARATOR ? 'custom-binary -i fake.css' : "'custom-binary' '-i' 'fake.css'";
        $this->assertSame(
            $expected,
            $process->getCommandLine()
        );
    }

    public function platformAndVersionProvider(): iterable
    {
        yield ['3.4.17', 'linux-arm64', 'tailwindcss-linux-arm64'];
        yield ['3.4.17', 'linux-armv7', 'tailwindcss-linux-armv7'];
        yield ['3.4.17', 'linux-x64', 'tailwindcss-linux-x64'];
        yield ['3.4.17', 'macos-arm64', 'tailwindcss-macos-arm64'];
        yield ['3.4.17', 'macos-x64', 'tailwindcss-macos-x64'];
        yield ['3.4.17', 'windows-x64', 'tailwindcss-windows-x64.exe'];
        yield ['4.0.0', 'linux-arm64', 'tailwindcss-linux-arm64'];
        yield ['4.0.0', 'linux-arm64-musl', 'tailwindcss-linux-arm64-musl'];
        yield ['4.0.0', 'linux-x64', 'tailwindcss-linux-x64'];
        yield ['4.0.0', 'linux-x64-musl', 'tailwindcss-linux-x64-musl'];
        yield ['4.0.0', 'macos-arm64', 'tailwindcss-macos-arm64'];
        yield ['4.0.0', 'macos-x64', 'tailwindcss-macos-x64'];
        yield ['4.0.0', 'windows-x64', 'tailwindcss-windows-x64.exe'];
    }
}
