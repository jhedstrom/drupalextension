Environment specific settings
=============================

Some of the settings in ``behat.yml`` are environment specific. For example the
base URL may be ``http://mysite.localhost`` on your local development
environment, while on a test server it might be ``http://127.0.0.1:8080``. Some
other environment specific settings are the Drupal root path and the paths to
search for subcontexts.

If you intend to run your tests on different environments these settings should
not be committed to ``behat.yml``. Instead they should be exported in an
environment variable. Before running tests Behat will check the ``BEHAT_PARAMS``
environment variable and add these settings to the ones that are present in
``behat.yml``. This variable should contain a JSON object with your settings. 

Example JSON object:

.. code-block:: json

    {
        "extensions": {
            "Drupal\\MinkExtension": {
                "base_url": "http://myproject.localhost"
            },
            "Drupal\\DrupalExtension": {
                "drupal": {
                    "drupal_root": "/var/www/myproject"
                }
            }
        }
    }


To export this into the ``BEHAT_PARAMS`` environment variable, squash the JSON
object into a single line and surround with single quotes:

.. code-block:: bash

    $ export BEHAT_PARAMS='{"extensions":{"Drupal\\MinkExtension":{"base_url":"http://myproject.localhost"},"Drupal\\DrupalExtension":{"drupal":{"drupal_root":"/var/www/myproject"}}}}'

You must also remove (or comment out) the entries that you use in behat.yml for the values in BEHAT_PARAMS to take affect.

.. code-block:: yml

    default:
      suites:
        default:
          contexts:
            - FeatureContext
            - Drupal\DrupalExtension\Context\DrupalContext
            - Drupal\DrupalExtension\Context\MinkContext
            - Drupal\DrupalExtension\Context\MessageContext
            - Drupal\DrupalExtension\Context\DrushContext
      extensions:
        Drupal\MinkExtension:
          goutte: ~
          selenium2: ~
    # Must comment out for BEHAT_PARAMS to be effective.
    #      base_url: http://seven.l
        Drupal\DrupalExtension:
          # Anything used in BEHAT_PARAMS must be removed or commented.
          # drupal:
            # drupal_root: /var/www
          # drush:
            # alias: '@site'
          blackbox: ~

    # You can use profiles if you wish to allow users to run tests locally.
    # Usage: 
    #   bin/behat --profile=local
    local:
      extensions:
        Drupal\MinkExtension:
        base_url: 'localhost'
        Drupal\DrupalExtension:
          drush:
            alias: '@self'
          drupal:
            drupal_root: '../web'



There is also a `Drush extension <https://github.com/pfrenssen/drush-bde-env>`_
that can help you generate these environment variables.
