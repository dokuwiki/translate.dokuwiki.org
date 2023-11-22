DokuWiki Translation Tool
=========================

This tool provides a web based tool to create and update translations for DokuWiki and it's plugins.

The DokuWiki installation available at https://translate.dokuwiki.org/

Source is available at https://github.com/dokufreaks/dokuwiki-translation  
Development documentation available at https://github.com/dokufreaks/dokuwiki-translation/wiki

Deployment: https://www.dokuwiki.org/teams:translate-tool


Configuration
-----

Copy .env to .env.local and setup the configuration.
The development version of the translation tool will create forks and perform pull requests to github.com. 
To prevent these temporary repositories mix with your normal repositories, you have to setup a second github account for testing purposes.
Ensure you have a proper ssh key to your github test account configured (no passphrase).

See for the details: https://github.com/dokufreaks/dokuwiki-translation/wiki/Git-interaction

Production setup
----------------
TODO - NOT TESTED

    composer update
    bin/console doctrine:migrations:migrate
    #if changed language objects
    bin/console dokuwiki:updateLanguages

Point the document root to the public/ folder. The document index is index.php.

Development setup
-----------------
TODO - NOT TESTED

If new:

    composer install --dev
    #not sure what to do if new setup, NOT TESTED
    #php app/console doctrine:database:create
    #php app/console doctrine:schema:update --force
    bin/console doctrine:migrations:migrate
    bin/console dokuwiki:setup

or existing:

    composer update --dev
    bin/console doctrine:migrations:migrate

The website is available at public/
or using symfony server: ....TODO

To create migrations, edit after creation to ensure proper handling existing data:

    bin/console doctrine:schema:diff

See for details: https://github.com/dokufreaks/dokuwiki-translation/wiki/Maintenance-development-notes


To run the Unittests you need to have PHPUnit installed (I expect that it is already done via composer) and the application configured. Run the tests with:

    bin/phpunit

From Intellij IDEA/PHPStorm:

Configure PHPUnit
  * Open File | Settings | Languages & Frameworks | PHP | Test Frameworks
  * Click the top-left "+" to add a test framework
  * When using Composer (you should)
  * Choose "Use Composer autoloader"
  * Set "Path to script" to /path/to/yourprojectdirectory/vendor/autoload.php
  * If not present, add KERNEL_CLASS='App\Kernel' to your .env.test file
  * Add .env.test.local with copy of .env.local. Copy all setting but the framework settings not.
  * Open previous settings (File | Settings | Languages & Frameworks | PHP | Test Frameworks)
  * Check "Default configuration file"
  * Set path to /path/to/yourprojectdirectory/phpunit.xml.dist


CronJobs
--------
You need to setup two cronjobs, the commands are:

To update plugin information from the DokuWiki plugin repository. This command should run at least once per day.

    bin/console dokuwiki:updateDwApi

Keep track of plugin updates and process new translations. This command should run about every 5 min.

    bin/console dokuwiki:updateGit

Admin commands
-----------------------
The following Symfony commands are additionally available via commandline.

Add a repository:

    bin/console dokuwiki:add <type> <name> <gitUrl> <branch> <email> [<englishReadonly>] [<displayName>] [<author>] [<popularity>]

Delete a repository:

    bin/console dokuwiki:deleteRepo <type> <name>

Edit an existing repository, show or set value for: giturl, branch, state, email, englishReadonly

    bin/console dokuwiki:editRepo <type> <name> <property> [<value>]

Show some info about the repositories:

    bin/console dokuwiki:showStats <type> <name>

Reset lock, tmp folder, error count and last updated of a core/plugin/template:

    bin/console dokuwiki:softReset <type> <name>

Updates all language information from local repository. Refreshes the cached translation objects

    bin/console dokuwiki:updateLanguages

General Symfony commands
-----------------------
Some useful command for listing and help

List possible commands:

    php app/console list

Help for a command:

    php app/console help dokuwiki:add
