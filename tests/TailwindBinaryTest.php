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
    public function testBinaryIsDownloadedAndProcessCreated(string $version, string $platform, string $expectedBinaryName): void
    {
        $binaryDownloadDir = __DIR__.'/fixtures/download';

        $fs = new Filesystem();
        if (file_exists($binaryDownloadDir)) {
            $fs->remove($binaryDownloadDir);
        }
        $fs->mkdir($binaryDownloadDir);

        $binaryContents = 'fake binary contents';
        $client = new MockHttpClient([
            new MockResponse(\sprintf("%s  %s\n", hash('sha256', $binaryContents), $expectedBinaryName)),
            new MockResponse($binaryContents),
        ]);

        $binary = new TailwindBinary($binaryDownloadDir, __DIR__, null, $version, null, $client, $platform);
        $process = $binary->createProcess(['-i', 'fake.css']);
        $binaryFile = $binaryDownloadDir.'/'.$version.'/'.$expectedBinaryName;
        $this->assertFileExists($binaryFile);

        $this->assertSame(
            (new Process([$binaryFile, '-i', 'fake.css'], __DIR__))->getCommandLine(),
            $process->getCommandLine()
        );
    }

    /**
     * @dataProvider versionProvider
     */
    public function testGetVersionFromBinary(string $version): void
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

    public function testCustomBinaryUsed(): void
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

    public function testCanBeConstructedWithoutBinaryOrVersion(): void
    {
        // the bundle must be usable before "tailwind:init" is run, so
        // construction must not require a binary or version
        $binary = new TailwindBinary(__DIR__.'/fixtures/download', __DIR__, null, null, null, new MockHttpClient());

        $this->assertInstanceOf(TailwindBinary::class, $binary);
    }

    /**
     * @dataProvider publicInstanceMethodProvider
     */
    public function testThrowsWhenUsedWithoutBinaryOrVersion(\Closure $call): void
    {
        $binary = new TailwindBinary(__DIR__.'/fixtures/download', __DIR__, null, null, null, new MockHttpClient());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('You must specify a "binary" or "binary_version"');

        $call($binary);
    }

    public function testZeroByteFileIsReplacedOnRedownload(): void
    {
        $binaryDownloadDir = __DIR__.'/fixtures/download';

        $fs = new Filesystem();
        if (file_exists($binaryDownloadDir)) {
            $fs->remove($binaryDownloadDir);
        }
        $fs->mkdir($binaryDownloadDir.'/v4.1.16');

        // place a 0-byte file to simulate a corrupted/interrupted download
        $binaryName = 'tailwindcss-linux-x64';
        $corruptFile = $binaryDownloadDir.'/v4.1.16/'.$binaryName;
        file_put_contents($corruptFile, '');
        $this->assertSame(0, filesize($corruptFile));

        $binaryContents = 'fake binary contents';
        $client = new MockHttpClient([
            new MockResponse(\sprintf("%s  %s\n", hash('sha256', $binaryContents), $binaryName)),
            new MockResponse($binaryContents),
        ]);

        $binary = new TailwindBinary($binaryDownloadDir, __DIR__, null, 'v4.1.16', null, $client, 'linux-x64');
        $binary->createProcess(['-i', 'fake.css']);

        $this->assertFileExists($corruptFile);
        $this->assertGreaterThan(0, filesize($corruptFile));
    }

    public function testMissingChecksumsSkipsIntegrityCheck(): void
    {
        $binaryDownloadDir = __DIR__.'/fixtures/download';

        $fs = new Filesystem();
        if (file_exists($binaryDownloadDir)) {
            $fs->remove($binaryDownloadDir);
        }
        $fs->mkdir($binaryDownloadDir);

        // older releases have no "sha256sums.txt": the fetch 404s and the
        // integrity check is skipped, so the download still succeeds
        $client = new MockHttpClient([
            new MockResponse('', ['http_code' => 404]),
            new MockResponse('fake binary contents'),
        ]);

        $binary = new TailwindBinary($binaryDownloadDir, __DIR__, null, 'v3.0.0', null, $client, 'linux-x64');
        $binary->createProcess(['-i', 'fake.css']);

        $binaryFile = $binaryDownloadDir.'/v3.0.0/tailwindcss-linux-x64';
        $this->assertFileExists($binaryFile);
        $this->assertGreaterThan(0, filesize($binaryFile));
    }

    public function testEmptyDownloadThrowsDescriptiveException(): void
    {
        $binaryDownloadDir = __DIR__.'/fixtures/download';

        $fs = new Filesystem();
        if (file_exists($binaryDownloadDir)) {
            $fs->remove($binaryDownloadDir);
        }
        $fs->mkdir($binaryDownloadDir);

        // no checksums (404) + an empty binary body, as antivirus quarantine leaves it
        $client = new MockHttpClient([
            new MockResponse('', ['http_code' => 404]),
            new MockResponse(''),
        ]);

        $binary = new TailwindBinary($binaryDownloadDir, __DIR__, null, 'v4.1.16', null, $client, 'linux-x64');
        $binaryFile = $binaryDownloadDir.'/v4.1.16/tailwindcss-linux-x64';

        try {
            $binary->createProcess(['-i', 'fake.css']);
            $this->fail('Expected a RuntimeException for the empty download.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('antivirus', $e->getMessage());
        }

        // the empty file must have been removed
        $this->assertFileDoesNotExist($binaryFile);
    }

    public function testIntegrityFailureDeletesFileAndThrows(): void
    {
        $binaryDownloadDir = __DIR__.'/fixtures/download';

        $fs = new Filesystem();
        if (file_exists($binaryDownloadDir)) {
            $fs->remove($binaryDownloadDir);
        }
        $fs->mkdir($binaryDownloadDir);

        $binaryName = 'tailwindcss-linux-x64';

        // advertise the hash of one payload but serve a different one so the hashes won't match
        $client = new MockHttpClient([
            new MockResponse(\sprintf("%s  %s\n", hash('sha256', 'the real binary'), $binaryName)),
            new MockResponse('this content does not match the advertised hash'),
        ]);

        $binary = new TailwindBinary($binaryDownloadDir, __DIR__, null, 'v4.1.16', null, $client, 'linux-x64');
        $binaryFile = $binaryDownloadDir.'/v4.1.16/'.$binaryName;

        try {
            $binary->createProcess(['-i', 'fake.css']);
            $this->fail('Expected a RuntimeException for the integrity check failure.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('integrity check', $e->getMessage());
        }

        // the corrupt file must have been removed
        $this->assertFileDoesNotExist($binaryFile);
    }

    public static function publicInstanceMethodProvider(): iterable
    {
        yield 'createProcess' => [static fn (TailwindBinary $b) => $b->createProcess()];
        yield 'getVersion' => [static fn (TailwindBinary $b) => $b->getVersion()];
        yield 'getRawVersion' => [static fn (TailwindBinary $b) => $b->getRawVersion()];
        yield 'isV4' => [static fn (TailwindBinary $b) => $b->isV4()];
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
