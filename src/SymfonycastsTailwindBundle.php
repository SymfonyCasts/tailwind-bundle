<?php

/*
 * This file is part of the SymfonyCasts TailwindBundle package.
 * Copyright (c) SymfonyCasts <https://symfonycasts.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfonycasts\TailwindBundle;

use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfonycasts\TailwindBundle\DependencyInjection\TailwindExtension;

class SymfonycastsTailwindBundle extends Bundle
{
    protected function createContainerExtension(): ?ExtensionInterface
    {
        return new TailwindExtension();
    }
}
