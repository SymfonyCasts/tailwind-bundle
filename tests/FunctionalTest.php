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
    protected function setUp(): void
    {
        $fs = new Filesystem();
        $tailwindVarDir = __DIR__.'/fixtures/var/tailwind';
        if (is_dir($tailwindVarDir)) {
            $fs->remove($tailwindVarDir);
        }
        $fs->mkdir($tailwindVarDir);
        file_put_contents($tailwindVarDir.'/tailwind.built.css', <<<EOF
        body {
            padding: 17px;
            background-image: url('../images/penguin.png');
        }
        EOF
        );
    }

    protected function tearDown(): void
    {
        if (is_file(__DIR__.'/fixtures/var/tailwind/tailwind.built.css')) {
            unlink(__DIR__.'/fixtures/var/tailwind/tailwind.built.css');
        }
    }

    public function testBuiltCSSFileIsUsed(): void
    {
        self::bootKernel();
        $assetMapper = self::getContainer()->get('asset_mapper');
        \assert($assetMapper instanceof AssetMapperInterface);

        $asset = $assetMapper->getAsset('styles/app.css');
        $this->assertInstanceOf(MappedAsset::class, $asset);
        $this->assertStringContainsString('padding: 17px', $asset->content);
        // verify the core CSS compiler that handles url() was executed
        $this->assertMatchesRegularExpression('/penguin-[a-f0-9]{32}\.png/', $asset->content);
    }
}
