<?php

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use Symfonycasts\TailwindBundle\AssetMapper\TailwindCssAssetCompiler;
use Symfonycasts\TailwindBundle\Command\TailwindBuildCommand;
use Symfonycasts\TailwindBundle\Command\TailwindInitCommand;
use Symfonycasts\TailwindBundle\TailwindBinary;
use Symfonycasts\TailwindBundle\TailwindBuilder;
use function Symfony\Component\DependencyInjection\Loader\Configurator\abstract_arg;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('cache.symfonycasts.tailwind_bundle')
            ->parent('cache.system')
            ->tag('cache.pool')

        ->set('tailwind.builder', TailwindBuilder::class)
            ->args([
                param('kernel.project_dir'),
                abstract_arg('path to source Tailwind CSS file'),
                param('kernel.project_dir').'/var/tailwind',
                service('cache.symfonycasts.tailwind_bundle'),
                abstract_arg('path to tailwind binary'),
                abstract_arg('Tailwind binary version'),
                abstract_arg('path to Tailwind CSS config file'),
            ])

        ->set('tailwind.command.build', TailwindBuildCommand::class)
            ->args([
                service('tailwind.builder')
            ])
            ->tag('console.command')

        ->set('tailwind.command.init', TailwindInitCommand::class)
            ->args([
                service('tailwind.builder'),
            ])
            ->tag('console.command')

        ->set('tailwind.css_asset_compiler', TailwindCssAssetCompiler::class)
            ->args([
                service('tailwind.builder')
            ])
            ->tag('asset_mapper.compiler', [
                // run before core CssAssetUrlCompiler that resolves url() references
                'priority' => 10,
            ])
    ;
};
