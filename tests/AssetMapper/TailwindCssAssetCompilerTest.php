<?php

/*
 * This file is part of the SymfonyCasts TailwindBundle package.
 * Copyright (c) SymfonyCasts <https://symfonycasts.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfonycasts\TailwindBundle\Tests\AssetMapper;

use PHPUnit\Framework\TestCase;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\AssetMapper\MappedAsset;
use Symfonycasts\TailwindBundle\AssetMapper\TailwindCssAssetCompiler;
use Symfonycasts\TailwindBundle\TailwindBuilder;

class TailwindCssAssetCompilerTest extends TestCase
{
    public function testCompile()
    {
        $builder = $this->createMock(TailwindBuilder::class);
        $builder->expects($this->any())
            ->method('getInputCssPaths')
            ->willReturn([__DIR__.'/../fixtures/assets/styles/app.css']);
        $builder->expects($this->once())
            ->method('getInternalOutputCssPath');
        $builder->expects($this->once())
            ->method('getOutputCssContent')
            ->willReturn('output content from Tailwind');

        $compiler = new TailwindCssAssetCompiler($builder);
        $asset1 = new MappedAsset('styles/other.css', __DIR__.'/../fixtures/assets/styles/other.css');
        // extra ../ added so the path doesn't exactly match the string used above
        $asset2 = new MappedAsset('styles/app.css', __DIR__.'/../../tests/fixtures/assets/styles/app.css');
        $this->assertFalse($compiler->supports($asset1));
        $this->assertTrue($compiler->supports($asset2));

        $this->assertSame('output content from Tailwind', $compiler->compile('input content', $asset2, $this->createMock(AssetMapperInterface::class)));
    }
}
