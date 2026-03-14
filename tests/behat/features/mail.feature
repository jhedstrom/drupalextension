Feature: MailContext
  As a developer
  I want to send and inspect emails in test scenarios
  So that I can verify mail recipients, subjects, and content

  @api @test-drupal
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
      | to   | subject | body      |
      | fred |         | test body |
      | jane | test    | body 2    |
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

  @api @test-drupal
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

  @api @test-drupal
  Scenario: No mail is sent
    Then no mail has been sent

  @api @test-drupal
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

  @api @test-drupal
  Scenario: I follow link in mail
    When Drupal sends a mail:
      | to      | fred@example.com                        |
      | subject | test link                               |
      | body    | A link to Google: http://www.Google.com |
    And I follow the link to "google" from the mail with the subject "test link"
    Then the response should contain "Search"

  @api @test-drupal
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
      | to   | subject | body      |
      | jane | test    | body 2    |
      | fred |         | test body |

  @api @test-drupal
  Scenario: Follow link from mail without filters
    When Drupal sends a mail:
      | to      | fred@example.com                             |
      | subject | plain link                                   |
      | body    | Click here: http://www.Google.com for search |
    And I follow the link to "google" from the mail
    Then the response should contain "Search"

  @api @test-drupal
  Scenario: Follow link from mail to specific recipient
    When Drupal sends a mail:
      | to      | specific@example.com                      |
      | subject | link for specific                         |
      | body    | Visit http://www.Google.com for more info |
    And I follow the link to "google" from the mail to "specific@example.com"
    Then the response should contain "Search"

  @api @test-drupal
  Scenario: Follow link from mail to recipient with subject
    When Drupal sends a mail:
      | to      | combo@example.com           |
      | subject | combo test                  |
      | body    | Link: http://www.Google.com |
    And I follow the link to "google" from the mail to "combo@example.com" with the subject "combo test"
    Then the response should contain "Search"

  @api @test-drupal
  Scenario: New mail sent to recipient with subject filter
    When Drupal sends a mail:
      | to      | bob@example.com  |
      | subject | filtered test    |
      | body    | filtered body    |
    Then new mail is sent to "bob@example.com" with the subject "filtered test":
      | body          |
      | filtered body |

  @api @test-drupal
  Scenario: Count mails sent with subject
    When Drupal sends a mail:
      | to      | count1@example.com |
      | subject | counted subject    |
    And Drupal sends a mail:
      | to      | count2@example.com |
      | subject | counted subject    |
    And Drupal sends a mail:
      | to      | count3@example.com |
      | subject | other subject      |
    Then 2 mails have been sent with the subject "counted subject"
    And 1 mail has been sent with the subject "other subject"

  @api @test-drupal
  Scenario: Count new mails sent to recipient
    When Drupal sends a mail:
      | to      | newcount@example.com |
      | subject | first               |
    And Drupal sends a mail:
      | to      | newcount@example.com |
      | subject | second              |
    Then 2 new emails are sent to "newcount@example.com"

  @api @test-drupal
  Scenario: Count new mails sent to recipient with subject
    When Drupal sends a mail:
      | to      | ncsubj@example.com |
      | subject | target subject     |
    And Drupal sends a mail:
      | to      | ncsubj@example.com |
      | subject | target subject     |
    And Drupal sends a mail:
      | to      | ncsubj@example.com |
      | subject | other              |
    Then 2 new emails are sent to "ncsubj@example.com" with the subject "target subject"

  @api @test-drupal
  Scenario: New mail sent with subject and body filter
    When Drupal sends a mail:
      | to      | alice@example.com |
      | subject | new mail test     |
      | body    | first body        |
    Then a mail has been sent to "alice@example.com" with the subject "new mail test":
      | body       |
      | first body |
    When Drupal sends a mail:
      | to      | alice@example.com |
      | subject | new mail test     |
      | body    | second body       |
    Then new email is sent:
      | body        |
      | second body |

  @api @test-drupal
  Scenario: New mail sent with subject-only filter
    When Drupal sends a mail:
      | to      | subonly@example.com |
      | subject | subject only test  |
      | body    | some body text     |
    Then new email is sent with the subject "subject only test":
      | body           |
      | some body text |

  @test-blackbox
  Scenario: Fail when expected mail body does not match
    Given some behat configuration
    And scenario steps:
      """
      When Drupal sends a mail:
        | to      | neg@example.com |
        | subject | neg test        |
        | body    | actual body     |
      Then mails have been sent:
        | body          |
        | wrong body    |
      """
    When I run "behat --no-colors"
    Then it should fail

  @test-blackbox
  Scenario: Fail when expected mail recipient does not match
    Given some behat configuration
    And scenario steps:
      """
      When Drupal sends a mail:
        | to      | real@example.com |
        | subject | neg test         |
      Then a mail has been sent to "wrong@example.com"
      """
    When I run "behat --no-colors"
    Then it should fail

  @test-blackbox
  Scenario: Fail when expected new mail is not sent
    Given some behat configuration
    And scenario steps:
      """
      When Drupal sends a mail:
        | to      | sent@example.com |
        | subject | sent subject     |
      Then new email is sent:
        | to   | subject | body |
        | sent | sent    | sent |
      Then new email is sent:
        | to            |
        | not-sent      |
      """
    When I run "behat --no-colors"
    Then it should fail

  @test-blackbox
  Scenario: Fail when mail count does not match
    Given some behat configuration
    And scenario steps:
      """
      When Drupal sends a mail:
        | to      | cnt@example.com |
        | subject | cnt test        |
      Then 5 mails have been sent
      """
    When I run "behat --no-colors"
    Then it should fail

  @test-blackbox
  Scenario: Fail when following link that does not exist in mail
    Given some behat configuration
    And scenario steps:
      """
      When Drupal sends a mail:
        | to      | link@example.com |
        | subject | link test        |
        | body    | No links here    |
      And I follow the link to "nonexistent" from the mail
      """
    When I run "behat --no-colors"
    Then it should fail

  @test-blackbox
  Scenario: Fail when following link from wrong recipient
    Given some behat configuration
    And scenario steps:
      """
      When Drupal sends a mail:
        | to      | right@example.com          |
        | subject | link test                  |
        | body    | Link: http://www.Google.com |
      And I follow the link to "google" from the mail to "wrong@example.com"
      """
    When I run "behat --no-colors"
    Then it should fail

  @test-blackbox
  Scenario: Fail when new mail count to recipient does not match
    Given some behat configuration
    And scenario steps:
      """
      When Drupal sends a mail:
        | to      | cntr@example.com |
        | subject | cntr test        |
      Then 3 new emails are sent to "cntr@example.com"
      """
    When I run "behat --no-colors"
    Then it should fail

  @test-blackbox
  Scenario: Fail when mail count with subject does not match
    Given some behat configuration
    And scenario steps:
      """
      When Drupal sends a mail:
        | to      | subj@example.com  |
        | subject | specific subject  |
      Then 5 mails have been sent with the subject "specific subject"
      """
    When I run "behat --no-colors"
    Then it should fail

  @test-blackbox
  Scenario: Fail when following link that exists but does not match fragment
    Given some behat configuration
    And scenario steps:
      """
      When Drupal sends a mail:
        | to      | frag@example.com                    |
        | subject | fragment test                       |
        | body    | Visit http://www.example.com/page1  |
      And I follow the link to "nonexistent-fragment" from the mail
      """
    When I run "behat --no-colors"
    Then it should fail

  @test-blackbox
  Scenario: Fail when no mail sent but expected
    Given some behat configuration
    And scenario steps:
      """
      Then a mail has been sent to "nobody@example.com"
      """
    When I run "behat --no-colors"
    Then it should fail

  @test-blackbox
  Scenario: Fail when mail has been sent but should not have been
    Given some behat configuration
    And scenario steps:
      """
      When Drupal sends a mail:
        | to      | exists@example.com |
        | subject | exists test        |
      Then no mail has been sent
      """
    When I run "behat --no-colors"
    Then it should fail
