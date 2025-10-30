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
    public function __construct(
        private string $name,
        private string $publishedAt,
        private array $assets,
    ) {
    }

    public function getAssetByBinaryName(string $assetName)
    {
        foreach ($this->assets as $asset) {
            if ($asset->getName() !== $assetName) {
                continue;
            }

            return $asset;
        }

        return null;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getPublishedAt(): string
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(string $publishedAt): void
    {
        $this->publishedAt = $publishedAt;
    }

    public function getAssets(): array
    {
        return $this->assets;
    }

    public function setAssets(array $assets): void
    {
        $this->assets = $assets;
    }
}
