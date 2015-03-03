System Requirements 
===================

Meet the system requirements
----------------------------

#. Check your PHP version::

    php --version

   It must be higher than 5.3.5! Note: This means you cannot use the same
   version of PHP for testing that you might use to run a Drupal 5 site.

  PHP will also need to have the following libraries installed:

  * `curl <http://curl.haxx.se/libcurl/php/install.html>`_ 
  * `mbstring <http://php.net/manual/en/mbstring.installation.php>`_ 
  * `xml <http://www.php.net/manual/en/dom.setup.php#102046>`_ 
  
  Check your current modules by running::
  
    php -m

2. Check for Java::

    java -version

   It doesn't necessarily matter what version, but it will be required for
   Selenium.


#. Directions are written to use command-line cURL. You can make sure it's
   installed with::

    curl --version

#. Selenium

  Download the latest version of `Selenium Server
  <http://docs.seleniumhq.org/download/>`_ It's under the heading Selenium
  Server (formerly the Selenium RC Server).   This is a single file which can be
  placed any where you like on your system and run with the following command::

    java -jar selenium-server-standalone-2.44.0.jar & 
    // replace with the name of the version you downloaded

