<?php 

  /**
   * @Then /^I should see a slideshow$/
   */
  public function assertSlideshowExists() {
    $slideshow = $this->getMainContext()->getSession()->getPage()
    ->find('css', '.beanslide');
    if (!$slideshow) {
      throw new \Exception(sprintf("No slideshow found on the page %s", 
      $this->getSession()->getCurrentUrl()));
    }
  }
