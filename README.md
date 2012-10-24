Drupal Extension
====================

The Drupal Extension is an integration layer between [Behat](http://behat.org), [Mink Extension](http://extensions.behat.org/mink/), and Drupal. It provides step definitions for common testing scenarios specific to Drupal sites.

### Using the Drupal Extension for testing your own projects.
1. You'll need something resembling this `composer.json` file

  ```
    {
      "require": {
        "drupal/drupal-extension": "*"
    },
      "minimum-stability": "dev",
      "config": {
        "bin-dir": "bin/"
      }
    }
  ```

1. Then run

  ```
  php composer.phar install
  ```

  To download the required dependencies.

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
1. Start adding your feature files to the `features` directory of your repository.

1. Features that require API access in order to setup the proper testing conditions can be tagged with `@api`. This will bootstrap the driver specified by the `api_driver` parameter (which defaults to the drush driver). When using the drush driver, this must be initialized via the `behat.yml` file.

  ```
  Drupal\DrupalExtension\Extension:
    blackbox: ~
	drush:
	  alias: myDrushAlias
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
