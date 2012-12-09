DrupalExtension
===============

 A Behat extension providing step definitions for Drupal and related projects.

 [![Build Status](https://travis-ci.org/jhedstrom/drupalextension.png)](https://travis-ci.org/jhedstrom/drupalextension)


Installation
------------

  Get [Composer](http://getcomposer.org/download/) and start a new project.
   
    ./composer.phar init

  When asked for minimum stability, specify `dev`, when asked for dependencies,
  specify `drupal/drupal-extension`.


Usage
-----

  Copy behat.yml.dist into your project and modify for your needs.

  Begin writing [Behat features](http://docs.behat.org/).


API Usage
---------

  Some step definitions will require direct API access in order to manipulate
  Drupal; the `@api` tag will bootstrap the driver specified by the
  `api_driver` parameter in `behat.yml` (or Drush by default).

  The Drush driver requires a working site alias or a local drupal path to be
  specified in your Behat configuration (`behat.yml`):

    Drupal\DrupalExtension\Extension:
      blackbox: ~
      drush:
        alias: myDrushAlias

  or

    Drupal\DrupalExtension\Extension:
      blackbox: ~
      drush:
        root: /my/path/to/drupal

  Alternatively, if Drupal is in the local filesystem, you can use the `drupal`
  driver, which calls Drupal APIs directly, but requires you specify the path
  to Drupal:

    Drupal\DrupalExtension\Extension:
      api_driver: "drupal"
      drupal:
        drupal_root: "path/to/drupal"


Configuration
-------------

  Some steps take advantage of nice names for Drupal page regions. These can
  be specified in a `region_map`:

    Drupal\DrupalExtension\Extension:
      region_map:
        My region: "#css-selector"
        Content: "#main .region-content"
        Right sidebar: "#sidebar-second"


  If your site has changed the text of certain core UI strings, such as "Log
  out" or "Username" you inform DrupalExtension so related steps will continue
  to work:

    Drupal\DrupalExtension\Extension:
      text:
        log_out: "Sign out"
        log_in: "Sign in"
        password_field: "Enter your password"
        username_field: "Nickname"


Step Autodiscovery
------------------

  Drupal module authors may provide additional step definitions using a
  subcontext inside a `<module>.behat.inc` within their project; these will
  automatically discovered and added, making those steps available for use.

  You may disable this behavior in `behat.yml`:

    Drupal\DrupalExtension\Extension:
      subcontexts:
        autoload: 0
  
  Further subcontexts can be loaded by either placing them in the bootstrap
  directory (typically `features/bootstrap`) or by adding them to behat.yml:

    Drupal\DrupalExtension\Extension:
      subcontexts:
        paths:
          - "/path/to/additional/subcontexts"
          - "/another/path"
