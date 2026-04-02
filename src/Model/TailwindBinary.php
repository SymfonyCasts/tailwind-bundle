<?php

/*
 * This file is part of the SymfonyCasts TailwindBundle package.
 * Copyright (c) SymfonyCasts <https://symfonycasts.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfonycasts\TailwindBundle\Model;

class TailwindBinary
{
    public function __construct(
        private readonly string $name,
        private readonly string $contentType,
        private readonly int $size,
        private readonly string $digest,
        private readonly string $createdAt,
        private readonly string $downloadUrl,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getContentType(): string
    {
        return $this->contentType;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getDigest(): string
    {
        if (empty($this->digest)) {
            return '';
        }

        return explode(':', $this->digest, 2)[1];
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function getDownloadUrl(): string
    {
        return $this->downloadUrl;
    }
}
