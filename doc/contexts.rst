Contexts
========

Before Behat 3, each test suite was limited to a single context class. As of
Behat 3, it is possible to flexibly structure your code by using multiple
contexts in a single test suite.

Available Contexts
------------------

In accordance with this new capability, The Drupal Extension includes the
following contexts:

*RawDrupalContext*
  A context that provides no step definitions, but all of the
  necessary functionality for interacting with Drupal, and with the
  browser via Mink sessions.

*DrupalContext*
  Provides step-definitions for creating users, terms, and nodes.

*MinkContext*
  Builds on top of the Mink Extension and adds steps specific to regions and
  forms.

*MarkupContext*
  Contains step definitions that deal with low-level markup (such as tags,
  classes, and attributes).

*MessageContext*
  Step-definitions that are specific to Drupal messages that get displayed
  (notice, warning, and error).

*DrushContext*
  Allows steps to directly call drush commands.

Custom Contexts
---------------

You can structure your own code with additional contexts. See Behat's `testing features <http://docs.behat.org/en/latest/guides/4.contexts.html>`_ documentation for a detailed discussion of how contexts work.

.. Important::

   Every context you want to use in a suite must be declared in the behat.yml
   file.

Example
#######

In this example, you would have access to:

 * pre-written step definitions for users, terms, and nodes
   (from the ``DrupalContext``)
 * steps you've implemented in the  main
   ``features/bootstrap/FeatureContext.php`` file
 * steps you've implemented in the ``CustomContext`` class

You would not have access to the steps from the ``MarkupContext``,
``MessageContext``, or ``DrushContext``, however.

.. code-block:: yaml
   :linenos:

    default:
      suites:
        default:
          contexts:
            - Drupal\DrupalExtension\Context\DrupalContext
            - FeatureContext
            - CustomContext

Context communication
---------------------

Since Behat 3 can have many concurrent contexts active, communication between those  contexts can be important.

The following will gather any specified contexts before a given scenario is run:

  .. literalinclude:: _static/snippets/context-communication.inc
     :language: php
     :linenos:

Drupal Extension Hooks
----------------------

In addition to the `hooks provided by Behat
<http://behat.readthedocs.org/en/v2.5/guides/3.hooks.html>`_, the Drupal
Extension provides three additional ways to tag the methods in your
``CustomContext`` class in order to have them fire before certain events.

  1. ``@beforeNodeCreate``
  2. ``@beforeTermCreate``
  3. ``@beforeUserCreate``

Example
#######

.. code-block:: php
   :linenos:

     use Drupal\DrupalExtension\Hook\Scope\EntityScope;
      ...
      /**
       * Call this function before nodes are created.
       *
       * @beforeNodeCreate
       */
       public function alterNodeObject(EntityScope $scope) {
         $node = $scope->getEntity();
         // Alter node object as needed.
       }

