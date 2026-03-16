<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Element;

use Behat\Mink\Driver\BrowserKitDriver;
use Behat\Mink\Element\TraversableElement;

/**
 * Document element with BrowserKitDriver getText() workaround.
 *
 * Replaces Mink's DocumentElement via class_alias() to fix a bug where
 * BrowserKitDriver wrongly includes text from <head> and Drupal settings
 * JSON in the page text.
 *
 * This class must extend TraversableElement (not Mink's DocumentElement)
 * because it is registered as a class_alias before the Mink class is
 * autoloaded.
 *
 * @see https://github.com/minkphp/MinkBrowserKitDriver/issues/153
 * @see https://www.drupal.org/project/drupal/issues/3175718
 */
class DocumentElement extends TraversableElement {

  /**
   * Returns XPath for handled element.
   *
   * @return string
   *   The XPath expression.
   */
  public function getXpath() {
    return '//html';
  }

  /**
   * Returns document content.
   *
   * @return string
   *   The trimmed page content.
   */
  public function getContent(): string {
    return trim($this->getDriver()->getContent());
  }

  /**
   * Check whether document has specified content.
   *
   * @param string $content
   *   The content to check for.
   *
   * @return bool
   *   TRUE if the content is found, FALSE otherwise.
   */
  public function hasContent(string $content) {
    return $this->has('named', ['content', $content]);
  }

  /**
   * {@inheritdoc}
   */
  public function getText() {
    if ($this->getDriver() instanceof BrowserKitDriver) {
      // Work around https://github.com/minkphp/MinkBrowserKitDriver/issues/153.
      // To simulate what the user sees, it removes:
      // - all text inside the head tags
      // - Drupal settings json.
      $rawContent = preg_replace([
        '@<head>(.+?)</head>@si',
        '@<script type="application/json" data-drupal-selector="drupal-settings-json">([^<]*)</script>@',
      ], '', $this->getContent());
      // Filter out all HTML tags, as they are not visible in a normal browser.
      $text = strip_tags((string) $rawContent);
      // To preserve BC and match \Behat\Mink\Element\Element::getText() include
      // the page title.
      $titleElement = $this->find('css', 'title');
      if ($titleElement) {
        $text = $titleElement->getText() . ' ' . $text;
      }
      // To match what the user sees and \Behat\Mink\Element\Element::getText()
      // decode HTML entities.
      $text = html_entity_decode($text, ENT_QUOTES);
      // To match \Behat\Mink\Element\Element::getText() remove new lines and
      // normalize spaces.
      $text = str_replace("\n", ' ', $text);
      $text = preg_replace('/ {2,}/', ' ', $text);
      return trim((string) $text);
    }

    return parent::getText();
  }

}
