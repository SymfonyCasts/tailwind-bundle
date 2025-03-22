<?php

/*
 * This file is part of the SymfonyCasts TailwindBundle package.
 * Copyright (c) SymfonyCasts <https://symfonycasts.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfonycasts\TailwindBundle\Tests\fixtures;

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Symfonycasts\TailwindBundle\SymfonycastsTailwindBundle;

class TailwindTestKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): array
    {
        return [
            new FrameworkBundle(),
            new SymfonycastsTailwindBundle(),
        ];
    }

    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        $container->loadFromExtension('framework', [
            'secret' => 'foo',
            'test' => true,
            'http_method_override' => true,
            'asset_mapper' => [
                'paths' => [
                    __DIR__.'/assets',
                ],
            ],
            'handle_all_throwables' => true,
            'php_errors' => [
                'log' => true,
            ],
        ]);

        $container->loadFromExtension('symfonycasts_tailwind', [
            'input_css' => [__DIR__.'/assets/styles/app.css'],
            'binary_version' => 'v3.4.17',
        ]);
    }
}
