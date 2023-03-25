<?php
namespace App\Tests\Services\GitHub;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class GitHubServiceSearchTest extends KernelTestCase {

    /**
     * @return array
     */
    public function dataProvider_GitHubSearch(): array
    {
         return [
             [
                 'https://github.com/Klap-in/dokuwiki-plugin-docnavigation.git',
                 'test',
                 'https://github.com/Klap-in/dokuwiki-plugin-docnavigation/pulls?q=is%3Apr+is%3Aopen+Translation+update+%28test%29',
                 1 //one prepared pull request
             ]

         ];
    }

    /**
     * @dataProvider dataProvider_GitHubSearch
     *
     * @param string $url
     * @param string $languageCode
     * @param string $expectedUrl
     * @param int $number
     *
     */
    public function testGitHubSearch($url, $languageCode, $expectedUrl, $number) {
        self::bootKernel();

        $api = static::$kernel->getContainer()->get('git_hub_service');

        $info = $api->getOpenPRListInfo($url, $languageCode);
        $this->assertEquals($expectedUrl, $info['listURL']);
        $this->assertEquals($number, $info['count']);
    }
}
