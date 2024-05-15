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
use Symfony\Contracts\Cache\CacheInterface;
use Symfonycasts\TailwindBundle\TailwindBinary;

class TailwindBinaryTest extends TestCase
{
    public function testBinaryIsDownloadedAndProcessCreated()
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
        $cache = $this->createMock(CacheInterface::class);

        $binary = new TailwindBinary($binaryDownloadDir, __DIR__, null, 'fake-version', $cache, null, $client);
        $process = $binary->createProcess(['-i', 'fake.css']);
        $this->assertFileExists($binaryDownloadDir.'/fake-version/'.TailwindBinary::getBinaryName());

        // Windows doesn't wrap arguments in quotes
        $expectedTemplate = '\\' === \DIRECTORY_SEPARATOR ? '"%s" -i fake.css' : "'%s' '-i' 'fake.css'";

        $this->assertSame(
            sprintf($expectedTemplate, $binaryDownloadDir.'/fake-version/'.TailwindBinary::getBinaryName()),
            $process->getCommandLine()
        );
    }

    public function testCustomBinaryUsed()
    {
        $client = new MockHttpClient();
        $cache = $this->createMock(CacheInterface::class);

        $binary = new TailwindBinary('', __DIR__, 'custom-binary', null, $cache, null, $client);
        $process = $binary->createProcess(['-i', 'fake.css']);
        // on windows, arguments are not wrapped in quotes
        $expected = '\\' === \DIRECTORY_SEPARATOR ? 'custom-binary -i fake.css' : "'custom-binary' '-i' 'fake.css'";
        $this->assertSame(
            $expected,
            $process->getCommandLine()
        );
    }
}
