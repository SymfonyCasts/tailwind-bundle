# Tailwind CSS for Symfony!

This bundle makes it easy to use [Tailwind CSS](https://tailwindcss.com/) with
Symfony's [AssetMapper Component](https://symfony.com/doc/current/frontend/asset_mapper.html)
(no Node required!).

* Automatically downloads the correct [standalone Tailwind CSS binary](https://tailwindcss.com/blog/standalone-cli);
* Adds a `tailwind:build` command to build & watch for changes;
* Transparently swaps in the compiled CSS.

> **Note**
> Want to use Tailwind CSS with WebpackEncore instead? Check out
> the [Tailwind + Symfony Docs](https://tailwindcss.com/docs/guides/symfony).

## Installation

Install the bundle & initialize your app with two commands:

```bash
composer require symfonycasts/tailwind-bundle
php bin/console tailwind:init
```

Done! This will create a ``tailwind.config.js`` file and make sure your
``assets/styles/app.css`` contains the Tailwind directives.

## Usage

To use the Tailwind CSS file, start by including the input file
(`assets/styles/app.css` by default) in `base.html.twig`. It's quite likely
you already have this:

```twig
{# templates.base.html.twig #}

{% block stylesheets %}
    <link rel="stylesheet" href="{{ asset('styles/app.css') }}">
{% endblock %}
```

The bundle works by swapping out the contents of `styles/app.css` with the
compiled CSS automatically. For this to work, you need to run the `tailwind:build`
command:

```bash
php bin/console tailwind:build --watch
```

That's it! This will watch for changes to your `assets/styles/app.css` file
and automatically recompile it when needed. If you refresh the page, the
final `app.css` file will already contain the compiled CSS.

## How Does It Work?

The first time you run one of the Tailwind commands, the bundle will
download the correct Tailwind binary for your system into a `var/tailwind/`
directory.

When you run `tailwind:build`, that binary is used to compile
your CSS file into a `var/tailwind/tailwind.built.css` file. Finally,
when the contents of `assets/styles/app.css` is requested, the bundle
swaps the contents of that file with the contents of `var/tailwind/tailwind.built.css`.
Nice!

## Deploying

When you deploy, run the `tailwind:build` command *before* the `asset-map:compile`
command so the built file is available:

```bash
php bin/console tailwind:build
php bin/console asset-map:compile
```

## Configuration

To see the full config from this bundle, run:

```bash
php bin/console config:dump symfonycasts_tailwind
```

The main option is `input_css` option, which defaults to `assets/styles/app.css`.
This represents the "source" Tailwind file (the one that contains the `@tailwind`
directives):

```yml
# config/packages/symfonycasts_tailwind.yaml
symfonycasts_tailwind:
    input_css: 'assets/styles/other.css'
```

### Using a Different Binary

The standalone Tailwind binary comes with the first-party plugins. However,
if you want to add extra plugins, you may choose to install Tailwind via
npm instead:

```yml
npm add tailwindcss
```

To instruct the bundle to use that binary instead, set the `binary` option:

```yml
# config/packages/symfonycasts_tailwind.yaml
symfonycasts_tailwind:
    binary: 'node_modules/.bin/tailwindcss'
```
