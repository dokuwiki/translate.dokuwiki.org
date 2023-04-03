DokuWiki Translation Tool
=========================

This tool provides a web based tool to create and update translations for DokuWiki and it's plugins.

The DokuWiki installation available at https://translate.dokuwiki.org/

Source is available at https://github.com/dokufreaks/dokuwiki-translation  
Development documentation available at https://github.com/dokufreaks/dokuwiki-translation/wiki

Deployment: https://www.dokuwiki.org/teams:translate-tool

Configuration
-----

Copy app/config/parameters.yml.dist to app/config/parameters.yml and setup the configuration.
The development version of the translation tool will create forks and perform pull requests to github.com. 
To prevent these temporary repositories mix with your normal repositories, you have to setup a second github account for testing purposes.
Ensure you have a proper ssh key to your github test account configured (no passphrase).

http://sampreshan.svashishtha.com/2012/05/20/quicktip-github-multiple-accounts-access-with-ssh/

For your default GitHub account you can use the `Host github.com`, while using e.g. `Host translationtesting.github.com` 
for your test account. This last host should be configured in the parameters.yml of your local translation tool.

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

The website is available at web/app_dev.php

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
    
General Symfony commands
-----------------------
Some useful command for listing and help

List possible commands:

    php app/console list

Help for a command:

    php app/console help dokuwiki:add
