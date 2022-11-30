# Contributing

Features and bug fixes are welcome! First-time contributors can jump in with the issues tagged [good first issue](https://github.com/jhedstrom/drupalextension/issues?q=is%3Aissue+is%3Aopen+label%3A%22good+first+issue%22).

## Testing

Testing is performed automatically in Github Actions when a PR is submitted. To execute tests locally before submitting a PR, you'll need [Docker and Docker Compose](https://docs.docker.com/engine/install/).

Configure your local test environment:
```
export PHP_VERSION=8.1
export DRUPAL_VERSION=10
export DOCKER_USER_ID=${UID}
```

Prepare your local environment for testing:
```
docker-compose up -d
docker-compose exec -T -u node node npm install
docker-compose exec -T php composer self-update
docker-compose exec -u ${DOCKER_USER_ID} -T php composer require --no-interaction --dev --no-update drupal/core:^${DRUPAL_VERSION} drupal/core-composer-scaffold:^${DRUPAL_VERSION}
docker-compose exec -T php composer install
docker-compose exec -T php ./vendor/bin/drush --yes --root=drupal site-install --db-url=mysql://drupal:drupal@db/drupal --debug
docker-compose exec -T php cp -r fixtures/drupal/modules/behat_test drupal/modules
docker-compose exec -T php ./vendor/bin/drush --yes --root=drupal pmu page_cache
docker-compose exec -T php ./vendor/bin/drush --yes --root=drupal en behat_test
```

Execute NPM linters:
```
docker-compose exec -T -u node node npm test
```

Execute PHP checks:
```
docker-compose exec -T php composer test
```

Execute Behat tests:
```
docker-compose exec -T php vendor/bin/behat -fprogress --strict
docker-compose exec -T php vendor/bin/behat -fprogress --profile=drupal --strict
```

Execute specific tests, eg just PHPUnit's Drupal7FieldHandlerTest:
```
docker-compose exec -T php vendor/bin/behat -fprogress --profile=drupal --strict --tags=@random
```

## Before submitting a change

- Check the changes from `composer require` are not included in your submitted PR.
- Before testing another PHP or Drupal version, revert changes to `composer.json` and remove `composer.lock`, `package-lock.json`, `drupal/`, and `vendor/`.
