# Available steps

| Class | Description |
| --- | --- |
| [BatchContext](#batchcontext) | Extensions to the Mink Extension. |
| [ConfigContext](#configcontext) | Provides pre-built step definitions for interacting with Drupal config. |
| [DrupalContext](#drupalcontext) | Provides pre-built step definitions for interacting with Drupal. |
| [DrushContext](#drushcontext) | Provides step definitions for interacting directly with Drush commands. |
| [MailContext](#mailcontext) | Provides pre-built step definitions for interacting with mail. |
| [MarkupContext](#markupcontext) | Extensions to the Mink Extension. |
| [MessageContext](#messagecontext) | Provides step-definitions for interacting with Drupal messages. |
| [MinkContext](#minkcontext) | Extensions to the Mink Extension. |


---

## BatchContext

[Source](src/Drupal/DrupalExtension/Context/BatchContext.php), [Example](tests/behat/features/batch.feature)

>  Extensions to the Mink Extension.


<details>
  <summary><code>@Given I wait for the batch job to finish</code></summary>

<br/>
Wait for the Batch API to finish. 
<br/><br/>

```gherkin
Given I wait for the batch job to finish

```

</details>

<details>
  <summary><code>@Given there is an item in the system queue:</code></summary>

<br/>
Creates a queue item. Defaults inputs if none are available. 
<br/><br/>

```gherkin
  Given there is an item in the system queue:
    | name    | my_queue              |
    | data    | {"key":"value"}       |
    | created | 1700000000            |
    | expire  | 0                     |

```

</details>

## ConfigContext

[Source](src/Drupal/DrupalExtension/Context/ConfigContext.php), [Example](tests/behat/features/config.feature)

>  Provides pre-built step definitions for interacting with Drupal config.


<details>
  <summary><code>@Given I set the configuration item :name with key :key to :value</code></summary>

<br/>
Sets basic configuration item. 
<br/><br/>

```gherkin
  Given I set the configuration item "system.site" with key "name" to "My Site"

```

</details>

<details>
  <summary><code>@Given I set the configuration item :name with key :key with values:</code></summary>

<br/>
Sets complex configuration. 
<br/><br/>

```gherkin
  Given I set the configuration item "system.site" with key "page" with values:
    | key   | value  |
    | front | /node  |
    | 403   | /error |

```

</details>

## DrupalContext

[Source](src/Drupal/DrupalExtension/Context/DrupalContext.php)

>  Provides pre-built step definitions for interacting with Drupal.


<details>
  <summary><code>@Given I am an anonymous user
@Given I am not logged in
@Then I log out</code></summary>

<br/>
Assert the user is anonymous or log out. 
<br/><br/>

```gherkin
Given I am an anonymous user
Given I am not logged in
Then I log out

```

</details>

<details>
  <summary><code>@Given I am logged in as a user with the :role role(s)
@Given I am logged in as a/an :role</code></summary>

<br/>
Creates and authenticates a user with the given role(s). 
<br/><br/>

```gherkin
Given I am logged in as a user with the "editor" role
Given I am logged in as a user with the "editor, admin" roles
Given I am logged in as an "editor"

```

</details>

<details>
  <summary><code>@Given I am logged in as a user with the :role role(s) and I have the following fields:</code></summary>

<br/>
Creates and authenticates a user with the given role(s) and given fields. 
<br/><br/>

```gherkin
  Given I am logged in as a user with the "editor" role and I have the following fields:
    | field_user_name    | John  |
    | field_user_surname | Smith |

```

</details>

<details>
  <summary><code>@Given I am logged in as :name</code></summary>

<br/>
Log in as an existing user by name. 
<br/><br/>

```gherkin
Given I am logged in as "admin"

```

</details>

<details>
  <summary><code>@Given I am logged in as a user with the :permissions permission(s)</code></summary>

<br/>
Log in as a user with specific permissions. 
<br/><br/>

```gherkin
Given I am logged in as a user with the "administer nodes" permission
Given I am logged in as a user with the "administer nodes, bypass node access" permissions

```

</details>

<details>
  <summary><code>@Given I click :link in the :rowText row</code></summary>

<br/>
Clicks a link in a table row containing given text. 
<br/><br/>

```gherkin
Given I click "Edit" in the "My article" row

```

</details>

<details>
  <summary><code>@Given I press :button in the :rowText row</code></summary>

<br/>
Attempts to find a button in a table row containing giving text. 
<br/><br/>

```gherkin
Given I press "Remove" in the "My article" row

```

</details>

<details>
  <summary><code>@Given the cache has been cleared</code></summary>

<br/>
Clear the Drupal cache. 
<br/><br/>

```gherkin
Given the cache has been cleared

```

</details>

<details>
  <summary><code>@Given I run cron</code></summary>

<br/>
Run Drupal cron. 
<br/><br/>

```gherkin
Given I run cron

```

</details>

<details>
  <summary><code>@Given I am viewing a/an :type (content )with the title :title
@Given a/an :type (content )with the title :title</code></summary>

<br/>
Creates content of the given type. 
<br/><br/>

```gherkin
Given I am viewing an "article" with the title "Test article"
Given I am viewing an "article" content with the title "Test article"
Given a "page" with the title "About us"

```

</details>

<details>
  <summary><code>@Given I am viewing my :type (content )with the title :title</code></summary>

<br/>
Creates content authored by the current user. 
<br/><br/>

```gherkin
Given I am viewing my "article" with the title "My article"
Given I am viewing my "article" content with the title "My article"

```

</details>

<details>
  <summary><code>@Given :type content:</code></summary>

<br/>
Creates content of a given type. 
<br/><br/>

```gherkin
  Given "article" content:
    | title      | status |
    | My article | 1      |

```

</details>

<details>
  <summary><code>@Given I am viewing a/an :type( content):</code></summary>

<br/>
Creates content of the given type and visits it. 
<br/><br/>

```gherkin
  Given I am viewing an "article":
    | title | My article     |
    | body  | Lorem ipsum    |
  Given I am viewing an "article" content:
    | title | My article     |
    | body  | Lorem ipsum    |

```

</details>

<details>
  <summary><code>@Given I am viewing a/an :vocabulary term with the name :name
@Given a/an :vocabulary term with the name :name</code></summary>

<br/>
Creates a term on an existing vocabulary. 
<br/><br/>

```gherkin
Given I am viewing a "tags" term with the name "Sports"
Given an "categories" term with the name "News"

```

</details>

<details>
  <summary><code>@Given users:</code></summary>

<br/>
Creates multiple users. 
<br/><br/>

```gherkin
  Given users:
    | name     | mail            | roles  |
    | Joe User | joe@example.com | editor |

```

</details>

<details>
  <summary><code>@Given :vocabulary terms:</code></summary>

<br/>
Creates one or more terms on an existing vocabulary. 
<br/><br/>

```gherkin
  Given "tags" terms:
    | name   |
    | Sports |
    | News   |

```

</details>

<details>
  <summary><code>@Given the/these (following )languages are available:</code></summary>

<br/>
Creates one or more languages. 
<br/><br/>

```gherkin
  Given the following languages are available:
    | languages |
    | en        |
    | fr        |
  Given these languages are available:
    | languages |
    | de        |

```

</details>

<details>
  <summary><code>@Then I should see (the text ):text in the :rowText row</code></summary>

<br/>
Find text in a table row containing given text. 
<br/><br/>

```gherkin
Then I should see "Edit" in the "My article" row
Then I should see the text "Edit" in the "My article" row

```

</details>

<details>
  <summary><code>@Then I should not see (the text ):text in the :rowText row</code></summary>

<br/>
Asset text not in a table row containing given text. 
<br/><br/>

```gherkin
Then I should not see "Delete" in the "My article" row
Then I should not see the text "Delete" in the "My article" row

```

</details>

<details>
  <summary><code>@Then I (should )see the :link in the :rowText row</code></summary>

<br/>
Asserts a link exists in a table row containing given text. 
<br/><br/>

```gherkin
Then I see the "Edit" in the "My article" row
Then I should see the "Edit" in the "My article" row

```

</details>

<details>
  <summary><code>@Then I should not see the :link in the :rowText row</code></summary>

<br/>
Asserts a link does not exist in a table row containing given text. 
<br/><br/>

```gherkin
Then I should not see the "Delete" in the "My article" row

```

</details>

<details>
  <summary><code>@Then I should be able to edit a/an :type( content)</code></summary>

<br/>
Asserts that a given content type is editable. 
<br/><br/>

```gherkin
Then I should be able to edit an "article"
Then I should be able to edit an "article" content

```

</details>

<details>
  <summary><code>@Then (I )break</code></summary>

<br/>
Pauses the scenario until the user presses a key. Useful when debugging a scenario. 
<br/><br/>

```gherkin
Then break
Then I break

```

</details>

## DrushContext

[Source](src/Drupal/DrupalExtension/Context/DrushContext.php), [Example](tests/behat/features/drush.feature)

>  Provides step definitions for interacting directly with Drush commands.


<details>
  <summary><code>@Given I run drush :command</code></summary>

<br/>
Run a Drush command. 
<br/><br/>

```gherkin
Given I run drush "status"

```

</details>

<details>
  <summary><code>@Given I run drush :command :arguments</code></summary>

<br/>
Run a Drush command with arguments. 
<br/><br/>

```gherkin
Given I run drush "pm:list" "--status=enabled"

```

</details>

<details>
  <summary><code>@Then drush output should contain :output</code></summary>

<br/>
Assert Drush output contains a string. 
<br/><br/>

```gherkin
Then drush output should contain "Drupal version"

```

</details>

<details>
  <summary><code>@Then drush output should match :regex</code></summary>

<br/>
Assert Drush output matches a regular expression. 
<br/><br/>

```gherkin
Then drush output should match "/Drupal [0-9]+/"

```

</details>

<details>
  <summary><code>@Then drush output should not contain :output</code></summary>

<br/>
Assert Drush output does not contain a string. 
<br/><br/>

```gherkin
Then drush output should not contain "error"

```

</details>

<details>
  <summary><code>@Then print last drush output</code></summary>

<br/>
Print the last Drush output. 
<br/><br/>

```gherkin
Then print last drush output

```

</details>

## MailContext

[Source](src/Drupal/DrupalExtension/Context/MailContext.php), [Example](tests/behat/features/mail.feature)

>  Provides pre-built step definitions for interacting with mail.


<details>
  <summary><code>@When Drupal sends a/an (e)mail:</code></summary>

<br/>
This is mainly useful for testing this context. 
<br/><br/>

```gherkin
  When Drupal sends a mail:
    | to      | user@example.com |
    | subject | Test mail        |
    | body    | Hello world      |
  When Drupal sends an email:
    | to      | user@example.com |
    | subject | Test email       |
    | body    | Hello world      |

```

</details>

<details>
  <summary><code>@When I follow the link to :urlFragment from the (e)mail
@When I follow the link to :urlFragment from the (e)mail to :to
@When I follow the link to :urlFragment from the (e)mail with the subject :subject
@When I follow the link to :urlFragment from the (e)mail to :to with the subject :subject</code></summary>

<br/>
Follow a link from an email body. 
<br/><br/>

```gherkin
When I follow the link to "user/reset" from the mail
When I follow the link to "user/reset" from the email
When I follow the link to "user/reset" from the email to "user@example.com"
When I follow the link to "user/reset" from the email with the subject "Welcome"

```

</details>

<details>
  <summary><code>@Then (a )(an )(e)mail(s) has/have been sent:
@Then (a )(an )(e)mail(s) has/have been sent to :to:
@Then (a )(an )(e)mail(s) has/have been sent with the subject :subject:
@Then (a )(an )(e)mail(s) has/have been sent to :to with the subject :subject:</code></summary>

<br/>
Check all mail sent during the scenario. 
<br/><br/>

```gherkin
  Then mail has been sent:
    | to               | body                |
    | user@example.com | Welcome to the site |
  Then an email has been sent with the subject "Welcome":
    | to               | body                |
    | user@example.com | Welcome to the site |
  Then emails have been sent to "user@example.com" with the subject "Welcome":
    | body                |
    | Welcome to the site |

```

</details>

<details>
  <summary><code>@Then (a )(an )new (e)mail(s) is/are sent:
@Then (a )(an )new (e)mail(s) is/are sent to :to:
@Then (a )(an )new (e)mail(s) is/are sent with the subject :subject:
@Then (a )(an )new (e)mail(s) is/are sent to :to with the subject :subject:</code></summary>

<br/>
Check mail sent since the last step that checked mail. 
<br/><br/>

```gherkin
  Then new mail is sent:
    | subject   |
    | Greetings |
  Then a new email is sent to "user@example.com":
    | subject   |
    | Greetings |

```

</details>

<details>
  <summary><code>@Then :count (e)mail(s) has/have been sent
@Then :count (e)mail(s) has/have been sent to :to
@Then :count (e)mail(s) has/have been sent with the subject :subject
@Then :count (e)mail(s) has/have been sent to :to with the subject :subject</code></summary>

<br/>
Check all mail sent during the scenario. 
<br/><br/>

```gherkin
Then 0 emails have been sent
Then 2 mails have been sent to "user@example.com"
Then 1 email has been sent with the subject "Welcome"

```

</details>

<details>
  <summary><code>@Then :count new (e)mail(s) is/are sent
@Then :count new (e)mail(s) is/are sent to :to
@Then :count new (e)mail(s) is/are sent with the subject :subject
@Then :count new (e)mail(s) is/are sent to :to with the subject :subject</code></summary>

<br/>
Check mail sent since the last step that checked mail. 
<br/><br/>

```gherkin
Then 0 new emails are sent
Then 1 new mail is sent to "user@example.com"

```

</details>

## MarkupContext

[Source](src/Drupal/DrupalExtension/Context/MarkupContext.php), [Example](tests/behat/features/markup.feature)

>  Extensions to the Mink Extension.


<details>
  <summary><code>@Then I should see the button :button in the :region( region)
@Then I should see the :button button in the :region( region)</code></summary>

<br/>
Checks if a button with id|name|title|alt|value exists in a region. 
<br/><br/>

```gherkin
Then I should see the button "Submit" in the "content"
Then I should see the button "Submit" in the "content" region
Then I should see the "Submit" button in the "content" region

```

</details>

<details>
  <summary><code>@Then I should not see the button :button in the :region( region)
@Then I should not see the :button button in the :region( region)</code></summary>

<br/>
Asserts that a button does not exists in a region. 
<br/><br/>

```gherkin
Then I should not see the button "Delete" in the "sidebar"
Then I should not see the button "Delete" in the "sidebar" region
Then I should not see the "Delete" button in the "sidebar" region

```

</details>

<details>
  <summary><code>@Then I( should) see the :tag element in the :region( region)</code></summary>

<br/>
Assert an element exists in a region. 
<br/><br/>

```gherkin
Then I see the "h2" element in the "content"
Then I should see the "h2" element in the "content" region

```

</details>

<details>
  <summary><code>@Then I( should) not see the :tag element in the :region( region)</code></summary>

<br/>
Assert an element does not exist in a region. 
<br/><br/>

```gherkin
Then I not see the "h2" element in the "sidebar"
Then I should not see the "h2" element in the "sidebar" region

```

</details>

<details>
  <summary><code>@Then I( should) see :text in the :tag element in the :region( region)</code></summary>

<br/>
Assert text in an element within a region. 
<br/><br/>

```gherkin
Then I see "Welcome" in the "h2" element in the "content"
Then I should see "Welcome" in the "h2" element in the "content" region

```

</details>

<details>
  <summary><code>@Then I( should) not see :text in the :tag element in the :region( region)</code></summary>

<br/>
Assert text is not in an element within a region. 
<br/><br/>

```gherkin
Then I not see "Error" in the "div" element in the "content"
Then I should not see "Error" in the "div" element in the "content" region

```

</details>

<details>
  <summary><code>@Then I( should) see the :tag element with the :attribute attribute set to :value in the :region( region)</code></summary>

<br/>
Assert an element with a specific attribute value exists in a region. 
<br/><br/>

```gherkin
Then I see the "a" element with the "href" attribute set to "/about" in the "footer"
Then I should see the "a" element with the "href" attribute set to "/about" in the "footer" region

```

</details>

<details>
  <summary><code>@Then I( should) see :text in the :tag element with the :attribute attribute set to :value in the :region( region)</code></summary>

<br/>
Assert text in an element with a specific attribute value in a region. 
<br/><br/>

```gherkin
Then I see "About" in the "a" element with the "href" attribute set to "/about" in the "footer"
Then I should see "About" in the "a" element with the "href" attribute set to "/about" in the "footer" region

```

</details>

<details>
  <summary><code>@Then I( should) see :text in the :tag element with the :property CSS property set to :value in the :region( region)</code></summary>

<br/>
Assert text in an element with a specific CSS property value in a region. 
<br/><br/>

```gherkin
Then I see "Notice" in the "div" element with the "color" CSS property set to "red" in the "content"
Then I should see "Notice" in the "div" element with the "color" CSS property set to "red" in the "content" region

```

</details>

## MessageContext

[Source](src/Drupal/DrupalExtension/Context/MessageContext.php)

>  Provides step-definitions for interacting with Drupal messages.


<details>
  <summary><code>@Given I should not see the error message( containing) :message</code></summary>

<br/>
Checks if the current page does not contain the given error message. 
<br/><br/>

```gherkin
  Given I should not see the error message "Access denied"
  Given I should not see the error message containing "Access"

```

</details>

<details>
  <summary><code>@Given I should not see the success message( containing) :message</code></summary>

<br/>
Checks if the current page does not contain the given set of success message. 
<br/><br/>

```gherkin
  Given I should not see the success message "saved"
  Given I should not see the success message containing "saved"

```

</details>

<details>
  <summary><code>@Given I should not see the warning message( containing) :message</code></summary>

<br/>
Checks if the current page does not contain the given set of warning message. 
<br/><br/>

```gherkin
  Given I should not see the warning message "deprecated"
  Given I should not see the warning message containing "deprecated"

```

</details>

<details>
  <summary><code>@Then I should see the error message( containing) :message</code></summary>

<br/>
Checks if the current page contains the given error message. 
<br/><br/>

```gherkin
  Then I should see the error message "Username is required"
  Then I should see the error message containing "Username"

```

</details>

<details>
  <summary><code>@Then I should see the following error message(s):</code></summary>

<br/>
Checks if the current page contains the given set of error messages. 
<br/><br/>

```gherkin
  Then I should see the following error messages:
    | error messages         |
    | Username is required   |
    | Password is required   |

```

</details>

<details>
  <summary><code>@Then I should not see the following error messages:</code></summary>

<br/>
Checks if the current page does not contain the given set error messages. 
<br/><br/>

```gherkin
  Then I should not see the following error messages:
    | error messages |
    | Access denied  |

```

</details>

<details>
  <summary><code>@Then I should see the success message( containing) :message</code></summary>

<br/>
Checks if the current page contains the given success message. 
<br/><br/>

```gherkin
  Then I should see the success message "Article has been created"
  Then I should see the success message containing "created"

```

</details>

<details>
  <summary><code>@Then I should see the following success messages:</code></summary>

<br/>
Checks if the current page contains the given set of success messages. 
<br/><br/>

```gherkin
  Then I should see the following success messages:
    | success messages        |
    | Article has been created |

```

</details>

<details>
  <summary><code>@Then I should not see the following success messages:</code></summary>

<br/>
Checks if the current page does not contain the given set of success messages. 
<br/><br/>

```gherkin
  Then I should not see the following success messages:
    | success messages |
    | Changes saved    |

```

</details>

<details>
  <summary><code>@Then I should see the warning message( containing) :message</code></summary>

<br/>
Checks if the current page contains the given warning message. 
<br/><br/>

```gherkin
  Then I should see the warning message "This action cannot be undone"
  Then I should see the warning message containing "cannot be undone"

```

</details>

<details>
  <summary><code>@Then I should see the following warning messages:</code></summary>

<br/>
Checks if the current page contains the given set of warning messages. 
<br/><br/>

```gherkin
  Then I should see the following warning messages:
    | warning messages                |
    | This action cannot be undone    |

```

</details>

<details>
  <summary><code>@Then I should not see the following warning messages:</code></summary>

<br/>
Checks if the current page does not contain the given set of warning messages. 
<br/><br/>

```gherkin
  Then I should not see the following warning messages:
    | warning messages |
    | deprecated       |

```

</details>

<details>
  <summary><code>@Then I should see the message( containing) :message</code></summary>

<br/>
Checks if the current page contain the given message. 
<br/><br/>

```gherkin
  Then I should see the message "Changes saved"
  Then I should see the message containing "saved"

```

</details>

<details>
  <summary><code>@Then I should not see the message( containing) :message</code></summary>

<br/>
Checks if the current page does not contain the given message. 
<br/><br/>

```gherkin
  Then I should not see the message "Access denied"
  Then I should not see the message containing "denied"

```

</details>

## MinkContext

[Source](src/Drupal/DrupalExtension/Context/MinkContext.php)

>  Extensions to the Mink Extension.


<details>
  <summary><code>@Given I am at :path
@When I visit :path</code></summary>

<br/>
Visit a given path, and additionally check for HTTP response code 200. 
<br/><br/>

```gherkin
Given I am at "/node/1"
When I visit "/node/1"

```

</details>

<details>
  <summary><code>@Given for :field I enter :value
@Given I enter :value for :field</code></summary>

<br/>
Enter a value into a form field. 
<br/><br/>

```gherkin
Given for "Title" I enter "My article"
Given I enter "My article" for "Title"

```

</details>

<details>
  <summary><code>@Given I wait for AJAX to finish</code></summary>

<br/>
Wait for AJAX to finish. 
<br/><br/>

```gherkin
Given I wait for AJAX to finish

```

</details>

<details>
  <summary><code>@Given I press the :char key in the :field field</code></summary>

<br/>
Press a key in a form field. 
<br/><br/>

```gherkin
  Given I press the "enter" key in the "Search" field

```

</details>

<details>
  <summary><code>@Given I press :button in the :region( region)</code></summary>

<br/>
Checks if a button with id|name|title|alt|value exists or not and presses the same. 
<br/><br/>

```gherkin
Given I press "Submit" in the "sidebar"
Given I press "Submit" in the "sidebar" region

```

</details>

<details>
  <summary><code>@Given I fill in :value for :field in the :region( region)
@Given I fill in :field with :value in the :region( region)</code></summary>

<br/>
Fills in a form field with id|name|title|alt|value in the specified region. 
<br/><br/>

```gherkin
Given I fill in "test" for "Search" in the "header"
Given I fill in "test" for "Search" in the "header" region
Given I fill in "Search" with "test" in the "header" region

```

</details>

<details>
  <summary><code>@Given I check :locator in the :region( region)</code></summary>

<br/>
Checks if a checkbox with id|name|title|alt|value exists or not and checks the same. 
<br/><br/>

```gherkin
Given I check "Published" in the "content"
Given I check "Published" in the "content" region

```

</details>

<details>
  <summary><code>@Given I uncheck :checkbox in the :region( region)</code></summary>

<br/>
Checks if a checkbox with id|name|title|alt|value exists or not and unchecks the same. 
<br/><br/>

```gherkin
Given I uncheck "Promoted" in the "content"
Given I uncheck "Promoted" in the "content" region

```

</details>

<details>
  <summary><code>@Given I check the box :checkbox</code></summary>

<br/>
Check a checkbox. 
<br/><br/>

```gherkin
Given I check the box "Published"

```

</details>

<details>
  <summary><code>@Given I uncheck the box :checkbox</code></summary>

<br/>
Uncheck a checkbox. 
<br/><br/>

```gherkin
Given I uncheck the box "Promoted to front page"

```

</details>

<details>
  <summary><code>@When I click :link</code></summary>

<br/>
Click a link by its text. 
<br/><br/>

```gherkin
When I click "Read more"

```

</details>

<details>
  <summary><code>@When I press the :button button</code></summary>

<br/>
Presses button with specified id|name|title|alt|value. 
<br/><br/>

```gherkin
When I press the "Save" button

```

</details>

<details>
  <summary><code>@When I follow/click :link in the :region( region)</code></summary>

<br/>
Follow a link in a specific region. 
<br/><br/>

```gherkin
When I follow "Read more" in the "content"
When I follow "Read more" in the "content" region
When I click "Read more" in the "content" region

```

</details>

<details>
  <summary><code>@When I select the radio button :label with the id :id
@When I select the radio button :label</code></summary>

<br/>
Select a radio button. 
<br/><br/>

```gherkin
When I select the radio button "Full HTML"
When I select the radio button "Full HTML" with the id "edit-format-full-html"

```

</details>

<details>
  <summary><code>@When I :action details labelled :summary</code></summary>

<br/>
Expand/collapse/toggle a <details> element by <summary> text. 
<br/><br/>

```gherkin
When I expand details labelled "Advanced settings"
When I collapse details labelled "Advanced settings"
When I click details labelled "Advanced settings"

```

</details>

<details>
  <summary><code>@Then I should see the link :link</code></summary>

<br/>
Assert a link is visible on the page. 
<br/><br/>

```gherkin
Then I should see the link "Log out"

```

</details>

<details>
  <summary><code>@Then I should not see the link :link</code></summary>

<br/>
Links are not loaded on the page. 
<br/><br/>

```gherkin
Then I should not see the link "Log out"

```

</details>

<details>
  <summary><code>@Then I should not visibly see the link :link</code></summary>

<br/>
Links are loaded but not visually visible (e.g they have display: hidden applied). 
<br/><br/>

```gherkin
Then I should not visibly see the link "Skip to main content"

```

</details>

<details>
  <summary><code>@Then I (should )see the heading :heading</code></summary>

<br/>
Assert a heading is visible on the page. 
<br/><br/>

```gherkin
Then I see the heading "Welcome"
Then I should see the heading "Welcome"

```

</details>

<details>
  <summary><code>@Then I (should )not see the heading :heading</code></summary>

<br/>
Assert a heading is not on the page. 
<br/><br/>

```gherkin
Then I not see the heading "Error"
Then I should not see the heading "Error"

```

</details>

<details>
  <summary><code>@Then I (should ) see the button :button
@Then I (should ) see the :button button</code></summary>

<br/>
Assert a button is visible on the page. 
<br/><br/>

```gherkin
Then I see the button "Save"
Then I should see the button "Save"
Then I should see the "Save" button

```

</details>

<details>
  <summary><code>@Then I should not see the button :button
@Then I should not see the :button button</code></summary>

<br/>
Assert a button is not on the page. 
<br/><br/>

```gherkin
Then I should not see the button "Delete"
Then I should not see the "Delete" button

```

</details>

<details>
  <summary><code>@Then I should see the heading :heading in the :region( region)
@Then I should see the :heading heading in the :region( region)</code></summary>

<br/>
Find a heading in a specific region. 
<br/><br/>

```gherkin
Then I should see the heading "Latest news" in the "sidebar"
Then I should see the heading "Latest news" in the "sidebar" region
Then I should see the "Latest news" heading in the "sidebar" region

```

</details>

<details>
  <summary><code>@Then I should see the link :link in the :region( region)</code></summary>

<br/>
Assert a link exists in a region. 
<br/><br/>

```gherkin
Then I should see the link "About us" in the "footer"
Then I should see the link "About us" in the "footer" region

```

</details>

<details>
  <summary><code>@Then I should not see the link :link in the :region( region)</code></summary>

<br/>
Assert a link does not exist in a region. 
<br/><br/>

```gherkin
Then I should not see the link "Admin" in the "footer"
Then I should not see the link "Admin" in the "footer" region

```

</details>

<details>
  <summary><code>@Then I should see( the text) :text in the :region( region)</code></summary>

<br/>
Assert text is visible in a region. 
<br/><br/>

```gherkin
Then I should see "Welcome" in the "content"
Then I should see "Welcome" in the "content" region
Then I should see the text "Welcome" in the "content" region

```

</details>

<details>
  <summary><code>@Then I should not see( the text) :text in the :region( region)</code></summary>

<br/>
Assert text is not visible in a region. 
<br/><br/>

```gherkin
Then I should not see "Error" in the "content"
Then I should not see "Error" in the "content" region
Then I should not see the text "Error" in the "content" region

```

</details>

<details>
  <summary><code>@Then I (should )see the text :text</code></summary>

<br/>
Assert text is visible on the page. 
<br/><br/>

```gherkin
Then I see the text "Welcome to Drupal"
Then I should see the text "Welcome to Drupal"

```

</details>

<details>
  <summary><code>@Then I should not see the text :text</code></summary>

<br/>
Assert text is not visible on the page. 
<br/><br/>

```gherkin
Then I should not see the text "Access denied"

```

</details>

<details>
  <summary><code>@Then I should get a :code HTTP response</code></summary>

<br/>
Assert the HTTP response code. 
<br/><br/>

```gherkin
Then I should get a 200 HTTP response

```

</details>

<details>
  <summary><code>@Then I should not get a :code HTTP response</code></summary>

<br/>
Assert the HTTP response code is not a specific value. 
<br/><br/>

```gherkin
Then I should not get a 403 HTTP response

```

</details>




[//]: # (END)
