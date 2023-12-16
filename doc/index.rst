Tailwind CSS for Symfony!
=========================

This bundle makes it easy to use `Tailwind CSS <https://tailwindcss.com/>`_ with
Symfony's `AssetMapper Component <https://symfony.com/doc/current/frontend/asset_mapper.html>`_
(no Node required!).

- Automatically downloads the correct `standalone Tailwind CSS binary <https://tailwindcss.com/blog/standalone-cli>`_;
- Adds a ``tailwind:build`` command to build & watch for changes;
- Transparently swaps in the compiled CSS.

.. note::

    Want to use Tailwind CSS with WebpackEncore instead? Check out
    the `Tailwind + Symfony Docs <https://tailwindcss.com/docs/guides/symfony>`_.

Installation
------------

Install the bundle & initialize your app with two commands:

.. code-block:: terminal

    $ composer require symfonycasts/tailwind-bundle
    $ php bin/console tailwind:init

Done! This will create a ``tailwind.config.js`` file and make sure your
``assets/styles/app.css`` contains the Tailwind directives.

Usage
-----

To use the Tailwind CSS file, start by including the input file
(``assets/styles/app.css`` by default) in ``base.html.twig``. It's quite likely
you already have this:

.. code-block:: html+twig

    {# templates/base.html.twig #}

    {% block stylesheets %}
        <link rel="stylesheet" href="{{ asset('styles/app.css') }}">
    {% endblock %}

The bundle works by swapping out the contents of ``styles/app.css`` with the
compiled CSS automatically. For this to work, you need to run the ``tailwind:build``
command:

.. code-block:: terminal

    $ php bin/console tailwind:build --watch

That's it! This will watch for changes to your ``assets/styles/app.css`` file
and automatically recompile it when needed. If you refresh the page, the
final ``app.css`` file will already contain the compiled CSS.

How Does It Work?
-----------------

The first time you run one of the Tailwind commands, the bundle will
download the correct Tailwind binary for your system into a ``var/tailwind/``
directory.

When you run ``tailwind:build``, that binary is used to compile
your CSS file into a ``var/tailwind/tailwind.built.css`` file. Finally,
when the contents of ``assets/styles/app.css`` is requested, the bundle
swaps the contents of that file with the contents of ``var/tailwind/tailwind.built.css``.
Nice!

Deploying
---------

When you deploy, run the ``tailwind:build`` command *before* the ``asset-map:compile``
command so the built file is available:

.. code-block:: terminal

    $ php bin/console tailwind:build --minify
    $ php bin/console asset-map:compile

Form Theming
------------

To make your Symfony forms look nice with Tailwind, you'll need a dedicated form theme.
Check out https://github.com/tales-from-a-dev/flowbite-bundle for a helpful bundle that
provides that!

Tailwind Plugins
----------------

The Tailwind binary the bundle downloads already contains the "Official Plugins" - e.g. `typography <https://tailwindcss.com/docs/typography-plugin>`_.
This means you can use those simply by adding the line to the ``plugins`` key in
``tailwind.config.js`` - e.g. ``require('@tailwindcss/typography')``.

For other plugins - like `Flowbite Datepicker <https://flowbite.com/docs/plugins/datepicker/>`_,
you will need to follow that package's documentation to `require the package <https://flowbite.com/docs/getting-started/quickstart/#require-via-npm>`_
with ``npm``:

.. code-block:: terminal

    $ npm install flowbite

Then add it to ``tailwind.config.js``:

.. code-block:: javascript

    module.exports = {
        plugins: [
            require('flowbite/plugin')
        ]
    }

Configuration
-------------

To see the full config from this bundle, run:

.. code-block:: terminal

    $ php bin/console config:dump symfonycasts_tailwind

The main option is ``input_css`` option, which defaults to ``assets/styles/app.css``.
This represents the "source" Tailwind file (the one that contains the ``@tailwind``
directives):

.. code-block:: yaml

    # config/packages/symfonycasts_tailwind.yaml
    symfonycasts_tailwind:
        input_css: 'assets/styles/other.css'

Another option is the ``config_file`` option, which defaults to ``tailwind.config.js``.
This represents the Tailwind configuration file:

.. code-block:: yaml

    # config/packages/symfonycasts_tailwind.yaml
    symfonycasts_tailwind:
        config_file: 'tailwind.config.js'

Using a Different Binary
------------------------

The standalone Tailwind binary comes with the first-party plugins. However,
if you want to add extra plugins, you may choose to install Tailwind via
npm instead:

.. code-block:: terminal

    $ npm add tailwindcss

To instruct the bundle to use that binary instead, set the ``binary`` option:

.. code-block:: yaml

    # config/packages/symfonycasts_tailwind.yaml
    symfonycasts_tailwind:
        binary: 'node_modules/.bin/tailwindcss'

Using a Different Binary Version
------------------------

By default the latest standalone Tailwind binary gets downloaded. However,
if you want to use a different version, you can specify the version to use,
set ``binary_version`` option:

.. code-block:: yaml
    # config/packages/symfonycasts_tailwind.yaml
    symfonycasts_tailwind:
        binary_version: 'v3.3.0'