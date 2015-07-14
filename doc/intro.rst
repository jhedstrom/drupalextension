Testing your site with the Drupal Extension to Behat and Mink
==============================================================

.. container:: clear

  .. image:: _static/beehat.png
     :align: left
     :height: 125px

The `Drupal Extension to Behat and Mink
<https://drupal.org/project/drupalextension>`_ provides Drupal-specific
functionality for the `Behavior-Driven Development
<http://dannorth.net/introducing-bdd/>`_ testing frameworks of `Behat and Mink
<http://extensions.behat.org/mink/>`_.

What do Behat and Mink Do?
--------------------------

Behat and Mink allow you to describe the behavior of a web site in plain, but
stylized language, and then turn that description into an automated test that
will visit the site and perform each step you describe. Such functional tests
can help site builders ensure that the added value they've created when
building a Drupal site continues to behave as expected after any sort of site
change -- security updates, new module versions, changes to custom code, etc.

What does the Drupal Extension add?
-----------------------------------

The Drupal Extension to Behat and Mink assists in the performance of these
common Drupal testing tasks:

*  Set up test data with Drush or the Drupal API 
*  Define theme regions and test data appears within them 
*  Clear the cache, log out, and other useful steps
*  Detect and discover steps provided by contributed modules and themes
