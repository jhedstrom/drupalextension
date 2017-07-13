@api @d8
Feature: MailContext
  In order to prove the Mail context is working properly
  As a developer
  I need to use the step definitions of this context

  Scenario: Mail is sent
    When Drupal sends an email:
      | to      | fred@example.com |
      | subject | test             |
      | body    | test body        |
    And Drupal sends a mail:
      | to      | jane@example.com |
      | subject | test             |
      | body    | test body 2      |
    Then mails have been sent:
      | to   | subject | body        |
      | fred |         | test body   |
      | jane | test    | body 2      |
    When Drupal sends a mail:
      | to      | jack@example.com          |
      | subject | test                      |
      | body    | test body with many words |
    Then new email is sent:
      | to   | subject | body | body       |
      | jack | test    | test | many words |

  Scenario: Mail is sent to
    When Drupal sends a mail:
      | to      | fred@example.com |
      | subject | test 1           |
    And Drupal sends a mail:
      | to      | jane@example.com |
      | subject | test 2           |
    Then new mail is sent to fred:
      | subject |
      | test 1  |
    And mail has been sent to "jane@example.com":
      | subject |
      | test 2  |

  Scenario: No mail is sent
    Then no mail has been sent

  Scenario: I follow link in mail
    When Drupal sends a mail:
      | to      | fred@example.com                        |
      | subject | test link                               |
      | body    | A link to Google: http://www.Google.com |
    And I follow the link to "google" from the mail with the subject "test link"
    Then I should see "Search"