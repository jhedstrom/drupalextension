System-wide installation 
========================

A system-wide installation allows you to maintain a single copy of the testing
tool set and use it for multiple test environments. Configuration is slightly
more complex than the stand-alone installation but many people prefer the
flexibility and ease-of-maintenance this setup provides.

Overview 
--------

To install the Drupal Extension globally:

#. Install Composer 
#. Install the Drupal Extension in `/opt/drupalextension` 
#. Create an alias to the behat binary in `/usr/local/bin` 
#. Create your test folder

Install Composer 
----------------

Composer is a PHP dependency manager that will make sure all the pieces you
need get installed. `Full directions for global installation
<http://getcomposer.org/doc/00-intro.md#globally>`_ and more information can be
found on the `Composer website <http://getcomposer.org/>`_.::

  curl -sS https://getcomposer.org/installer | 
  php mv composer.phar /usr/local/bin/composer

Install the Drupal Extension 
----------------------------

1. Run global require command::

    $ composer global require drupal/drupal-extension='~3.0'

  It will be a bit before you start seeing any output. It will also suggest
  that you install additional tools, but they're not normally needed so you can
  safely ignore that message.

2. Test that your install worked by typing the following::

    $ behat --help

  If you were successful, you'll see the help output.

Set up tests 
------------ 

1. Create the directory that will hold your tests. There is no technical
   reason this needs to be inside the Drupal directory at all. It is best to
   keep them in the same version control repository so that the tests match the 
   version of the site they are written for.

  One clear pattern is to keep them in the sites folder as follows:

  Single site: `sites/default/behat-tests`
  
  Multi-site or named single site: `/sites/my.domain.com/behat-tests`

2. Wherever you make your test folder, inside it create the behat.yml file:

  .. literalinclude:: _static/snippets/behat-1.yml 
     :language: yaml 
     :linenos:

3. Initialize behat. This creates the features folder with some basic things to
   get you started::

    bin/behat --init

4. This will generate a FeatureContext.php file that looks like:

  .. literalinclude:: _static/snippets/FeatureContext.php.inc
     :language: php 
     :linenos: 
     :emphasize-lines: 12 

  This will make your FeatureContext.php aware of both the Drupal Extension and
  the Mink Extension, so you'll be able to take advantage of their drivers and
  step definitions and add your own custom step definitions here. 
  The FeatureContext.php file must be in the same directory as your behat.yml
  file otherwise in step 5 you will get the following error:
  
    [Behat\Behat\Context\Exception\ContextNotFoundException]
    `FeatureContext` context class not found and can not be used. 
  

5. To ensure everything is set up appropriately, type::

    behat -dl

   You'll see a list of steps like the following, but longer, if you've
   installed everything successfully:


  .. code-block:: gherkin 
     :linenos:

      default | Given I am an anonymous user                                    
      default | Given I am not logged in                                        
      default | Given I am logged in as a user with the :role role(s)           
      default | Given I am logged in as :name     
