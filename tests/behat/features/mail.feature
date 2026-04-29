Feature: MailContext
  As a developer
  I want to send and inspect emails in test scenarios
  So that I can verify mail recipients, subjects, and content

  @test-drupal @api
  Scenario: Assert "When I send the following mail:" passes
    When I send the following email:
      | to      | fred@example.com |
      | subject | test             |
      | body    | test body        |
    And I send the following mail:
      | to      | jane@example.com |
      | subject | test             |
      | body    | test body 2      |
    Then there should be a total of 2 mails sent

  @test-drupal @api
  Scenario: Assert "Then the following mail should have been sent:" passes
    When I send the following email:
      | to      | fred@example.com |
      | subject | test             |
      | body    | test body        |
    And I send the following mail:
      | to      | jane@example.com |
      | subject | test             |
      | body    | test body 2      |
    Then the following mails should have been sent:
      | to   | subject | body      |
      | fred |         | test body |
      | jane | test    | body 2    |

  @test-drupal @api
  Scenario: Assert "Then the following mail should have been sent:" fails when no mail sent
    Given some behat configuration
    And scenario steps tagged with "@test-drupal @api":
      """
      Then the following mails should have been sent:
        | to               |
        | user@example.com |
      """
    When I run behat with drupal profile
    Then it should fail with an error:
      """
      Expected 1 mail, but 0 found
      """

  @test-drupal @api
  Scenario: Assert "Then the following mail should have been sent:" fails when content does not match
    Given some behat configuration
    And scenario steps tagged with "@test-drupal @api":
      """
      When I send the following mail:
        | to      | fred@example.com |
        | subject | actual subject   |
      Then the following mail should have been sent:
        | subject          |
        | wrong subject    |
      """
    When I run behat with drupal profile
    Then it should fail with an error:
      """
      did not have 'wrong subject' in its subject field
      """

  @test-drupal @api
  Scenario: Assert "Then the following mail should have been sent:" with filter variants passes
    When I send the following email:
      | to      | fred@example.com |
      | subject | test             |
      | body    | test body        |
    And I send the following mail:
      | to      | jane@example.com |
      | subject | test             |
      | body    | test body 2      |
    Then the following mail should have been sent to "jane@example.com":
      | subject |
      | test    |
    And the following emails should have been sent with the subject "test":
      | to   |
      | fred |
      | jane |
    And the following emails should have been sent to "fred" with the subject "test":
      | body      |
      | test body |

  @test-drupal @api
  Scenario: Assert "Then the following new mail should have been sent:" passes
    When I send the following email:
      | to      | fred@example.com |
      | subject | test             |
      | body    | test body        |
    Then the following new email should have been sent:
      | to   | body      |
      | fred | test body |
    When I send the following mail:
      | to      | jack@example.com          |
      | subject | for jack                  |
      | body    | test body with many words |
    Then the following new email should have been sent:
      | to   | body       |
      | jack | many words |

  @test-drupal @api
  Scenario: Assert "Then the following new mail should have been sent to :to:" passes
    When I send the following mail:
      | to      | fred@example.com |
      | subject | test 1           |
    And I send the following mail:
      | to      | jane@example.com |
      | subject | test 2           |
    Then the following new mail should have been sent to "fred":
      | subject |
      | test 1  |

  @test-drupal @api
  Scenario: Assert "Then there should be a total of :count mail sent" passes
    Then there should be a total of no mails sent

  @test-drupal @api
  Scenario: Assert "Then there should be a total of :count mail sent" fails when count does not match
    Given some behat configuration
    And scenario steps tagged with "@test-drupal @api":
      """
      When I send the following mail:
        | to      | fred@example.com |
        | subject | test             |
      Then there should be a total of 2 mails sent
      """
    When I run behat with drupal profile
    Then it should fail with an error:
      """
      Expected 2 mail, but 1 found
      """

  @test-drupal @api
  Scenario: Assert "Then there should be a total of :count mail sent" with filter variants passes
    When I send the following email:
      | to      | fred@example.com |
      | subject | test             |
    And I send the following mail:
      | to      | jane@example.com |
      | subject | test             |
    And I send the following mail:
      | to      | jane@example.com |
      | subject | something else   |
    Then there should be a total of 2 new emails sent with the subject "test"
    And there should be a total of 1 mail sent to "jane" with the subject "something else"
    And there should be a total of no new emails sent
    And there should be a total of no mails sent to "hans"

  @test-drupal @api
  Scenario: Assert "Then there should be a total of :count new mail sent" fails when count does not match
    Given some behat configuration
    And scenario steps tagged with "@test-drupal @api":
      """
      When I send the following mail:
        | to      | fred@example.com |
        | subject | test             |
      Then there should be a total of 5 new emails sent
      """
    When I run behat with drupal profile
    Then it should fail with an error:
      """
      Expected 5 mail, but 1 found
      """

  @test-drupal @api
  Scenario: Assert "When I follow the link to :urlFragment from the mail" passes
    When I send the following mail:
      | to      | fred@example.com                        |
      | subject | test link                               |
      | body    | A link to Google: http://www.Google.com |
    And I follow the link to "google" from the mail with the subject "test link"
    Then the response should contain "Search"

  @test-drupal @api
  Scenario: Assert "When I follow the link to :urlFragment from the mail" passes without filters
    When I send the following mail:
      | to      | fred@example.com                             |
      | subject | plain link                                   |
      | body    | Click here: http://www.Google.com for search |
    And I follow the link to "google" from the mail
    Then the response should contain "Search"

  @test-drupal @api
  Scenario: Assert "When I follow the link to :urlFragment from the mail to :to" passes
    When I send the following mail:
      | to      | specific@example.com                      |
      | subject | link for specific                         |
      | body    | Visit http://www.Google.com for more info |
    And I follow the link to "google" from the mail to "specific@example.com"
    Then the response should contain "Search"

  @test-drupal @api
  Scenario: Assert "When I follow the link to :urlFragment from the mail to :to with the subject :subject" passes
    When I send the following mail:
      | to      | combo@example.com           |
      | subject | combo test                  |
      | body    | Link: http://www.Google.com |
    And I follow the link to "google" from the mail to "combo@example.com" with the subject "combo test"
    Then the response should contain "Search"

  @test-drupal @api
  Scenario: Assert "When I follow the link to :urlFragment from the mail" fails when no mail found
    Given some behat configuration
    And scenario steps tagged with "@test-drupal @api":
      """
      When I follow the link to "example" from the mail to "nobody@example.com"
      """
    When I run behat with drupal profile
    Then it should fail with an error:
      """
      No such mail found.
      """

  @test-drupal @api
  Scenario: Assert "When I follow the link to :urlFragment from the mail" fails when no URL in body
    Given some behat configuration
    And scenario steps tagged with "@test-drupal @api":
      """
      When I send the following mail:
        | to      | fred@example.com |
        | subject | no link          |
        | body    | No URLs here     |
      And I follow the link to "example" from the mail
      """
    When I run behat with drupal profile
    Then it should fail with an error:
      """
      No URL found in mail body.
      """

  @test-drupal @api
  Scenario: Assert "When I follow the link to :urlFragment from the mail" fails when URL fragment not found
    Given some behat configuration
    And scenario steps tagged with "@test-drupal @api":
      """
      When I send the following mail:
        | to      | fred@example.com                    |
        | subject | wrong fragment                      |
        | body    | Visit http://www.Google.com for fun |
      And I follow the link to "nonexistent-fragment" from the mail
      """
    When I run behat with drupal profile
    Then it should fail with an error:
      """
      No URL in mail body contained "nonexistent-fragment".
      """

  @test-drupal @api
  Scenario: Assert mail comparison is order insensitive
    When I send the following email:
      | to      | fred@example.com |
      | subject | test             |
      | body    | test body        |
    And I send the following mail:
      | to      | jane@example.com |
      | subject | test             |
      | body    | test body 2      |
    Then the following mails should have been sent:
      | to   | subject | body      |
      | jane | test    | body 2    |
      | fred |         | test body |

  @test-drupal @api
  Scenario: Assert new mail sent with subject and body filter passes
    When I send the following mail:
      | to      | alice@example.com |
      | subject | new mail test     |
      | body    | first body        |
    Then the following mail should have been sent to "alice@example.com" with the subject "new mail test":
      | body       |
      | first body |
    When I send the following mail:
      | to      | alice@example.com |
      | subject | new mail test     |
      | body    | second body       |
    Then the following new email should have been sent:
      | body        |
      | second body |
