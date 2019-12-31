@d6 @d7 @d8 @api
Feature: DrupalContext with background steps
  Test DrupalContext in combination with Backgrounds

  Background:
    Given "tags" terms:
      | name    |
      | Tag one |
      | Tag two |

    And users:
      | name     |
      | User one |
      | User two |

    And "article" content:
      | title    |
      | Node one |
      | Node two |

  Scenario Outline:
    Given I am not logged in

    Examples:
      | user |
      | foo  |
      | bar  |
