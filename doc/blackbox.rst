Blackbox Driver
===============

The blackbox driver assumes no privileged access to the site. You can run the
tests on a local or remote server, and all the actions will take place through
the site's user interface. This driver was enabled as part of the installation
instructions by lines 9 and 10, highlighted below. 

.. literalinclude:: _static/snippets/behat-bb.yml
   :language: yaml
   :linenos:
   :emphasize-lines: 9-10

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
--------

A stock Drupal 7 installation has a footer area identified by the CSS Id
"footer". By editing the behat.yml file and adding lines 11 and 12 below:

.. literalinclude:: _static/snippets/behat-bb.yml
   :language: yaml
   :linenos:
   :emphasize-lines: 11-12 

You can use a step like the following without writing any custom PHP::

  When I click "About us" in the "footer" region. 


Using the blackbox driver, you can use all of the steps in the examples below.

.. literalinclude:: _static/snippets/blackbox.feature
   :language: gherkin
   :linenos:
