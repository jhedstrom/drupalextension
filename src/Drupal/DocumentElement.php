<?php

namespace Drupal;

use Behat\Mink\Driver\BrowserKitDriver;
use Behat\Mink\Element\TraversableElement;

/**
 * Document element.
 *
 * This is largely a copy of \Behat\Mink\Element\DocumentElement. This fixes the
 * ::getText() method to remove script tags inside the body element.
 *
 * @see https://github.com/minkphp/MinkBrowserKitDriver/issues/153
 * @see https://www.drupal.org/project/drupal/issues/3175718
 * @see \Behat\Mink\Element\DocumentElement
 */
class DocumentElement extends TraversableElement
{
    /**
     * Returns XPath for handled element.
     *
     * @return string
     */
    public function getXpath()
    {
        return '//html';
    }

    /**
     * Returns document content.
     *
     * @return string
     */
    public function getContent()
    {
        return trim($this->getDriver()->getContent());
    }

    /**
     * Check whether document has specified content.
     *
     * @param string $content
     *
     * @return bool
     */
    public function hasContent($content)
    {
        return $this->has('named', ['content', $content]);
    }

    /**
     * {@inheritdoc}
     */
    public function getText()
    {
        if ($this->getDriver() instanceof BrowserKitDriver) {
          // Work around https://github.com/minkphp/MinkBrowserKitDriver/issues/153.
          // To simulate what the user sees, it removes:
          // - all text inside the head tags
          // - Drupal settings json.
            $raw_content = preg_replace([
                '@<head>(.+?)</head>@si',
                '@<script type="application/json" data-drupal-selector="drupal-settings-json">([^<]*)</script>@',
            ], '', $this->getContent());
          // Filter out all HTML tags, as they are not visible in a normal browser.
            $text = strip_tags($raw_content);
          // To preserve BC and match \Behat\Mink\Element\Element::getText() include
          // the page title.
            $title_element = $this->find('css', 'title');
            if ($title_element) {
                $text = $title_element->getText() . ' ' . $text;
            }
          // To match what the user sees and \Behat\Mink\Element\Element::getText()
          // decode HTML entities.
            $text = html_entity_decode($text, ENT_QUOTES);
          // To match \Behat\Mink\Element\Element::getText() remove new lines and
          // normalize spaces.
            $text = str_replace("\n", ' ', $text);
            $text = preg_replace('/ {2,}/', ' ', $text);
            return trim($text);
        }

      // If using a real browser fallback to the \Behat\Mink\Element\Element
      // implementation.
        return parent::getText();
    }
}
