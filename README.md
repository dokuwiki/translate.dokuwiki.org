= DokuWiki Translation Tool

This tool provides a web based tool to create and update translations for DokuWiki and it's Plug-Ins.

== Setup

copy app/config/parameters.yml.dist to app/config/parameters.yml and setup the configuration

=== Production setup

  composer install
  php app/console cache:clear --env=prod
  php app/console assetic:dump
  php app/console doctrine:database:create
  php app/console doctrine:schema:update --force
  php app/console dokuwiki:setup

Point the document root to the web/ folder. The document index is app.php.

=== Development Tests

  composer install --dev
  php app/console cache:clear
  php app/console assetic:dump --env=dev
  php app/console doctrine:database:create
  php app/console doctrine:schema:update --force
  php app/console dokuwiki:setup

The the website is availible at web/app_dev.php

Run tests:
  vendor/bin/phpunit -c app