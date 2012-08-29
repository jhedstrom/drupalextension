Drupal Extension
====================

The Drupal Extension is an integration layer between [Behat](http://behat.org), [Mink Extension](http://extensions.behat.org/mink/), and Drupal. It provides step definitions for common testing scenarios specific to Drupal sites.

### Getting started
1. Get [composer](http://getcomposer.org)

  ```
  curl -s https://getcomposer.org/installer | php
  ```

1. Download the dependencies

  ```
  php composer.phar install
  ```

1. Run the sample tests!
  ```
  ./bin/behat
  ```
  
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
  ```

1. To see a list of available step definitions

  ```
  bin/behat -dl
  ```
1. Start adding your feature files to the `features` directory of your repository.
