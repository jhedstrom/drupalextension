# Behat Drupal Extension

The Drupal Extension is an integration layer between [Behat](http://behat.org),
[Mink Extension](https://github.com/Behat/MinkExtension), and Drupal. It
provides step definitions for common testing scenarios specific to Drupal
sites.

[![Build Status](https://travis-ci.org/jhedstrom/drupalextension.png?branch=master)](https://travis-ci.org/jhedstrom/drupalextension)

The Drupal Extension 4.x supports Drupal 6, 7 and 8, utilizes Behat 3.2+ and
runs on PHP 5.5+. It is compatible with Symfony components 2.x as well as 3.x
so it can be used on Drupal 8.4.x.

[![Latest Stable Version](https://poser.pugx.org/drupal/drupal-extension/v/stable.svg)](https://packagist.org/packages/drupal/drupal-extension)
[![Total Downloads](https://poser.pugx.org/drupal/drupal-extension/downloads.svg)](https://packagist.org/packages/drupal/drupal-extension)
[![Latest Unstable Version](https://poser.pugx.org/drupal/drupal-extension/v/unstable.svg)](https://packagist.org/packages/drupal/drupal-extension)
[![License](https://poser.pugx.org/drupal/drupal-extension/license.svg)](https://packagist.org/packages/drupal/drupal-extension)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/jhedstrom/drupalextension/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/jhedstrom/drupalextension/?branch=master)



## Use it for testing your Drupal site.

If you're new to the Drupal Extension, we recommend starting with 
the [Full documentation](https://behat-drupal-extension.readthedocs.org)

[![Documentation Status](https://readthedocs.org/projects/behat-drupal-extension/badge/?version=master)](https://behat-drupal-extension.readthedocs.org)

### Quick start

1. Install using [Composer](https://getcomposer.org/):

    ``` bash
    mkdir projectdir
    cd projectdir
    curl -sS https://getcomposer.org/installer | php
    COMPOSER_BIN_DIR=bin php composer.phar require drupal/drupal-extension='~3.0'
    ```

1.  In the projectdir, create a file called `behat.yml`. Below is the
    minimal configuration. Many more options are covered in the 
    [Full documentation](https://behat-drupal-extension.readthedocs.org)  

  ``` yaml
  default:
    suites:
      default:
        contexts:
          - Drupal\DrupalExtension\Context\DrupalContext
    extensions:
      Behat\MinkExtension:
        goutte: ~
        base_url: http://example.org/  # Replace with your site's URL
      Drupal\DrupalExtension:
        blackbox: ~
  ```

1. In the projectdir, run

    ``` bash
    bin/behat --init
    ```

1. Find pre-defined steps to work with using:

    ```bash
    bin/behat -di
    ```

1. Define your own steps in `projectdir\features\FeatureContext.php`

1. Start adding your [feature files](http://behat.org/en/latest/user_guide/gherkin.html) 
   to the `features` directory of your repository.

## Credits

 * Originally developed by [Jonathan Hedstrom](https://github.com/jhedstrom) with great help from [eliza411](https://github.com/eliza411)
 * Maintainers
   * [Pieter Frenssen](https://github.com/pfrenssen)
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

### Backwards incompatible changes

Starting with 3.3.0 Behat Drupal Extension depends on Behat 3.2.0 which
requires all callbacks to be defined as static methods.

Before 3.3.0:

```
/**
 * @afterUserCreate
 */
public function afterUserCreate(EntityScope $scope) {
  // ...
}
```

Starting with 3.3.0:

```
/**
 * @afterUserCreate
 */
public static function afterUserCreate(EntityScope $scope) {
  // ...
}
```
