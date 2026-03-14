<div align="center">
  <a href="" rel="noopener">
  <img height=100px src="doc/_static/beehat.png" alt="Behat Drupal Extension Logo"></a>
</div>

<h1 align="center">Behat Drupal Extension</h1>

<div align="center">

[![Latest Stable Version](https://poser.pugx.org/drupal/drupal-extension/v/stable.svg)](https://packagist.org/packages/drupal/drupal-extension)
[![Total Downloads](https://poser.pugx.org/drupal/drupal-extension/downloads.svg)](https://packagist.org/packages/drupal/drupal-extension)
[![Latest Unstable Version](https://poser.pugx.org/drupal/drupal-extension/v/unstable.svg)](https://packagist.org/packages/drupal/drupal-extension)
[![License](https://poser.pugx.org/drupal/drupal-extension/license.svg)](https://packagist.org/packages/drupal/drupal-extension)

[![ci](https://github.com/jhedstrom/drupalextension/actions/workflows/ci.yml/badge.svg)](https://github.com/jhedstrom/drupalextension/actions/workflows/ci.yml)
[![GitHub Issues](https://img.shields.io/github/issues/jhedstrom/drupalextension.svg)](https://github.com/jhedstrom/drupalextension/issues)
[![GitHub Pull Requests](https://img.shields.io/github/issues-pr/jhedstrom/drupalextension.svg)](https://github.com/jhedstrom/drupalextension/pulls)
[![Documentation Status](https://readthedocs.org/projects/behat-drupal-extension/badge/?version=master)](https://behat-drupal-extension.readthedocs.org)

</div>

The Drupal Extension is an integration layer between [Behat](http://behat.org),
[Mink Extension](https://github.com/Behat/MinkExtension), and [Drupal](https://www.drupal.org/). It
provides step definitions for common testing scenarios specific to Drupal
sites.


## Use it for testing your Drupal site.

If you're new to the Drupal Extension, we recommend starting with
the [Full documentation](https://behat-drupal-extension.readthedocs.org)

### Quick start

1. Install using [Composer](https://getcomposer.org/):

    ```shell
    composer require --dev drupal/drupal-extension
    ```

2. Create a file called `behat.yml` with a minimal configuration.
    For more information on configuration options, see [Full documentation](https://behat-drupal-extension.readthedocs.org)

    ```yaml behat.yml
    default:
      autoload: ['%paths.base%/tests/bootstrap']
      suites:
        default:
          paths: ['%paths.base%/tests/features']
          contexts:
            - Drupal\DrupalExtension\Context\DrupalContext
      extensions:
        Drupal\MinkExtension:
          browserkit_http: ~
          base_url: http://example.org/  # Replace with your site's URL
        Drupal\DrupalExtension:
          blackbox: ~
     ```

3. Initialize Behat in your project:
    ```shell
    vendor/bin/behat --init
    ```

4. Find pre-defined steps to work with using:

    ```shell
    vendor/bin/behat -di
    ```

5. Optionally, define your own steps in `tests/bootstrap/FeatureContext.php`

6. Start adding your [feature files](http://behat.org/en/latest/user_guide/gherkin.html)
   to the `tests/features` directory of your repository.

## Available steps

| Class | Description |
| --- | --- |
| [BatchContext](STEPS.md#batchcontext) | Extensions to the Mink Extension. |
| [ConfigContext](STEPS.md#configcontext) | Provides pre-built step definitions for interacting with Drupal config. |
| [DrupalContext](STEPS.md#drupalcontext) | Provides pre-built step definitions for interacting with Drupal. |
| [DrushContext](STEPS.md#drushcontext) | Provides step definitions for interacting directly with Drush commands. |
| [MailContext](STEPS.md#mailcontext) | Provides pre-built step definitions for interacting with mail. |
| [MarkupContext](STEPS.md#markupcontext) | Extensions to the Mink Extension. |
| [MessageContext](STEPS.md#messagecontext) | Provides step-definitions for interacting with Drupal messages. |
| [MinkContext](STEPS.md#minkcontext) | Extensions to the Mink Extension. |




[//]: # (END)

## Writing tests with AI assistants

Copy and paste below into your project's `CLAUDE.md` or `AGENTS.md` file.

```
## Writing Behat Tests

Available step definitions are listed in `STEPS.md`.
Read this file before writing or modifying Behat tests.
Use only step patterns from this file. Do not invent steps.

If `STEPS.md` does not exist or is outdated, regenerate it:

    vendor/bin/behat -di > STEPS.md

Regenerate after adding new Context classes or updating dependencies.

For detailed step documentation, see: vendor/drupal/drupal-extension/STEPS.md
```

## Credits

 * Originally developed by [Jonathan Hedstrom](https://github.com/jhedstrom) with great help from [eliza411](https://github.com/eliza411)
 * Maintainers
   * [Alex Skrypnyk](https://github.com/AlexSkrypnyk)
   * [Pieter Frenssen](https://github.com/pfrenssen)
   * [Ricardo Sanz](https://github.com/rsanzante)
   * [All these great contributors](https://github.com/jhedstrom/drupalextension/graphs/contributors)

## Additional resources

 * [Behat Drupal Extension documentation](https://behat-drupal-extension.readthedocs.org)
 * [Behat documentation](http://docs.behat.org)
 * [Mink documentation](http://mink.behat.org)
 * [Drupal Behat group](http://groups.drupal.org/behat)

## Examples and code snippets

 * [Complex node creation, with field collections and entity references](https://gist.github.com/jhedstrom/5708233)
 * [Achievements module support](https://gist.github.com/jhedstrom/9633067)
 * [Drupal form element visibility](https://gist.github.com/pbuyle/7698675)
 * [Track down PHP notices](https://www.godel.com.au/blog/use-behat-track-down-php-notices-they-take-over-your-drupal-site-forever)
 * [Support for sites using basic HTTP authentication](https://gist.github.com/jhedstrom/5bc5192d6dacbf8cc459)

## Release notes

See [CHANGELOG](CHANGELOG.md).

## Contributing

Features and bug fixes are welcome!

See [CONTRIBUTING.md](https://github.com/jhedstrom/drupalextension/blob/master/CONTRIBUTING.md) for more information.
