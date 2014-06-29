Drupal Extension
====================

The Drupal Extension is an integration layer between [Behat](http://behat.org), [Mink Extension](http://extensions.behat.org/mink/), and Drupal. It provides step definitions for common testing scenarios specific to Drupal sites.

[![Build Status](https://travis-ci.org/jhedstrom/drupalextension.png?branch=1.0)](https://travis-ci.org/jhedstrom/drupalextension)

Drupal Extension 1.0 supports Behat 2.4, and Drupal 6 and 7 (with Drupal 8 support being backported as it changes). Drupal Extension 2.0 aims to work with Behat 3, and focus on Drupal 8.

### Using the Drupal Extension for testing your own projects.
1. You'll need something resembling this `composer.json` file

  ```
    {
      "require": {
        "drupal/drupal-extension": "1.0.*@stable"
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
    paths:
      features: 'features'
    extensions:
      Behat\MinkExtension\Extension:
        goutte: ~
        selenium2: ~
        base_url: http://git6site.devdrupal.org/
      Drupal\DrupalExtension\Extension:
        blackbox: ~
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

### Additional resources

 * [Behat documentation](http://docs.behat.org)
 * [Mink documentation](http://mink.behat.org)
 * [Drupal Extension documentation](http://dspeak.com/drupalextension)

#### Examples and code snippets

 * [Complex node creation, with field collections and entity references](https://gist.github.com/jhedstrom/5708233)
 * [Achievements module support](https://gist.github.com/jhedstrom/9633067)
 * [Drupal form element visibility](https://gist.github.com/pbuyle/7698675)
