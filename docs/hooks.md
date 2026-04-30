# Hooks

In addition to the standard
[Behat hooks](https://docs.behat.org/en/latest/user_guide/context/hooks.html),
the Drupal Extension fires three hooks before it creates entities,
giving custom contexts a chance to alter the data first.

| Annotation | Fires before |
| --- | --- |
| `@beforeNodeCreate` | A node is saved by `DrupalContext`. |
| `@beforeTermCreate` | A taxonomy term is saved by `DrupalContext`. |
| `@beforeUserCreate` | A user is saved by `DrupalContext`. |

The hooks only fire under the [Drush](drivers/drush.md) and
[Drupal API](drivers/drupal-api.md) drivers - the Blackbox driver does
not create entities.

## EntityScope

Each hook receives a `Drupal\DrupalExtension\Hook\Scope\EntityScope`.
The most useful method on it is `getEntity()`, which returns the
entity stub the driver is about to save. Mutate the stub in place to
change the data that will be persisted.

## Example: rewrite a node body before save

```php
<?php

use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\DrupalExtension\Hook\Scope\EntityScope;

class CustomContext extends RawDrupalContext {

  /**
   * @beforeNodeCreate
   */
  public function alterNodeObject(EntityScope $scope) {
    $node = $scope->getEntity();

    if (!empty($node->body)) {
      $node->body = strtoupper($node->body);
    }
  }

}
```

## Example: derive a username from email

```php
<?php

use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\DrupalExtension\Hook\Scope\EntityScope;

class CustomContext extends RawDrupalContext {

  /**
   * @beforeUserCreate
   */
  public function deriveUsername(EntityScope $scope) {
    $user = $scope->getEntity();

    if (empty($user->name) && !empty($user->mail)) {
      $user->name = strstr($user->mail, '@', TRUE);
    }
  }

}
```

## Example: add a parent term

```php
<?php

use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\DrupalExtension\Hook\Scope\EntityScope;

class CustomContext extends RawDrupalContext {

  /**
   * @beforeTermCreate
   */
  public function setDefaultParent(EntityScope $scope) {
    $term = $scope->getEntity();

    if (empty($term->parent)) {
      $term->parent = 'Root term';
    }
  }

}
```

For the hook to run, your custom context must be declared in
`behat.yml`:

```yaml
default:
  suites:
    default:
      contexts:
        - Drupal\DrupalExtension\Context\DrupalContext
        - CustomContext
```
