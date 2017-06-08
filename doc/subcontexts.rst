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
   :emphasize-lines: 8-9,25-26

The Drupal Extension will search recursively within the directory or
directories specified to discover and load any file ending in `.behat.inc`. This
system, although created with Drupal contrib projects in mind, searches where
it's pointed, so you can also use it for your own subcontexts, a strategy you
might employ to re-use step definitions particular to your shop or company's
development patterns. The `paths` key allows running tests located in features
within the `features` directory of a contributed/custom module.

Disable autoloading
-------------------
Autoloading can be disabled in the behat.yml file temporarily with the
following:

.. literalinclude:: _static/snippets/behat-auto.yml
   :language: yaml
   :linenos:
   :emphasize-lines: 25 

For Contributors
----------------
Behat `subcontexts
<http://docs.behat.org/guides/4.context.html#using-subcontexts>`_ are no longer
supported in version 3. The Drupal Extension, however, continues to support
saving module-specific contexts in a file ending with `.behat.inc` 

Just like functions, preface the filename with the project's machine name to prevent namespace collisions.

  .. literalinclude:: _static/snippets/subcontext.inc
     :language: php
     :linenos:
