<?php

/*
 * This file is part of the SymfonyCasts TailwindBundle package.
 * Copyright (c) SymfonyCasts <https://symfonycasts.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfonycasts\TailwindBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader;

class TailwindExtension extends Extension implements ConfigurationInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new Loader\PhpFileLoader($container, new FileLocator(__DIR__.'/../../config'));
        $loader->load('services.php');

        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $container->findDefinition('tailwind.builder')
            ->replaceArgument(1, $config['input_css'])
            ->replaceArgument(4, $config['binary'])
            ->replaceArgument(5, $config['binary_version'])
            ->replaceArgument(6, $config['config_file'])
        ;
    }

    public function getConfiguration(array $config, ContainerBuilder $container): ?ConfigurationInterface
    {
        return $this;
    }

    public function getAlias(): string
    {
        return 'symfonycasts_tailwind';
    }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('symfonycasts_tailwind');
        $rootNode = $treeBuilder->getRootNode();
        \assert($rootNode instanceof ArrayNodeDefinition);

        $rootNode
            ->children()
                ->arrayNode('input_css')
                    ->prototype('scalar')->end()
                    ->beforeNormalization()->castToArray()->end()
                    ->info('Paths to CSS files to process through Tailwind')
                    ->defaultValue(['%kernel.project_dir%/assets/styles/app.css'])
                ->end()
                ->scalarNode('config_file')
                    ->info('Path to the tailwind.config.js file')
                    ->defaultValue('%kernel.project_dir%/tailwind.config.js')
                ->end()
                ->scalarNode('binary')
                    ->info('The tailwind binary to use instead of downloading a new one')
                    ->defaultNull()
                ->end()
                ->scalarNode('binary_version')
                    ->info('Tailwind CLI version to download - null means the latest version')
                    ->defaultNull()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
