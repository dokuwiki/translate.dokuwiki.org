DokuWiki Translation Tool
=========================

This tool provides a web based tool to create and update translations for DokuWiki and it's Plug-Ins.

Source is availiable at https://github.com/dokufreaks/dokuwiki-translation
The DokuWiki installation availiable at http://translate.dokuwiki.org/

Development documentation available at https://github.com/dokufreaks/dokuwiki-translation/wiki

Configuration
-----

Copy app/config/parameters.yml.dist to app/config/parameters.yml and setup the configuration.
Ensure you have a proper ssh key to your github account configured (no passphrase).

http://sampreshan.svashishtha.com/2012/05/20/quicktip-github-multiple-accounts-access-with-ssh/

Production setup
----------------

    composer install
    php app/console cache:clear --env=prod
    php app/console assetic:dump --env=prod
    php app/console doctrine:database:create
    php app/console doctrine:schema:update --force
    php app/console dokuwiki:setup

Point the document root to the web/ folder. The document index is app.php.

Development setup
-----------------

    composer install --dev
    php app/console cache:clear
    php app/console assetic:dump --env=dev
    php app/console doctrine:database:create
    php app/console doctrine:schema:update --force
    php app/console dokuwiki:setup

The the website is availible at web/app_dev.php

To run the Unittests you need to have PHPUnit installed and the application configured. Run the tests with:

    vendor/bin/phpunit -c app

CronJobs
--------
You need to setup two cronjobs, the commands are:

To update plugin information from the DokuWiki plugin repository. This command should run at least once per day.

    php app/console dokuwiki:updateDwApi

Keep track of plugin updates and process new translations. This command should run about every 5min.

    php app/console dokuwiki:updateGit

Admin commands
-----------------------
The following Symfony commands are additionally availiable via commandline.

Add a repository:

    php app/console dokuwiki:add

Delete a repository:

    php app/console dokuwiki:deleteRepo

Reset the local information and git repository of a plugin:

    php app/console dokuwiki:softReset
