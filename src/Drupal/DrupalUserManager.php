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
  public function getCurrentUser() {
    return $this->user;
  }

  /**
   * {@inheritdoc}
   */
  public function setCurrentUser($user) {
    $this->user = $user;
  }

}
