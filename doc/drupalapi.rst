Drupal API Driver
=================

The Drupal API Driver is the fastest and the most powerful of the three
drivers. Its biggest limitation is that the tests must run on the same server
as the Drupal site.

Enable the Drupal API Driver
----------------------------
To enable the Drupal API driver, edit the behat.yml file, change the api_driver
to drupal and add the path to the local Drupal installation as shown below:

.. literalinclude:: _static/snippets/behat-api.yml
   :language: php
   :linenos:
   :emphasize-lines: 15,18-19

.. note:: 
   It's fine to leave the information for the drush driver in the file. It's 
   the api_driver value that declares which setting will be used for scenarios 
   tagged @api.

Using this driver, you gain the ability to use all the steps in the 
examples below (and more).

.. literalinclude::  _static/snippets/api.feature
   :language: gherkin
   :linenos:
   :emphasize-lines: 1
