# Blackbox driver

The Blackbox driver assumes no privileged access to the site under
test. Behat can run on the same machine as Drupal or on a completely
separate host - all interaction happens through the user interface
over HTTP.

## Enable the driver

The Blackbox driver is enabled with the `blackbox: ~` line in your
`Drupal\DrupalExtension` configuration:

```yaml
default:
  suites:
    default:
      contexts:
        - FeatureContext
        - Drupal\DrupalExtension\Context\DrupalContext
        - Drupal\DrupalExtension\Context\MinkContext
  extensions:
    Drupal\MinkExtension:
      browserkit_http: ~
      base_url: http://example.org/
    Drupal\DrupalExtension:
      blackbox: ~
```

Scenarios with no tag (and no `api_driver` set) use the Blackbox
driver.

## Region steps

The Blackbox driver supports a `regions` map that lets you reference
parts of the page by name instead of CSS selectors. This makes
scenarios readable without writing custom PHP.

```yaml
Drupal\DrupalExtension:
  blackbox: ~
  regions:
    header: '#header'
    content: '#main'
    footer: '#footer'
```

Once mapped, you can write:

```gherkin
When I press "Search" in the "header" region
And I fill in "a value" for "a field" in the "content" region
And I click "About us" in the "footer" region
```

Other examples that work with a configured region map:

```gherkin
Scenario: Test the ability to find a heading in a region
  Given I am on the homepage
  When I click "Download & Extend"
  Then I should see the heading "Core" in the "content" region

Scenario: Submit a form in a region
  Given I am on the homepage
  When I fill in "Search" with "Views" in the "right header" region
  And I press "Search" in the "right header" region
  Then I should see the text "Search again" in the "right sidebar" region

Scenario: Find an element in a region
  Given I am on the homepage
  Then I should see the "h1" element in the "left header"

Scenario: Find an element with an attribute in a region
  Given I am on the homepage
  Then I should see the "h1" element with the "id" attribute set to "site-name" in the "left header" region
```

> Region steps fail with a clear error if the region name is not
> defined in `regions`.

## Message selectors

The Drupal Extension recognises three message types - generic,
error, and success. The default selectors target Drupal's standard
markup. If your theme renders messages differently, override them:

```yaml
Drupal\DrupalExtension:
  selectors:
    message_selector: '.messages'
    error_message_selector: '.messages.messages-error'
    success_message_selector: '.messages.messages-status'
```

Message-related steps available with the Blackbox driver:

```gherkin
Scenario: Error messages
  Given I am on "/user"
  When I press "Log in"
  Then I should see the error message "Password field is required"
  And I should not see the error message "Sorry, unrecognized username or password"
  And I should see the following error messages:
    | error messages             |
    | Username field is required |
    | Password field is required |

Scenario: Status messages
  Given I am on "/user/register"
  When I press "Create new account"
  Then I should see the message "Username field is required"
  But I should not see the message "Registration successful. You are now logged in"
```

## Override text strings

Several built-in steps depend on default English strings such as
"Log in", "Log out", and standard Drupal URLs. Override them when your
site uses different labels or paths:

```yaml
Drupal\DrupalExtension:
  text:
    login_url: '/user'
    logout_url: '/user/logout'
    logout_confirm_url: '/user/logout/confirm'
    log_out: 'Sign out'
    log_in: 'Sign in'
    password_field: 'Enter your password'
    username_field: 'Nickname'
```

## Limitations

The Blackbox driver cannot create users, nodes, or taxonomy terms - it
has no privileged access to Drupal. For data-creation steps, use the
[Drush driver](drush.md) or the [Drupal API driver](drupal-api.md).
