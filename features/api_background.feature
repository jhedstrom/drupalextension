@d6 @d7 @d8 @api
Feature: DrupalContext
  Test DrupalContext in combination with Backgrounds

  Background:
    Given "tags" terms:
      | name    |
      | Tag one |
      | Tag two |

    Given users:
      | name     |
      | User one |
      | User two |

    Given "article" content:
      | title    |
      | Node one |
      | Node two |

  Scenario Outline:
    Given I am not logged in

    Examples:
      | user |
      | foo  |
      | bar  |
