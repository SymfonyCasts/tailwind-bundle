<?php

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
        return $asset->sourcePath === $this->tailwindBuilder->getInputCssPath();
    }

    public function compile(string $content, MappedAsset $asset, AssetMapperInterface $assetMapper): string
    {
        $asset->addFileDependency($this->tailwindBuilder->getInternalOutputCssPath());

        return $this->tailwindBuilder->getOutputCssContent();
    }
}
