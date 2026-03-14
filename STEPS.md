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
  <summary><code>@Given /^I wait for the batch job to finish$/</code></summary>

<br/>
Wait for the Batch API to finish
<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Given there is an item in the system queue:</code></summary>

<br/>
Creates a queue item. Defaults inputs if none are available
<br/><br/>

```gherkin

```

</details>

## ConfigContext

[Source](src/Drupal/DrupalExtension/Context/ConfigContext.php), [Example](tests/behat/features/config.feature)

>  Provides pre-built step definitions for interacting with Drupal config.


<details>
  <summary><code>@Given I set the configuration item :name with key :key to :value</code></summary>

<br/>
Sets basic configuration item
<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Given I set the configuration item :name with key :key with values:</code></summary>

<br/>
Sets complex configuration
<br/><br/>

```gherkin

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

<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Given I am logged in as a user with the :role role(s)
@Given I am logged in as a/an :role</code></summary>

<br/>
Creates and authenticates a user with the given role(s)
<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Given I am logged in as a user with the :role role(s) and I have the following fields:</code></summary>

<br/>
Creates and authenticates a user with the given role(s) and given fields
<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Given I am logged in as :name</code></summary>

<br/>

<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Given I am logged in as a user with the :permissions permission(s)</code></summary>

<br/>

<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Given I click :link in the :rowText row
@Then I (should )see the :link in the :rowText row</code></summary>

<br/>
Attempts to find a link in a table row containing giving text. This is for
<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Given I press :button in the :rowText row</code></summary>

<br/>
Attempts to find a button in a table row containing giving text. This is
<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Given the cache has been cleared</code></summary>

<br/>

<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Given I run cron</code></summary>

<br/>

<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Given I am viewing a/an :type (content )with the title :title
@Given a/an :type (content )with the title :title</code></summary>

<br/>
Creates content of the given type
<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Given I am viewing my :type (content )with the title :title</code></summary>

<br/>
Creates content authored by the current user
<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Given :type content:</code></summary>

<br/>
Creates content of a given type provided in the form:
<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Given I am viewing a/an :type( content):</code></summary>

<br/>
Creates content of the given type, provided in the form:
<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Given I am viewing a/an :vocabulary term with the name :name
@Given a/an :vocabulary term with the name :name</code></summary>

<br/>
Creates a term on an existing vocabulary
<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Given users:</code></summary>

<br/>
Creates multiple users
<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Given :vocabulary terms:</code></summary>

<br/>
Creates one or more terms on an existing vocabulary
<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Given the/these (following )languages are available:</code></summary>

<br/>
Creates one or more languages
<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Then I should see (the text ):text in the :rowText row</code></summary>

<br/>
Find text in a table row containing given text
<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Then I should not see (the text ):text in the :rowText row</code></summary>

<br/>
Asset text not in a table row containing given text
<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Then I should be able to edit a/an :type( content)</code></summary>

<br/>
Asserts that a given content type is editable
<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Then (I )break</code></summary>

<br/>
Pauses the scenario until the user presses a key. Useful when debugging a scenario
<br/><br/>

```gherkin

```

</details>

## DrushContext

[Source](src/Drupal/DrupalExtension/Context/DrushContext.php), [Example](tests/behat/features/drush.feature)

>  Provides step definitions for interacting directly with Drush commands.


<details>
  <summary><code>@Given I run drush :command</code></summary>

<br/>

<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Given I run drush :command :arguments</code></summary>

<br/>

<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Then drush output should contain :output</code></summary>

<br/>

<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Then drush output should match :regex</code></summary>

<br/>

<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Then drush output should not contain :output</code></summary>

<br/>

<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Then print last drush output</code></summary>

<br/>

<br/><br/>

```gherkin

