# Drupal Extension

The Drupal Extension is an integration layer between [Behat](http://behat.org), [Mink Extension](https://github.com/Behat/MinkExtension), and Drupal. It provides step definitions for common testing scenarios specific to Drupal sites.

[![Build Status](https://travis-ci.org/jhedstrom/drupalextension.png?branch=master)](https://travis-ci.org/jhedstrom/drupalextension)

The Drupal Extension 3.0 supports Drupal 7 and 8, and utilized Behat 3. For Drupal 6 support (or Behat 2), use the 1.0 version.

[![Latest Stable Version](https://poser.pugx.org/drupal/drupal-extension/v/stable.svg)](https://packagist.org/packages/drupal/drupal-extension) [![Total Downloads](https://poser.pugx.org/drupal/drupal-extension/downloads.svg)](https://packagist.org/packages/drupal/drupal-extension) [![Latest Unstable Version](https://poser.pugx.org/drupal/drupal-extension/v/unstable.svg)](https://packagist.org/packages/drupal/drupal-extension) [![License](https://poser.pugx.org/drupal/drupal-extension/license.svg)](https://packagist.org/packages/drupal/drupal-extension)

## Use it for testing your Drupal site.

1. You'll need something resembling this `composer.json` file

  ```
    {
      "require": {
        "drupal/drupal-extension": "~3.0"
      },
      "config": {
        "bin-dir": "bin/"
      }
    }
  ```

1. Then run

  ```
  php composer.phar install
  ```

  To download the required dependencies. If composer isn't installed

  ```
  curl -s https://getcomposer.org/installer | php
  ```

1. At a minimum, your `behat.yml` file will look like this

  ```
  default:
    suites:
      default:
        contexts:
          - Drupal\DrupalExtension\ContextDrupalContext
    extensions:
      Behat\MinkExtension:
        goutte: ~
        selenium2: ~
        base_url: http://git6site.devdrupal.org/
      Drupal\DrupalExtension:
        blackbox: ~
  ```

1. To add in support for additional web-based step definitions add the `WebHelperContext`:
  ``` yaml
  contexts:
    - Drupal\DrupalExtension\ContextDrupalContext
    - Drupal\DrupalExtension\WebHelperContext
  ```

1. To see a list of available step definitions

  ```
  bin/behat -dl
  ```

  If the step definitions aren't listed, try running this command:

  ```
  bin/behat --init
  ```

1. Start adding your feature files to the `features` directory of your repository.

1. Features that require API access in order to setup the proper testing conditions can be tagged with `@api`. This will bootstrap the driver specified by the `api_driver` parameter (which defaults to the drush driver). When using the drush driver, this must be initialized via the `behat.yml` file.

  ```
    Drupal\DrupalExtension\Extension:
      blackbox: ~
      # Set the drush alias to "@self" by default, when executing tests from within the drupal installation.
      drush:
        alias: self
  ```

  Alternatively, the root path to the Drupal installation may be specified.

  ```
    Drupal\DrupalExtension\Extension:
      blackbox: ~
	  drush:
	    root: /my/path/to/drupal
  ```
  If you want to use native API calls instead of drush API you should configure your behat.yml as follows:

  ```
  Drupal\DrupalExtension\Extension:
    api_driver: "drupal"
    drupal:
      drupal_root: "/absolute/path/to/drupal"
  ```

1. Targeting content in specific regions can be accomplished once those regions have been defined.

  ```
    Drupal\DrupalExtension\Extension:
      region_map:
	    My region: "#css-selector"
	    Content: "#main .region-content"
	    Right sidebar: "#sidebar-second"
  ```

1. The drupal extension makes use of three selectors by default for messages:

  ```
    Drupal\DrupalExtension\Extension:
      selectors:
        message_selector: '.messages'
        error_message_selector: '.messages.messages-error'
        success_message_selector: '.messages.messages-status'
  ```

1. Text strings, such as *Log out* or the *Username* field can be altered via `behat.yml` if they vary from the default values.

   ```
   Drupal\DrupalExtension\Extension:
     text:
	   log_out: "Sign out"
	   log_in: "Sign in"
	   password_field: "Enter your password"
	   username_field: "Nickname"
   ```

1. The Drupal Extension is capable of discovering additional step-definitions provided by subcontexts. Module authors can provide these in files following the naming convention of `foo.behat.inc`. Once that module is enabled, the Drupal Extension will load these.

  Additional subcontexts can be loaded by either placing them in the bootstrap directory (typically `features/bootstrap`) or by adding them to `behat.yml`.

  ```
    Drupal\DrupalExtension\Extension:
      subcontexts:
	    paths:
	      - "/path/to/additional/subcontexts"
		  - "/another/path"
  ```

  To disable automatic loading of subcontexts:

  ```
    Drupal\DrupalExtension\Extension:
      subcontexts:
	    autoload: 0
  ```

1. The file: `features/bootstrap/FeatureContext.php` is for testing the Drupal Extension itself, and should not be used as a starting point for a feature context. A feature context that extends the Drupal Extension would look like this:

  ```
  use Drupal\DrupalExtension\Context\DrupalContext;
  
  class FeatureContext extends DrupalContext {
    ...
  }
  ```

1. Methods in your `FeatureContext` class can be tagged to fire before certain events:

  ```php
  use Drupal\DrupalExtension\Event\EntityEvent;
  
  ...
  
  /**
   * Call this function before nodes are created.
   *
   * @beforeNodeCreate
   */
   public function alterNodeObject(EntityEvent $event) {
     $node = $event->getEntity();
     // Alter node object as needed.
   }
   ```

   Other available tags include `@beforeTermCreate` and `@beforeUserCreate`

## Additional resources

 * [Behat documentation](http://docs.behat.org)
 * [Mink documentation](http://mink.behat.org)
 * [Drupal Extension documentation](http://behat-drupal-extension.readthedocs.org/en/latest/)

## Examples and code snippets

 * [Complex node creation, with field collections and entity references](https://gist.github.com/jhedstrom/5708233)
 * [Achievements module support](https://gist.github.com/jhedstrom/9633067)
 * [Drupal form element visibility](https://gist.github.com/pbuyle/7698675)
 * [Track down PHP notices](https://www.godel.com.au/blog/use-behat-track-down-php-notices-they-take-over-your-drupal-site-forever)
 * [Support for sites using basic HTTP authentication](https://gist.github.com/jhedstrom/5bc5192d6dacbf8cc459)
