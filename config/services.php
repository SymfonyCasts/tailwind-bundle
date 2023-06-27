<?php

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use Symfonycasts\TailwindBundle\Command\TailwindBuildCommand;
use Symfonycasts\TailwindBundle\TailwindBinary;
use Symfonycasts\TailwindBundle\TailwindBuilder;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container
        ->services()
            ->set('tailwind.builder', TailwindBuilder::class)
            ->args([
                // TODO: make dynamic
                param('kernel.project_dir').'/assets/styles/app.css',
                param('kernel.project_dir').'/var/tailwind',
            ])

            ->set('tailwind.command.build', TailwindBuildCommand::class)
            ->args([
                service('tailwind.builder')
            ])
            ->tag('console.command')
    ;
};
