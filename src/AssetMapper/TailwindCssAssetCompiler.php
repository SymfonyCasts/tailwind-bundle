<?php

/*
 * This file is part of the SymfonyCasts TailwindBundle package.
 * Copyright (c) SymfonyCasts <https://symfonycasts.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfonycasts\TailwindBundle\AssetMapper;

use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\AssetMapper\Compiler\AssetCompilerInterface;
use Symfony\Component\AssetMapper\MappedAsset;
use Symfonycasts\TailwindBundle\TailwindBuilder;

/**
 * Intercepts the "input" Tailwind CSS file and changes its contents to the built version.
 */
class TailwindCssAssetCompiler implements AssetCompilerInterface
{
    public function __construct(private TailwindBuilder $tailwindBuilder)
    {
    }

    public function supports(MappedAsset $asset): bool
    {
        return \in_array(
            realpath($asset->sourcePath),
            $this->tailwindBuilder->getInputCssPaths(),
        );
    }

    public function compile(string $content, MappedAsset $asset, AssetMapperInterface $assetMapper): string
    {
        $asset->addFileDependency($this->tailwindBuilder->getInternalOutputCssPath($asset->sourcePath));

        return $this->tailwindBuilder->getOutputCssContent($asset->sourcePath);
    }
}
