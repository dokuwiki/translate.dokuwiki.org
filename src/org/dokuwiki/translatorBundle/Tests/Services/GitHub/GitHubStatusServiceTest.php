<?php

namespace org\dokuwiki\translatorBundle\Services\GitHub;

class GitHubStatusServiceExtend extends GitHubStatusService {
    public function testCheckResponse($content) {
        return $this->checkResponse($content);
    }
}

class GitHubStatusServiceTest extends \PHPUnit_Framework_TestCase {

    public function testCheckResponseGood() {
        $service = new GitHubStatusServiceExtend();

        $content = '{  "status": "good",  "last_updated": "2012-12-07T18:11:55Z" }';

        $this->assertTrue($service->testCheckResponse($content));
    }

    public function testCheckResponseMinor() {
        $service = new GitHubStatusServiceExtend();

        $content = '{  "status": "minor",  "last_updated": "2012-12-07T18:11:55Z" }';

        $this->assertFalse($service->testCheckResponse($content));
    }

    public function testCheckResponseMajor() {
        $service = new GitHubStatusServiceExtend();

        $content = '{  "status": "major",  "last_updated": "2012-12-07T18:11:55Z" }';

        $this->assertFalse($service->testCheckResponse($content));
    }

}