```

</details>

## MailContext

[Source](src/Drupal/DrupalExtension/Context/MailContext.php), [Example](tests/behat/features/mail.feature)

>  Provides pre-built step definitions for interacting with mail.


<details>
  <summary><code>@When Drupal sends a/an (e)mail:</code></summary>

<br/>
This is mainly useful for testing this context
<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@When I follow the link to :urlFragment from the (e)mail
@When I follow the link to :urlFragment from the (e)mail to :to
@When I follow the link to :urlFragment from the (e)mail with the subject :subject
@When I follow the link to :urlFragment from the (e)mail to :to with the subject :subject</code></summary>

<br/>

<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Then (a )(an )(e)mail(s) has/have been sent:
@Then (a )(an )(e)mail(s) has/have been sent to :to:
@Then (a )(an )(e)mail(s) has/have been sent with the subject :subject:
@Then (a )(an )(e)mail(s) has/have been sent to :to with the subject :subject:</code></summary>

<br/>
Check all mail sent during the scenario
<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Then (a )(an )new (e)mail(s) is/are sent:
@Then (a )(an )new (e)mail(s) is/are sent to :to:
@Then (a )(an )new (e)mail(s) is/are sent with the subject :subject:
@Then (a )(an )new (e)mail(s) is/are sent to :to with the subject :subject:</code></summary>

<br/>
Check mail sent since the last step that checked mail
<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Then :count (e)mail(s) has/have been sent
@Then :count (e)mail(s) has/have been sent to :to
@Then :count (e)mail(s) has/have been sent with the subject :subject
@Then :count (e)mail(s) has/have been sent to :to with the subject :subject</code></summary>

<br/>
Check all mail sent during the scenario
<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Then :count new (e)mail(s) is/are sent
@Then :count new (e)mail(s) is/are sent to :to
@Then :count new (e)mail(s) is/are sent with the subject :subject
@Then :count new (e)mail(s) is/are sent to :to with the subject :subject</code></summary>

<br/>
Check mail sent since the last step that checked mail
<br/><br/>

```gherkin

```

</details>

## MarkupContext

[Source](src/Drupal/DrupalExtension/Context/MarkupContext.php), [Example](tests/behat/features/markup.feature)

>  Extensions to the Mink Extension.


<details>
  <summary><code>@Then I should see the button :button in the :region( region)
@Then I should see the :button button in the :region( region)</code></summary>

<br/>
Checks if a button with id|name|title|alt|value exists in a region
<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Then I should not see the button :button in the :region( region)
@Then I should not see the :button button in the :region( region)</code></summary>

<br/>
Asserts that a button does not exists in a region
<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Then I( should) see the :tag element in the :region( region)</code></summary>

<br/>

<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Then I( should) not see the :tag element in the :region( region)</code></summary>

<br/>

<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Then I( should) see :text in the :tag element in the :region( region)</code></summary>

<br/>

<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Then I( should) not see :text in the :tag element in the :region( region)</code></summary>

<br/>

<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Then I( should) see the :tag element with the :attribute attribute set to :value in the :region( region)</code></summary>

<br/>

<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Then I( should) see :text in the :tag element with the :attribute attribute set to :value in the :region( region)</code></summary>

<br/>

<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Then I( should) see :text in the :tag element with the :property CSS property set to :value in the :region( region)</code></summary>

<br/>

<br/><br/>

```gherkin

```

</details>

## MessageContext

[Source](src/Drupal/DrupalExtension/Context/MessageContext.php)

>  Provides step-definitions for interacting with Drupal messages.


<details>
  <summary><code>@Given I should not see the error message( containing) :message</code></summary>

<br/>
Checks if the current page does not contain the given error message
<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Given I should not see the success message( containing) :message</code></summary>

<br/>
Checks if the current page does not contain the given set of success message
<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Given I should not see the warning message( containing) :message</code></summary>

<br/>
Checks if the current page does not contain the given set of warning message
<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Then I should see the error message( containing) :message</code></summary>

<br/>
Checks if the current page contains the given error message
<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Then I should see the following error message(s):</code></summary>

<br/>
Checks if the current page contains the given set of error messages
<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Then I should not see the following error messages:</code></summary>

<br/>
Checks if the current page does not contain the given set error messages
<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Then I should see the success message( containing) :message</code></summary>

<br/>
Checks if the current page contains the given success message
<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Then I should see the following success messages:</code></summary>

<br/>
Checks if the current page contains the given set of success messages
<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Then I should not see the following success messages:</code></summary>

<br/>
Checks if the current page does not contain the given set of success messages
<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Then I should see the warning message( containing) :message</code></summary>

<br/>
Checks if the current page contains the given warning message
<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Then I should see the following warning messages:</code></summary>

<br/>
Checks if the current page contains the given set of warning messages
<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Then I should not see the following warning messages:</code></summary>

<br/>
Checks if the current page does not contain the given set of warning messages
<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Then I should see the message( containing) :message</code></summary>

<br/>
Checks if the current page contain the given message
<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Then I should not see the message( containing) :message</code></summary>

<br/>
Checks if the current page does not contain the given message
<br/><br/>

```gherkin

