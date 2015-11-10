Drush Driver
============

Many tests require that a user logs into the site. With the blackbox driver,
all user creation and login would have to take place via the user interface,
which quickly becomes tedious and time consuming. You can use the Drush driver
to add users, reset passwords, and log in by following the steps below, again,
without having to write custom PHP. You can also do this with the Drupal API
driver. The main advantage of the Drush driver is that it can work when your
tests run on a different server than the site being tested.

Install Drush
-------------

See the `Drush project page <https://drupal.org/project/drush>`_ for
installation directions.

Install the Behat Drush Endpoint
--------------------------------

The Behat Drush Endpoint is a Drush-based service that the Drush Driver uses in order to create content on the Drupal site being tested.  See the `Behat Drush Endpoint project page <https://github.com/drush-ops/behat-drush-endpoint>`_ for instructions on how to install it with your Drupal site.

Point Drush at your Drupal site
-------------------------------

Drupal Alias (For local or remote sites)
++++++++++++++++++++++++++++++++++++++++

You'll need ssh-key access to a remote server to use Drush. If Drush and Drush
aliases are new to you, see the `Drush site <http://drush.ws/help>`_ for
`detailed examples <http://drush.ws/examples/example.aliases.drushrc.php>`_

The alias for our example looks like:

.. literalinclude:: _static/snippets/aliases.drushrc.php
   :language: php
   :linenos:

Path to Drupal (local sites only)
+++++++++++++++++++++++++++++++++

If you'll only be running drush commands to access a site on the same machine,
you can specify the path to your Drupal root:

.. code-block:: yaml
   :linenos:

    Drupal\DrupalExtension:
      blackbox: ~
    drush:
      root: /my/path/to/drupal


Enable the Drush driver
-----------------------

In the behat.yml file:

.. literalinclude:: _static/snippets/behat-drush.yml
   :language: yaml
   :linenos:
   :emphasize-lines: 15-17

.. note:: Line 15 isn't strictly necessary for the Drush driver, which is the
          default for the API.

Calling the Drush driver
------------------------

Untagged tests use the blackbox driver. To invoke the Drush driver, tag the
scenario with @api

.. literalinclude:: _static/snippets/apitag.feature
   :language: gherkin
   :linenos:
   :emphasize-lines: 11

If you try to run a test without that tag, it will fail.

Example:
++++++++

.. literalinclude:: _static/snippets/apitag.output
   :language: gherkin
   :linenos:
   :emphasize-lines: 10-12
   :lines: 1-24

The Drush driver gives you access to all the blackbox steps, plus those used in
each of the following examples:

.. literalinclude:: _static/snippets/drush.feature
   :language: gherkin
   :linenos:

If the Behat Drush Endpoint is installed on the Drupal site being tested, then you will also have access to all of the examples shown for the Drupal API driver.
