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
      | subject | for jack                  |
      | body    | test body with many words |
    Then new email is sent:
      | to   | body | body       |
      | jack | test | many words |
    And a mail has been sent to "jane@example.com"
    And a mail has been sent to "jane@example.com":
      | subject |
      | test    |
    And an email has been sent with the subject "test"
    And emails have been sent with the subject "test":
      | to   |
      | fred |
      | jane |
    And a mail has been sent to "fred" with the subject "test"
    And emails have been sent to "fred" with the subject "test":
      | body      |
      | test body |

  Scenario: New mail is sent to someone
    When Drupal sends a mail:
      | to      | fred@example.com |
      | subject | test 1           |
    And Drupal sends a mail:
      | to      | jane@example.com |
      | subject | test 2           |
    Then new mail is sent to fred:
      | subject |
      | test 1  |

  Scenario: No mail is sent
    Then no mail has been sent

  Scenario: Count sent mail
    When Drupal sends an email:
      | to      | fred@example.com |
      | subject | test             |
    And Drupal sends a mail:
      | to      | jane@example.com |
      | subject | test             |
    And Drupal sends a mail:
      | to      | jane@example.com |
      | subject | something else   |
    Then 2 new emails are sent with the subject "test"
    And 1 mail has been sent to "jane" with the subject "something else"
    And no new emails are sent
    And no mail has been sent to "hans"

  Scenario: I follow link in mail
    When Drupal sends a mail:
      | to      | fred@example.com                        |
      | subject | test link                               |
      | body    | A link to Google: http://www.Google.com |
    And I follow the link to "google" from the mail with the subject "test link"
    Then I should see "Search"

  Scenario: We try to be order insensitive
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
      | jane | test    | body 2      |
      | fred |         | test body   |
