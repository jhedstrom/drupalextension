<?php

namespace Drupal;

class DrupalUserManager implements DrupalUserManagerInterface {

  /**
   * @var \stdClass|bool
   */
  protected $user = FALSE;

  /**
   * {@inheritdoc}
   */
  public function getUser() {
    return $this->user;
  }

  /**
   * {@inheritdoc}
   */
  public function setUser($user) {
    $this->user = $user;
  }

}
