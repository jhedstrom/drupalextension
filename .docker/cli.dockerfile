# hadolint global ignore=DL3018
ARG PHP_VERSION=8.3
FROM uselagoon/php-${PHP_VERSION}-cli-drupal:latest

RUN apk add --no-cache $PHPIZE_DEPS \
    && pecl install pcov \
    && docker-php-ext-enable pcov
