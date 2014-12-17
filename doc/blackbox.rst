Blackbox Driver
===============

The blackbox driver assumes no privileged access to the site. You can run the
tests on a local or remote server, and all the actions will take place through
the site's user interface. This driver was enabled as part of the installation
instructions by lines 13 and 14, highlighted below.

.. literalinclude:: _static/snippets/behat-bb.yml
   :language: yaml
   :linenos:
   :lines: 1-14
   :emphasize-lines: 13-14

Region steps
------------

It may be really important that a block is in the correct region, or you may
have a link or button that doesn't have a unique label. The blackbox driver
allows you to create a map between a CSS selector and a user-readable region
name so you can use steps like the following without having to write any custom
PHP::


  I press "Search" in the "header" region
  I fill in "a value" for "a field" in the "content" region
  I fill in "a field" with "Stuff" in the "header" region
  I click "About us" in the "footer" region

Example:
++++++++

A stock Drupal 7 installation has a footer area identified by the CSS Id
"footer". By editing the behat.yml file and adding lines 15 and 16 below:

.. literalinclude:: _static/snippets/behat-bb.yml
   :language: yaml
   :linenos:
   :emphasize-lines: 15-16

You can use a step like the following without writing any custom PHP::

  When I click "About us" in the "footer" region.


Using the blackbox driver configured with the regions of your site, you can
access the following region-related steps:

.. Note::
    These examples won't work unless you define the appropriate regions in
     your behat.yml file.

.. literalinclude:: _static/snippets/blackbox.feature
   :language: gherkin
   :linenos:
   :lines: 1-61

Message selectors
-----------------

The Drupal Extension makes use of three selectors for message. If your CSS
values are different than the defaults (shown below), you'll need to update
your behat.yml file:

.. code-block:: yaml
   :linenos:
   :emphasize-lines: 2-5

    Drupal\DrupalExtension:
      selectors:
        message_selector: '.messages'
        error_message_selector: '.messages.messages-error'
        success_message_selector: '.messages.messages-status'

Message-related steps include:

.. literalinclude::  _static/snippets/blackbox.feature
   :language: gherkin
   :linenos:
   :lines: 63-81

Override text strings
---------------------

The Drupal Extension relies on default text for certain steps. If you have
customized the label visible to users, you can change that text as follows:

.. code-block:: yaml

     Drupal\DrupalExtension:
       text:
         log_out: "Sign out"
         log_in: "Sign in"
         password_field: "Enter your password"
         username_field: "Nickname"
