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

This will ask you a few questions about your setup, and get things ready to go.

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
This means you can use those simply by adding the ``@plugin`` directive to your
input CSS file:

.. code-block:: css

    /* assets/styles/app.css */
    @import "tailwindcss";
    @plugin "@tailwindcss/typography";

.. note::

    In Tailwind CSS v3, add the plugin to the ``plugins`` key in ``tailwind.config.js``
    instead - e.g. ``require('@tailwindcss/typography')``.

For other plugins - like `Flowbite Datepicker <https://flowbite.com/docs/plugins/datepicker/>`_,
you will need to follow that package's documentation to `require the package <https://flowbite.com/docs/getting-started/quickstart/#require-via-npm>`_
with ``npm``:

.. code-block:: terminal

    $ npm install flowbite

Then load the plugin in your input CSS file and tell Tailwind to scan the package's
files for classes (paths are relative to the CSS file):

.. code-block:: css

    /* assets/styles/app.css */
    @import "tailwindcss";
    @plugin "flowbite/plugin";
    @source "../../node_modules/flowbite";

In Tailwind CSS v3, add it to ``tailwind.config.js`` instead:

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
This represents the "source" Tailwind files (the one that contains the
``@import "tailwindcss";`` line, or the ``@tailwind`` directives in Tailwind CSS v3):

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

The ``tailwind:build`` command builds all of them (and with ``--watch``, watches
all of them). To build only one of them, pass its path as an argument:

.. code-block:: terminal

    $ php bin/console tailwind:build assets/styles/other.css

Another option is the ``config_file`` option, which defaults to ``tailwind.config.js``.
This represents the Tailwind configuration file:

.. code-block:: yaml

    # config/packages/symfonycasts_tailwind.yaml
    symfonycasts_tailwind:
        config_file: 'tailwind.config.js'

.. caution::

    The ``config_file`` is ignored in Tailwind CSS v4+: the configuration lives
    in your input CSS file instead (see `Customizing your theme <https://tailwindcss.com/docs/theme>`_).

Tailwind CSS v4 automatically scans your project for files containing CSS classes,
but it skips anything ignored by ``.gitignore`` - like the ``vendor/`` directory.
If you use classes from files there, for example the
`Tailwind CSS form theme <https://symfony.com/doc/current/form/tailwindcss.html>`_
from the ``symfony/twig-bridge`` package, then after changing your Twig configuration:

.. code-block:: yaml

    # config/packages/twig.yaml
    twig:
        form_themes:
            - 'tailwind_2_layout.html.twig'

You will have to register them with the ``@source`` directive in your input CSS
file (the path is relative to the CSS file):

.. code-block:: diff

      /* assets/styles/app.css */
      @import "tailwindcss";
    + @source "../../vendor/symfony/twig-bridge/Resources/views/Form/*.html.twig";

In Tailwind CSS v3, add them to the ``content`` key of your ``tailwind.config.js``
file instead:

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

    $ npm add tailwindcss @tailwindcss/cli

.. note::

    In Tailwind CSS v3, the CLI is part of the ``tailwindcss`` package itself,
    so ``npm add tailwindcss@3`` is enough.

To instruct the bundle to use that binary instead, set the ``binary`` option:

.. code-block:: yaml

    # config/packages/symfonycasts_tailwind.yaml
    symfonycasts_tailwind:
        binary: 'node_modules/.bin/tailwindcss'

Using a Different Binary Version
--------------------------------

To use a different version, adjust the ``binary_version`` option. It must be an
*exact* version - e.g. ``v4.1.11``, not just ``v4``:

.. code-block:: yaml

    # config/packages/symfonycasts_tailwind.yaml
    symfonycasts_tailwind:
        binary_version: 'v4.1.11'

Then clear the cache so the new version is picked up, and restart ``tailwind:build``.
The new binary is downloaded automatically the next time it runs:

.. code-block:: terminal

    $ php bin/console cache:clear

Updating Tailwind
-----------------

To update to the latest Tailwind CSS release *within your current major version*,
run the ``tailwind:update`` command:

.. code-block:: terminal

    $ php bin/console tailwind:update

This finds the latest release matching your current major version (e.g. if you're
on ``v3.3.0`` it will look for the latest ``3.x`` release) and updates the
``binary_version`` option in ``config/packages/symfonycasts_tailwind.yaml`` for you.
Clear the cache afterwards (``php bin/console cache:clear``) - the next time you run
``tailwind:build``, the new binary is downloaded automatically.

.. note::

    If a ``tailwind:build --watch`` process (or worker) is already running, restart
    it after updating - it keeps using the binary it started with until then.

.. note::

    To move to a *new* major version (e.g. from ``3.x`` to ``4.x``), set the
    ``binary_version`` option manually - see `Upgrading from Tailwind CSS v3 to v4`_.

.. note::

    If you're managing your own binary (the ``binary`` option is set), this
    command can't update it for you - update it manually instead.

Upgrading from Tailwind CSS v3 to v4
------------------------------------

Tailwind CSS v4 moved its configuration from ``tailwind.config.js`` into your
CSS file. To upgrade an existing v3 setup:

1. Set the ``binary_version`` option to a v4 release:

   .. code-block:: yaml

       # config/packages/symfonycasts_tailwind.yaml
       symfonycasts_tailwind:
           binary_version: 'v4.1.11'

2. Replace the ``@tailwind`` directives in your input CSS file with a single import:

   .. code-block:: diff

         /* assets/styles/app.css */
       - @tailwind base;
       - @tailwind components;
       - @tailwind utilities;
       + @import "tailwindcss";

3. Move any customizations from ``tailwind.config.js`` into your input CSS file
   (``theme.extend`` becomes ``@theme``, ``plugins`` become ``@plugin``, extra
   ``content`` paths become ``@source`` - see `Configuration`_), then delete
   ``tailwind.config.js``. The ``content`` paths for ``assets/`` and ``templates/``
   aren't needed anymore: Tailwind CSS v4 finds them automatically. See the
   `Tailwind CSS upgrade guide <https://tailwindcss.com/docs/upgrade-guide>`_ for
   all the details.

4. Remove the ``config_file`` and ``postcss_config_file`` options from your bundle
   configuration if you set them - they aren't supported in Tailwind CSS v4.

5. Clear the cache so the new version is picked up, then restart ``tailwind:build``
   (the v4 binary is downloaded automatically):

   .. code-block:: terminal

       $ php bin/console cache:clear
       $ php bin/console tailwind:build --watch

Using a Different Binary Platform
---------------------------------

When downloading Tailwind, the bundle detects your platform and downloads
the appropriate binary. This detection sometimes gets it wrong, so you can
override it by setting the ``binary_platform`` option.

.. code-block:: yaml

    # config/packages/symfonycasts_tailwind.yaml
    symfonycasts_tailwind:
        # one of: 'linux-arm64', 'linux-arm64-musl', 'linux-x64', 'linux-x64-musl', 'macos-arm64', 'macos-x64', 'windows-x64'
        binary_platform: 'linux-x64'

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

Configuring the Build Timeout
------------------------------

By default, the build process will timeout after 60 seconds. For large projects
where the build takes longer, you can increase this limit via the ``process_timeout``
option:

.. code-block:: yaml

    # config/packages/symfonycasts_tailwind.yaml
    symfonycasts_tailwind:
        process_timeout: 120

To disable the timeout entirely, set it to ``0``:

.. code-block:: yaml

    # config/packages/symfonycasts_tailwind.yaml
    symfonycasts_tailwind:
        process_timeout: 0
