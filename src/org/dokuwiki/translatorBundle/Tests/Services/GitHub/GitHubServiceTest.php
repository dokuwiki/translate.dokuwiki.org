<?php

namespace org\dokuwiki\translatorBundle\Services\GitHub;

class GitHubServiceTest extends \PHPUnit_Framework_TestCase {

    function testGetUsernameAndRepositoryFromURLWithHTTP() {
        $api = new GitHubServiceImpl('', '', '', false);

        $result = $api->getUsernameAndRepositoryFromURL('https://github.com/splitbrain/dokuwiki.git');
        $this->assertEquals(array('splitbrain', 'dokuwiki'), $result);
        $result = $api->getUsernameAndRepositoryFromURL('https://github.com/dom-mel/dokuwiki.git');
        $this->assertEquals(array('dom-mel', 'dokuwiki'), $result);
    }

    function testGetUsernameAndRepositoryFromURLWithGit() {
        $api = new GitHubServiceImpl('', '', '', false);

        $result = $api->getUsernameAndRepositoryFromURL('git@github.com:splitbrain/dokuwiki.git');
        $this->assertEquals(array('splitbrain', 'dokuwiki'), $result);

        $result = $api->getUsernameAndRepositoryFromURL('git@github.com:dom-mel/dokuwiki.git');
        $this->assertEquals(array('dom-mel', 'dokuwiki'), $result);

        $result = $api->getUsernameAndRepositoryFromURL('git@sub.github.com:splitbrain/dokuwiki.git');
        $this->assertEquals(array('splitbrain', 'dokuwiki'), $result);

        $result = $api->getUsernameAndRepositoryFromURL('git@sub.github.com:dom-mel/dokuwiki.git');
        $this->assertEquals(array('dom-mel', 'dokuwiki'), $result);

        $result = $api->getUsernameAndRepositoryFromURL('git://github.com/splitbrain/dokuwiki.git');
        $this->assertEquals(array('splitbrain', 'dokuwiki'), $result);

        $result = $api->getUsernameAndRepositoryFromURL('git://github.com/dom-mel/dokuwiki.git');
        $this->assertEquals(array('dom-mel', 'dokuwiki'), $result);
    }

    function testGetUsernameAndRepositoryFromURLWithError() {
        $api = new GitHubServiceImpl('', '', '', false);

        $this->setExpectedException('org\dokuwiki\translatorBundle\Services\GitHub\GitHubServiceException');
        $api->getUsernameAndRepositoryFromURL('Wrong:splitbrain/dokuwiki.git');
    }

    function testGitHubUrlHack() {
        $api = new GitHubServiceImpl('', '', 'github.com', false);
        $this->assertEquals('github.com something', $api->gitHubUrlHack('github.com something'));

        $api = new GitHubServiceImpl('', '', 'some.github.com', false);
        $this->assertEquals('git@some.github.com', $api->gitHubUrlHack('git@github.com'));
    }

}
