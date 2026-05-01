#!/usr/bin/env bash
##
# Provision the fixture Drupal site used by the Behat suite.
#
# Called from both 'ahoy provision' and the CI workflow. Assumes Composer
# dependencies are already installed at /app/vendor and the Drupal scaffold
# has populated /app/build/web.
#
# shellcheck disable=SC2015

set -e
[ -n "${PROVISION_DEBUG}" ] && set -x

LOCALDEV_URL="${LOCALDEV_URL:-http://drupalextension.docker.amazee.io}"

DRUSH=(/app/vendor/bin/drush --root=/app/build/web --uri="${LOCALDEV_URL}" --yes)

echo "==> Provisioning fixture Drupal site at ${LOCALDEV_URL}."

echo "  > Copying Drush configuration."
mkdir -p /app/build/drush
cp -Rf /app/tests/behat/fixtures/drupal/drush/. /app/build/drush/

echo "  > Copying 'behat_test' module."
mkdir -p /app/build/web/modules
cp -Rf /app/tests/behat/fixtures/drupal/modules/behat_test /app/build/web/modules/

echo "  > Removing any leftover settings.php so 'site-install' can rewrite it."
chmod -f u+w /app/build/web/sites/default 2>/dev/null || true
chmod -f u+w /app/build/web/sites/default/settings.php 2>/dev/null || true
rm -f /app/build/web/sites/default/settings.php

echo "  > Dropping any existing database tables."
"${DRUSH[@]}" sql-drop 2>/dev/null || true

echo "  > Installing site."
"${DRUSH[@]}" site-install --db-url=mysql://drupal:drupal@mariadb:3306/drupal

echo "  > Enabling 'behat_test' module."
"${DRUSH[@]}" en behat_test

echo "  > Running 'deploy:hook'."
"${DRUSH[@]}" deploy:hook

echo "==> Provisioning finished."
