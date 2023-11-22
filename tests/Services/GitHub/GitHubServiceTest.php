<?php

namespace App\Tests\Services\GitHub;

use App\Services\GitHub\GitHubService;
use App\Services\GitHub\GitHubServiceException;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use ReflectionObject;

class GitHubServiceTest extends TestCase {

    /**
     *
     * @param GitHubService $api
     * @param string $method
     * @param mixed ...$args
     * @return mixed
     *
     * @throws ReflectionException
     */
    private function callPrivateMethod(GitHubService $api, string $method, ...$args)
    {
        $reflection = new ReflectionObject($api);
        $method = $reflection->getMethod($method);
        $method->setAccessible(true);
        return $method->invoke($api, ...$args);
    }

    /**
     * @throws ReflectionException
     */
    public function testGetUsernameAndRepositoryFromURLWithHTTP() {
        $api = new GitHubService('', '', '', false);
        $result = $this->callPrivateMethod($api, 'getUsernameAndRepositoryFromURL',
            'https://github.com/dokuwiki/dokuwiki.git');
        $this->assertEquals(['dokuwiki', 'dokuwiki'], $result);

        $result = $this->callPrivateMethod($api, 'getUsernameAndRepositoryFromURL',
            'https://github.com/dom-mel/dokuwiki.git');
        $this->assertEquals(['dom-mel', 'dokuwiki'], $result);
    }

    public function dataProvider_getUsernameAndRepositoryFromURL() {
        return [
            ['git@github.com:dokuwiki/dokuwiki.git',      ['dokuwiki', 'dokuwiki']],
            ['git@github.com:dom-mel/dokuwiki.git',         ['dom-mel', 'dokuwiki']],
            ['git@sub.github.com:dokuwiki/dokuwiki.git',  ['dokuwiki', 'dokuwiki']],
            ['git@sub.github.com:dom-mel/dokuwiki.git',     ['dom-mel', 'dokuwiki']],
            ['git://github.com/dokuwiki/dokuwiki.git',    ['dokuwiki', 'dokuwiki']],
            ['git://github.com/dom-mel/dokuwiki.git',       ['dom-mel', 'dokuwiki']],
            ['https://github.com/dom-mel/dokuwiki.git',       ['dom-mel', 'dokuwiki']],
            ['https://github.com/dom-mel/dokuwiki.git',       ['dom-mel', 'dokuwiki']]
        ];
    }

    /**
     * @dataProvider dataProvider_getUsernameAndRepositoryFromURL
     *
     * @param $url
     * @param $expected
     *
     * @throws ReflectionException
     */
    public function testGetUsernameAndRepositoryFromURLWithGit($url, $expected) {
        $api = new GitHubService('', '', '', false);
        $result = $this->callPrivateMethod($api, 'getUsernameAndRepositoryFromURL', $url);
        $this->assertEquals($expected, $result);
    }

    /**
     * @throws ReflectionException
     */
    public function testGetUsernameAndRepositoryFromURLWithError() {
        $api = new GitHubService('', '', '', false);

        $this->expectException(GitHubServiceException::class);
        $this->callPrivateMethod($api, 'getUsernameAndRepositoryFromURL',
            'Wrong:dokuwiki/dokuwiki.git');

    }

    /**
     * @throws ReflectionException
     */
    public function testGetUsernameAndRepositoryFromURLWithErrorNoGitExtension() {
        $api = new GitHubService('', '', '', false);

        $this->expectException(GitHubServiceException::class);
        $this->callPrivateMethod($api, 'getUsernameAndRepositoryFromURL', 'https://github.com/Klap-in/dokuwiki-plugin-docnavigation');
    }

    /**
     * @throws ReflectionException
     */
    public function testGitHubUrlHack() {
        $api = new GitHubService('', '', 'github.com', false);
        $result = $this->callPrivateMethod($api, 'gitHubUrlHack','github.com something');
        $this->assertEquals('github.com something', $result);

        $api = new GitHubService('', '', 'some.github.com', false);
        $result = $this->callPrivateMethod($api, 'gitHubUrlHack','git@github.com');
        $this->assertEquals('git@some.github.com', $result);
    }

}
