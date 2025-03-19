<?php

/*
 * This file is part of the SymfonyCasts TailwindBundle package.
 * Copyright (c) SymfonyCasts <https://symfonycasts.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfonycasts\TailwindBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\AssetMapper\MappedAsset;
use Symfony\Component\Filesystem\Filesystem;

class FunctionalTest extends KernelTestCase
{
    private const BUILT_CSS_FILE = __DIR__.'/../var/tailwind/app.built.css';

    protected function setUp(): void
    {
        (new Filesystem())->remove(__DIR__.'/../var');
    }

    public function testExceptionThrownIfFileNotBuiltInNonTestEnv(): void
    {
        self::bootKernel(['environment' => 'dev']);
        $assetMapper = self::getContainer()->get('asset_mapper');
        \assert($assetMapper instanceof AssetMapperInterface);

        $this->expectException(\RuntimeException::class);
        $assetMapper->getAsset('styles/app.css');
    }

    public function testExceptionNotThrownIfFileNotBuiltInTestEnv(): void
    {
        $this->expectNotToPerformAssertions();

        self::bootKernel(['environment' => 'test']);
        $assetMapper = self::getContainer()->get('asset_mapper');
        \assert($assetMapper instanceof AssetMapperInterface);

        $assetMapper->getAsset('styles/app.css');
    }

    public function testBuiltCSSFileIsUsed(): void
    {
        (new Filesystem())->dumpFile(self::BUILT_CSS_FILE, <<<EOF
        body {
            padding: 17px;
            background-image: url('../images/penguin.png');
        }
        EOF);

        self::bootKernel();
        $assetMapper = self::getContainer()->get('asset_mapper');
        \assert($assetMapper instanceof AssetMapperInterface);

        $asset = $assetMapper->getAsset('styles/app.css');
        $this->assertInstanceOf(MappedAsset::class, $asset);
        $this->assertStringContainsString('padding: 17px', $asset->content);
        // verify the core CSS compiler that handles url() was executed
        $this->assertMatchesRegularExpression('/penguin-[a-f0-9]{32}|[\w\d-]{7}\.png/', $asset->content);
    }
}
