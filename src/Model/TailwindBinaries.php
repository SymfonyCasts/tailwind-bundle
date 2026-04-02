<?php

/*
 * This file is part of the SymfonyCasts TailwindBundle package.
 * Copyright (c) SymfonyCasts <https://symfonycasts.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfonycasts\TailwindBundle\Model;

class TailwindBinaries
{
    /**
     * @param TailwindBinary[] $assets
     */
    public function __construct(
        private readonly string $name,
        private readonly string $publishedAt,
        private readonly array $assets,
    ) {
    }

    public function getAssetByBinaryName(string $assetName): ?TailwindBinary
    {
        foreach ($this->assets as $asset) {
            if ($asset->getName() === $assetName) {
                return $asset;
            }
        }

        return null;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPublishedAt(): string
    {
        return $this->publishedAt;
    }

    /** @return TailwindBinary[] */
    public function getAssets(): array
    {
        return $this->assets;
    }
}
