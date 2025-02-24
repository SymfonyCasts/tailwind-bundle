Tailwind CSS for Symfony!
=========================

This bundle makes it easy to use `Tailwind CSS <https://tailwindcss.com/>`_ with
Symfony's `AssetMapper Component <https://symfony.com/doc/current/frontend/asset_mapper.html>`_
(no Node.js required!).

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

.. note::

    If using Tailwind CSS v4+, ``tailwind.config.js`` is not created or used.

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

The bundle works by automatically swapping out the contents of ``styles/app.css``
with the compiled CSS. For this to work, you need to run the ``tailwind:build``
command:

.. code-block:: terminal

    $ php bin/console tailwind:build --watch

That's it! This will watch for changes to your ``assets/styles/app.css`` file
and automatically recompile it when needed. If you refresh the page, the
final ``app.css`` file will already contain the compiled CSS.

Watch mode in Docker with Windows host
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

If you work on Windows and your app is running in a Docker container, and you
are having trouble with the ``--watch`` option, you can try running the ``tailwind:build``
command with ``--poll`` option.

.. code-block:: terminal

    $ php bin/console tailwind:build --watch --poll

.. caution::

    The ``--poll`` option is not available in Tailwind CSS v4+.

Symfony CLI
~~~~~~~~~~~

If using the `Symfony CLI <https://symfony.com/download>`_, you can add build
command as a `worker <https://symfony.com/doc/current/setup/symfony_server.html#configuring-workers>`_
to be started whenever you run ``symfony server:start``:

.. code-block:: yaml

    # .symfony.local.yaml
    workers:
        # ...

        tailwind:
            cmd: ['symfony', 'console', 'tailwind:build', '--watch']

.. tip::

    If running ``symfony server:start`` as a daemon, you can run
    ``symfony server:log`` to tail the output of the worker.

How Does It Work?
-----------------

The first time you run one of the Tailwind commands, the bundle will
download the correct Tailwind binary for your system into a ``var/tailwind/``
directory.

When you run ``tailwind:build``, that binary is used to compile
each CSS file into a ``var/tailwind/<filename>.built.css`` file.
Finally, when the contents of the CSS file is requested, the bundle swaps the
contents of that file with the contents of ``var/tailwind/<filename>.built.css``.

E.g.: A request for ``assets/styles/app.css`` will be replaced by ``var/tailwind/app.built.css``.
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

The Tailwind binary that the bundle downloads already contains the "Official Plugins" - e.g. `typography <https://tailwindcss.com/docs/typography-plugin>`_.
This means you can use those simply by adding the line to the ``plugins`` key in
``tailwind.config.js`` - e.g. ``require('@tailwindcss/typography')``.

.. note

    In Tailwind CSS v4 you include plugins with the ``@plugin`` directive in your
    input CSS file - e.g. ``@plugin "@tailwindcss/typography";``.

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
This represents the "source" Tailwind files (the one that contains the ``@tailwind``
directives):

.. code-block:: yaml

    # config/packages/symfonycasts_tailwind.yaml
    symfonycasts_tailwind:
        input_css: 'assets/styles/other.css'

It's possible to use multiple input files by providing an array:
.. code-block:: yaml

        # config/packages/symfonycasts_tailwind.yaml
        symfonycasts_tailwind:
            input_css:
                - 'assets/styles/other.css'
                - 'assets/styles/another.css'

Another option is the ``config_file`` option, which defaults to ``tailwind.config.js``.
This represents the Tailwind configuration file:

.. caution::

    The ``config_file`` is ignored in Tailwind CSS v4+.

.. code-block:: yaml

    # config/packages/symfonycasts_tailwind.yaml
    symfonycasts_tailwind:
        config_file: 'tailwind.config.js'

If you include any other files containing CSS classes that are located outside of
the default directories, for example, in the ``vendor/`` directory like the
`Tailwind CSS form theme <https://symfony.com/doc/current/form/tailwindcss.html>`_
 from the ``symfony/twig-bridge`` package, then after changing your Twig configuration:

.. code-block:: yaml

    # config/packages/twig.yaml
    twig:
        form_themes:
            - 'tailwind_2_layout.html.twig'

You will have to add them to your ``tailwind.config.js`` file:

.. code-block:: diff

      # tailwind.config.js
      module.exports = {
          content: [
              "./assets/**/*.js",
              "./templates/**/*.html.twig",
    +         "./vendor/symfony/twig-bridge/Resources/views/Form/*.html.twig",
          ],
      }


Using a Different Binary
------------------------

The standalone Tailwind binary comes with first-party plugins. However,
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
--------------------------------

To use a different version, adjust the ``binary_version`` option:

.. code-block:: yaml

    # config/packages/symfonycasts_tailwind.yaml
    symfonycasts_tailwind:
        binary_version: 'v3.3.0'

Using a PostCSS config file
---------------------------

.. caution::

    PostCSS config is not available in Tailwind CSS v4+.

If you want to use additional PostCSS plugins, you can specify the
PostCSS config file to use, set ``postcss_config_file`` option or
pass the ``--postcss`` option to the ``tailwind:build`` command.

.. code-block:: yaml

    # config/packages/symfonycasts_tailwind.yaml
    symfonycasts_tailwind:
        postcss_config_file: 'postcss.config.js'


.. code-block:: terminal

    $ php bin/console tailwind:build --postcss='postcss.config.js'
