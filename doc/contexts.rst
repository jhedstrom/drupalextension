Available Contexts
==================

The Drupal Extension includes the following contexts:

*RawDrupalContext*
  A context that provides no step definitions, but all of the
  necessary functionality for interacting with Drupal, and with the
  browser via Mink sessions.

*DrupalContext*
  Provides step-definitions for creating users, terms, and nodes.

*MinkContext*
  Builds on top of the Mink Extension and adds steps specific to regions and forms.

*MarkupContext*
  Contains step definitions that deal with low-level markup (such as tags, classes, and attributes).

*MessageContext*
  Step-definitions that are specific to Drupal messages that get displayed (notice, warning, and error).

*DrushContext*
  Allows steps to directly call drush commands.
