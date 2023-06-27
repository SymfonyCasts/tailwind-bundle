<?php

namespace Symfonycasts\TailwindBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfonycasts\TailwindBundle\DependencyInjection\TailwindExtension;

class TailwindBundle extends Bundle
{
    protected function createContainerExtension(): ?ExtensionInterface
    {
        return new TailwindExtension();
    }
}