```

</details>

## MinkContext

[Source](src/Drupal/DrupalExtension/Context/MinkContext.php)

>  Extensions to the Mink Extension.


<details>
  <summary><code>@Given I am at :path
@When I visit :path</code></summary>

<br/>
Visit a given path, and additionally check for HTTP response code 200
<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Given for :field I enter :value
@Given I enter :value for :field</code></summary>

<br/>

<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Given I wait for AJAX to finish</code></summary>

<br/>
Wait for AJAX to finish
<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Given I press the :char key in the :field field</code></summary>

<br/>
@param mixed $char could be either char ('b') or char-code (98)
<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Given I press :button in the :region( region)</code></summary>

<br/>
Checks if a button with id|name|title|alt|value exists or not and presses the same
<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Given I fill in :value for :field in the :region( region)
@Given I fill in :field with :value in the :region( region)</code></summary>

<br/>
Fills in a form field with id|name|title|alt|value in the specified region
<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Given I check :locator in the :region( region)</code></summary>

<br/>
Checks if a checkbox with id|name|title|alt|value exists or not and checks the same
<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Given I uncheck :checkbox in the :region( region)</code></summary>

<br/>
Checks if a checkbox with id|name|title|alt|value exists or not and unchecks the same
<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Given I check the box :checkbox</code></summary>

<br/>

<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Given I uncheck the box :checkbox</code></summary>

<br/>

<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@When I click :link</code></summary>

<br/>

<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@When I press the :button button</code></summary>

<br/>
Presses button with specified id|name|title|alt|value
<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@When I follow/click :link in the :region( region)</code></summary>

<br/>
@throws \Exception
<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@When I select the radio button :label with the id :id
@When I select the radio button :label</code></summary>

<br/>
@TODO convert to mink extension
<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@When I :action details labelled :summary</code></summary>

<br/>
Expand/collapse/toggle a <details> element by <summary> text
<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Then I should see the link :link</code></summary>

<br/>

<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Then I should not see the link :link</code></summary>

<br/>
Links are not loaded on the page
<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Then I should not visibly see the link :link</code></summary>

<br/>
Links are loaded but not visually visible (e.g they have display: hidden applied)
<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Then I (should )see the heading :heading</code></summary>

<br/>

<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Then I (should )not see the heading :heading</code></summary>

<br/>

<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Then I (should ) see the button :button
@Then I (should ) see the :button button</code></summary>

<br/>

<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Then I should not see the button :button
@Then I should not see the :button button</code></summary>

<br/>

<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Then I should see the heading :heading in the :region( region)
@Then I should see the :heading heading in the :region( region)</code></summary>

<br/>
Find a heading in a specific region
<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Then I should see the link :link in the :region( region)</code></summary>

<br/>
@throws \Exception
<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Then I should not see the link :link in the :region( region)</code></summary>

<br/>
@throws \Exception
<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Then I should see( the text) :text in the :region( region)</code></summary>

<br/>
@throws \Exception
<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Then I should not see( the text) :text in the :region( region)</code></summary>

<br/>
@throws \Exception
<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Then I (should )see the text :text</code></summary>

<br/>

<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Then I should not see the text :text</code></summary>

<br/>

<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Then I should get a :code HTTP response</code></summary>

<br/>

<br/><br/>

```gherkin

```

</details>

<details>
  <summary><code>@Then I should not get a :code HTTP response</code></summary>

<br/>

<br/><br/>

```gherkin

```

</details>




[//]: # (END)
