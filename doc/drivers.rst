Drupal Extension Drivers
========================

The Drupal Extension provides drivers for interacting with your site which are
compatible with Drupal 6, 7, and 8. Each driver has its own limitations.

+-----------------------+----------+---------+------------+
| Feature               | Blackbox | Drush   | Drupal API |
+=======================+==========+=========+============+
| Map Regions           | Yes      | Yes     | Yes        |
+-----------------------+----------+---------+------------+
| Create users          | No       | Yes     | Yes        |
+-----------------------+----------+---------+------------+
| Create nodes          | No       | Yes [*] | Yes        |
+-----------------------+----------+---------+------------+
| Create vocabularies   | No       | Yes [*] | Yes        |
+-----------------------+----------+---------+------------+
| Create taxonomy terms | No       | Yes [*] | Yes        |
+-----------------------+----------+---------+------------+
| Run tests and site    |          |         |            |
| on different servers  | Yes      | Yes     | No         |
+-----------------------+----------+---------+------------+

[*] Requires that the `Behat Drush Endpoint <https://github.com/drush-ops/behat-drush-endpoint>`_ be installed on the Drupal site under test.
