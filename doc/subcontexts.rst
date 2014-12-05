Contributed Module Subcontexts
==============================

Although not yet a wide-spread practice, the Drupal Extension to Behat and Mink
makes it easy for maintainers to include custom step definitions in their
contributed projects.


Discovering SubContexts
-----------------------

In order to use contributed step definitions, define the search path in the
behat.yml

// sites/default/behat-tests/behat.yml

.. literalinclude:: _static/snippets/behat-sub.yml
   :language: yaml
   :linenos:
   :emphasize-lines: 18-20

The Drupal Extension will search recursively within the directory or
directories specified to discover and load any file ending in `.behat.inc`. This
system, although created with Drupal contrib projects in mind, searches where
it's pointed, so you can also use it for your own subcontexts, a strategy you
might employ to re-use step definitions particular to your shop or company's
development patterns.

Disable autoloading
-------------------
Autoloading can be disabled in the behat.yml file temporarily with the
following:

.. literalinclude:: _static/snippets/behat-auto.yml
   :language: yaml
   :linenos:
   :emphasize-lines: 21 

For Contributors
----------------
Read a detailed discussion of `using sucontexts
<http://docs.behat.org/guides/4.context.html#using-subcontexts>`_ on the Behat
site.

With regard to the Drupal Extension:

* Save custom step definitions in a file ending with `.behat.inc` Just like
  functions, preface the filename with the project's machine name to prevent
  namespace collisions.

* Writing step definitions for the subcontext is only slightly different than
  writing them for the main context. 

Your subcontext must include, at a minimum, lines 7 and 8 below. 


// sites/all/modules/beanslide/beanslide.behat.inc

.. literalinclude:: _static/snippets/beanslide.behat-2.inc
   :language: php
   :linenos:
   :emphasize-lines: 7,8,10-12

You'll also need to call the main context in order to access the session:

// sites/all/modules/beanslide/beanslide.behat.inc

.. literalinclude:: _static/snippets/beanslide.behat-1.inc
   :language: php
   :linenos:
   :emphasize-lines: 5
   
