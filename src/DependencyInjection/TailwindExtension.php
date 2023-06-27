<?php

namespace Symfonycasts\TailwindBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class TailwindExtension extends Extension implements ConfigurationInterface
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\PhpFileLoader($container, new FileLocator(__DIR__.'/../../config'));
        $loader->load('services.php');

        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $container->findDefinition('tailwind.builder')
            ->replaceArgument(0, $config['css_path']);
    }

    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return $this;
    }

    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('tailwind');
        $rootNode = $treeBuilder->getRootNode();
        \assert($rootNode instanceof ArrayNodeDefinition);

        $rootNode
            ->children()
                ->scalarNode('css_path')
                    ->info('Path to CSS file to process through Tailwind (AssetMapper only)')
                    ->defaultValue('%kernel.project_dir%/assets/styles/app.css')
                ->end()
            ->end();

        return $treeBuilder;
    }
}
