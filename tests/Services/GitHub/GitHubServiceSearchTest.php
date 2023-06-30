<?php
namespace App\Tests\Services\GitHub;

use App\Services\GitHub\GitHubService;
use App\Services\GitLab\GitLabService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class GitHubServiceSearchTest extends KernelTestCase {

    /**
     * @return array
     */
    public function dataProvider_UpstreamRepoSearch(): array
    {
        return [
            [
                GitHubService::class,
                'https://github.com/Klap-in/dokuwiki-plugin-docnavigation.git',
                'test',
                'https://github.com/Klap-in/dokuwiki-plugin-docnavigation/pulls?q=is%3Apr+is%3Aopen+Translation+update+%28test%29',
                1, //one prepared pull request
                'GitHub'
            ],
            [
                GitLabService::class,
                'https://gitlab.com/Klap-in/dokuwiki-plugin-docnavigation.git',
                'test',
                'https://gitlab.com/Klap-in/dokuwiki-plugin-docnavigation/-/merge_requests?scope=all&state=opened&search=Translation+update+%28test%29',
                1, //one prepared pull request
                'GitLab'
            ]

        ];
    }

    /**
     * @dataProvider dataProvider_UpstreamRepoSearch
     *
     * @param string $className fully qualified class name (FQCN) as service id
     * @param string $url original git clone url of repo on GitHub/GitLab
     * @param string $languageCode
     * @param string $expectedUrl
     * @param int $number
     * @param string $title title of the url
     *
     */
    public function testUpstreamSearch($className, $url, $languageCode, $expectedUrl, $number, $title) {
        self::bootKernel();
        $container = static::getContainer();
        $hostService = $container->get($className);

        $info = $hostService->getOpenPRListInfo($url, $languageCode);
        $this->assertEquals($expectedUrl, $info['listURL']);
        $this->assertEquals($number, $info['count']);
        $this->assertEquals($title, $info['title']);
    }
}
