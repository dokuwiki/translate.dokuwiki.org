<?php

namespace App\Tests\Services\GitLab;

use App\Services\GitLab\GitLabStatusService;
use PHPUnit\Framework\TestCase;

/**
 * Dummy class for the service
 */
class GitLabStatusServiceExtend extends GitLabStatusService
{
    public function testCheckResponse($content)
    {
        return $this->checkResponse($content);
    }
}

class GitLabStatusServiceTest extends TestCase
{

    public function testCheckResponseGood()
    {
        $service = new GitLabStatusServiceExtend();

        $content = '{
  "result": {
    "status_overall": {
      "updated": "2023-06-29T15:20:28.798Z",
      "status": "Operational",
      "status_code": 100
    },
    "status": [
      {
        "id": "5b36dc6502d06804c0834a07",
        "name": "Website",
        "status": "Operational",
        "status_code": 100,
        "containers": [
          {
            "id": "5b36e11140e76c04b7220a31",
            "name": "Google Compute Engine",
            "updated": "2023-06-24T18:39:28.191Z",
            "status": "Operational",
            "status_code": 100
          }
        ],
        "updated": "2023-06-24T18:39:28.191Z"
      },
      {
        "id": "5b36e05f02d06804c0834a09",
        "name": "API",
        "status": "Operational",
        "status_code": 100,
        "containers": [
          {
            "id": "5b36e11140e76c04b7220a31",
            "name": "Google Compute Engine",
            "updated": "2023-06-24T18:39:28.189Z",
            "status": "Operational",
            "status_code": 100
          }
        ],
        "updated": "2023-06-24T18:39:28.189Z"
      },
      {
        "id": "5d2f74932676bc45e4927ead",
        "name": "Git Operations",
        "status": "Operational",
        "status_code": 100,
        "containers": [
          {
            "id": "5b36e11140e76c04b7220a31",
            "name": "Google Compute Engine",
            "updated": "2023-06-24T18:39:28.193Z",
            "status": "Operational",
            "status_code": 100
          }
        ],
        "updated": "2023-06-24T18:39:28.193Z"
      },
      {
        "id": "5b36e07afc1f0804be9d754d",
        "name": "Container Registry",
        "status": "Operational",
        "status_code": 100,
        "containers": [
          {
            "id": "5b36e11140e76c04b7220a31",
            "name": "Google Compute Engine",
            "updated": "2023-06-29T03:39:49.823Z",
            "status": "Operational",
            "status_code": 100
          }
        ],
        "updated": "2023-06-29T03:39:49.823Z"
      },
      {
        "id": "5b371ffc1d4f0004bf746dbf",
        "name": "GitLab Pages",
        "status": "Operational",
        "status_code": 100,
        "containers": [
          {
            "id": "5b36e11140e76c04b7220a31",
            "name": "Google Compute Engine",
            "updated": "2023-06-07T13:32:47.416Z",
            "status": "Operational",
            "status_code": 100
          }
        ],
        "updated": "2023-06-07T13:32:47.416Z"
      },
      {
        "id": "5b36e06c633e9004b3d624ad",
        "name": "CI/CD - GitLab SaaS Shared Runners",
        "status": "Operational",
        "status_code": 100,
        "containers": [
          {
            "id": "5b36e11140e76c04b7220a31",
            "name": "Google Compute Engine",
            "updated": "2023-06-24T18:39:28.192Z",
            "status": "Operational",
            "status_code": 100
          }
        ],
        "updated": "2023-06-24T18:39:28.192Z"
      },
      {
        "id": "60a6dc9479914205363d3b09",
        "name": "CI/CD - GitLab SaaS Private Runners",
        "status": "Operational",
        "status_code": 100,
        "containers": [
          {
            "id": "5b36e11140e76c04b7220a31",
            "name": "Google Compute Engine",
            "updated": "2023-06-24T18:39:28.190Z",
            "status": "Operational",
            "status_code": 100
          }
        ],
        "updated": "2023-06-24T18:39:28.190Z"
      },
      {
        "id": "5e29c1403f4deb04c0d7f035",
        "name": "CI/CD - Windows Shared Runners (Beta)",
        "status": "Operational",
        "status_code": 100,
        "containers": [
          {
            "id": "5b36e11140e76c04b7220a31",
            "name": "Google Compute Engine",
            "updated": "2023-06-07T13:32:47.409Z",
            "status": "Operational",
            "status_code": 100
          }
        ],
        "updated": "2023-06-07T13:32:47.409Z"
      },
      {
        "id": "61118ed2b2684c099775f18d",
        "name": "SAML SSO - GitLab SaaS",
        "status": "Operational",
        "status_code": 100,
        "containers": [
          {
            "id": "5b36e11140e76c04b7220a31",
            "name": "Google Compute Engine",
            "updated": "2023-06-07T13:32:47.421Z",
            "status": "Operational",
            "status_code": 100
          }
        ],
        "updated": "2023-06-07T13:32:47.421Z"
      },
      {
        "id": "5b371ff2ab905c04b1de922e",
        "name": "Background Processing",
        "status": "Operational",
        "status_code": 100,
        "containers": [
          {
            "id": "5b36e11140e76c04b7220a31",
            "name": "Google Compute Engine",
            "updated": "2023-06-08T12:30:42.144Z",
            "status": "Operational",
            "status_code": 100
          }
        ],
        "updated": "2023-06-08T12:30:42.144Z"
      },
      {
        "id": "5d02cb79b2e5f00a022b5fb4",
        "name": "GitLab Customers Portal",
        "status": "Operational",
        "status_code": 100,
        "containers": [
          {
            "id": "5b36e11140e76c04b7220a31",
            "name": "Google Compute Engine",
            "updated": "2023-06-07T13:32:47.419Z",
            "status": "Operational",
            "status_code": 100
          }
        ],
        "updated": "2023-06-07T13:32:47.419Z"
      },
      {
        "id": "5c38362c5495bf472f8dfbae",
        "name": "Support Services",
        "status": "Operational",
        "status_code": 100,
        "containers": [
          {
            "id": "5c3836366953ce47539ce53a",
            "name": "Zendesk",
            "updated": "2023-06-07T13:32:47.560Z",
            "status": "Operational",
            "status_code": 100
          }
        ],
        "updated": "2023-06-07T13:32:47.560Z"
      },
      {
        "id": "5c7d5bb83efc3204ba5f53c3",
        "name": "packages.gitlab.com",
        "status": "Operational",
        "status_code": 100,
        "containers": [
          {
            "id": "5c7d5baf70abc604c107381c",
            "name": "AWS",
            "updated": "2023-06-07T13:32:47.410Z",
            "status": "Operational",
            "status_code": 100
          }
        ],
        "updated": "2023-06-07T13:32:47.410Z"
      },
      {
        "id": "5d31c2f251014050f413e808",
        "name": "version.gitlab.com",
        "status": "Operational",
        "status_code": 100,
        "containers": [
          {
            "id": "5c7d5baf70abc604c107381c",
            "name": "AWS",
            "updated": "2023-06-07T13:32:47.421Z",
            "status": "Operational",
            "status_code": 100
          }
        ],
        "updated": "2023-06-07T13:32:47.421Z"
      },
      {
        "id": "5d93c0defdc75b69cf385b1f",
        "name": "forum.gitlab.com",
        "status": "Operational",
        "status_code": 100,
        "containers": [
          {
            "id": "5b36e09b17b99c04bef1f946",
            "name": "Digital Ocean",
            "updated": "2023-06-07T13:32:47.417Z",
            "status": "Operational",
            "status_code": 100
          }
        ],
        "updated": "2023-06-07T13:32:47.417Z"
      },
      {
        "id": "63dc247523b01e05870db1ab",
        "name": "docs.gitlab.com",
        "status": "Operational",
        "status_code": 100,
        "containers": [
          {
            "id": "5b36e11140e76c04b7220a31",
            "name": "Google Compute Engine",
            "updated": "2023-06-07T13:32:47.569Z",
            "status": "Operational",
            "status_code": 100
          }
        ],
        "updated": "2023-06-07T13:32:47.569Z"
      },
      {
        "id": "5ed145987f9dc304bf8a9164",
        "name": "Canary",
        "status": "Operational",
        "status_code": 100,
        "containers": [
          {
            "id": "5b36e11140e76c04b7220a31",
            "name": "Google Compute Engine",
            "updated": "2023-06-07T13:32:47.560Z",
            "status": "Operational",
            "status_code": 100
          }
        ],
        "updated": "2023-06-07T13:32:47.560Z"
      }
    ],
    "incidents": [],
    "maintenance": {
      "active": [],
      "upcoming": [
        {
          "name": "CI Cluster PostgreSQL 14 Upgrade",
          "_id": "649d8d7a6c2f36053b37055d",
          "datetime_open": "2023-06-29T13:56:10.250Z",
          "datetime_planned_start": "2023-07-08T14:00:00.000Z",
          "datetime_planned_end": "2023-07-08T19:00:00.000Z",
          "messages": [
            {
              "details": "We will be undergoing scheduled maintenance to our CI database layer. GitLab.com will continue to be available during the maintenance window. We apologize in advance for any inconvenience this may cause. More details: https://gitlab.com/gitlab-com/gl-infra/production/-/issues/15945",
              "state": 100,
              "status": 200,
              "datetime": "2023-06-29T13:56:10.282Z"
            }
          ],
          "containers_affected": [
            {
              "name": "Google Compute Engine",
              "_id": "5b36e11140e76c04b7220a31"
            }
          ],
          "components_affected": [
            {
              "name": "Website",
              "_id": "5b36dc6502d06804c0834a07"
            },
            {
              "name": "API",
              "_id": "5b36e05f02d06804c0834a09"
            },
            {
              "name": "Git Operations",
              "_id": "5d2f74932676bc45e4927ead"
            },
            {
              "name": "CI/CD - GitLab SaaS Shared Runners",
              "_id": "5b36e06c633e9004b3d624ad"
            },
            {
              "name": "CI/CD - GitLab SaaS Private Runners",
              "_id": "60a6dc9479914205363d3b09"
            }
          ]
        }
      ]
    }
  }
}';

        $this->assertTrue($service->testCheckResponse($content));
    }

    public function testCheckResponseDegraded()
    {
        $service = new GitLabStatusServiceExtend();

        $content = '{
  "result": {
    "status_overall": {
      "updated": "2023-06-29T15:20:28.798Z",
      "status": "Operational",
      "status_code": 100
    },
    "status": [
      {
        "id": "5b36dc6502d06804c0834a07",
        "name": "Website",
        "status": "Operational",
        "status_code": 100,
        "containers": [
          {
            "id": "5b36e11140e76c04b7220a31",
            "name": "Google Compute Engine",
            "updated": "2023-06-24T18:39:28.191Z",
            "status": "Operational",
            "status_code": 100
          }
        ],
        "updated": "2023-06-24T18:39:28.191Z"
      },
      {
        "id": "5b36e05f02d06804c0834a09",
        "name": "API",
        "status": "Service Disruption",
        "status_code": 500
        "containers": [
          {
            "id": "5b36e11140e76c04b7220a31",
            "name": "Google Compute Engine",
            "updated": "2023-06-24T18:39:28.189Z",
            "status": "Operational",
            "status_code": 100
          }
        ],
        "updated": "2023-06-24T18:39:28.189Z"
      },
      {
        "id": "5d2f74932676bc45e4927ead",
        "name": "Git Operations",
        "status": "Operational",
        "status_code": 100,
        "containers": [
          {
            "id": "5b36e11140e76c04b7220a31",
            "name": "Google Compute Engine",
            "updated": "2023-06-24T18:39:28.193Z",
            "status": "Operational",
            "status_code": 100
          }
        ],
        "updated": "2023-06-24T18:39:28.193Z"
      },
      {
        "id": "5b36e07afc1f0804be9d754d",
        "name": "Container Registry",
        "status": "Operational",
        "status_code": 100,
        "containers": [
          {
            "id": "5b36e11140e76c04b7220a31",
            "name": "Google Compute Engine",
            "updated": "2023-06-29T03:39:49.823Z",
            "status": "Operational",
            "status_code": 100
          }
        ],
        "updated": "2023-06-29T03:39:49.823Z"
      },
      {
        "id": "5b371ffc1d4f0004bf746dbf",
        "name": "GitLab Pages",
        "status": "Operational",
        "status_code": 100,
        "containers": [
          {
            "id": "5b36e11140e76c04b7220a31",
            "name": "Google Compute Engine",
            "updated": "2023-06-07T13:32:47.416Z",
            "status": "Operational",
            "status_code": 100
          }
        ],
        "updated": "2023-06-07T13:32:47.416Z"
      },
      {
        "id": "5b36e06c633e9004b3d624ad",
        "name": "CI/CD - GitLab SaaS Shared Runners",
        "status": "Operational",
        "status_code": 100,
        "containers": [
          {
            "id": "5b36e11140e76c04b7220a31",
            "name": "Google Compute Engine",
            "updated": "2023-06-24T18:39:28.192Z",
            "status": "Operational",
            "status_code": 100
          }
        ],
        "updated": "2023-06-24T18:39:28.192Z"
      },
      {
        "id": "60a6dc9479914205363d3b09",
        "name": "CI/CD - GitLab SaaS Private Runners",
        "status": "Operational",
        "status_code": 100,
        "containers": [
          {
            "id": "5b36e11140e76c04b7220a31",
            "name": "Google Compute Engine",
            "updated": "2023-06-24T18:39:28.190Z",
            "status": "Operational",
            "status_code": 100
          }
        ],
        "updated": "2023-06-24T18:39:28.190Z"
      },
      {
        "id": "5e29c1403f4deb04c0d7f035",
        "name": "CI/CD - Windows Shared Runners (Beta)",
        "status": "Operational",
        "status_code": 100,
        "containers": [
          {
            "id": "5b36e11140e76c04b7220a31",
            "name": "Google Compute Engine",
            "updated": "2023-06-07T13:32:47.409Z",
            "status": "Operational",
            "status_code": 100
          }
        ],
        "updated": "2023-06-07T13:32:47.409Z"
      },
      {
        "id": "61118ed2b2684c099775f18d",
        "name": "SAML SSO - GitLab SaaS",
        "status": "Operational",
        "status_code": 100,
        "containers": [
          {
            "id": "5b36e11140e76c04b7220a31",
            "name": "Google Compute Engine",
            "updated": "2023-06-07T13:32:47.421Z",
            "status": "Operational",
            "status_code": 100
          }
        ],
        "updated": "2023-06-07T13:32:47.421Z"
      },
      {
        "id": "5b371ff2ab905c04b1de922e",
        "name": "Background Processing",
        "status": "Operational",
        "status_code": 100,
        "containers": [
          {
            "id": "5b36e11140e76c04b7220a31",
            "name": "Google Compute Engine",
            "updated": "2023-06-08T12:30:42.144Z",
            "status": "Operational",
            "status_code": 100
          }
        ],
        "updated": "2023-06-08T12:30:42.144Z"
      },
      {
        "id": "5d02cb79b2e5f00a022b5fb4",
        "name": "GitLab Customers Portal",
        "status": "Operational",
        "status_code": 100,
        "containers": [
          {
            "id": "5b36e11140e76c04b7220a31",
            "name": "Google Compute Engine",
            "updated": "2023-06-07T13:32:47.419Z",
            "status": "Operational",
            "status_code": 100
          }
        ],
        "updated": "2023-06-07T13:32:47.419Z"
      },
      {
        "id": "5c38362c5495bf472f8dfbae",
        "name": "Support Services",
        "status": "Operational",
        "status_code": 100,
        "containers": [
          {
            "id": "5c3836366953ce47539ce53a",
            "name": "Zendesk",
            "updated": "2023-06-07T13:32:47.560Z",
            "status": "Operational",
            "status_code": 100
          }
        ],
        "updated": "2023-06-07T13:32:47.560Z"
      },
      {
        "id": "5c7d5bb83efc3204ba5f53c3",
        "name": "packages.gitlab.com",
        "status": "Operational",
        "status_code": 100,
        "containers": [
          {
            "id": "5c7d5baf70abc604c107381c",
            "name": "AWS",
            "updated": "2023-06-07T13:32:47.410Z",
            "status": "Operational",
            "status_code": 100
          }
        ],
        "updated": "2023-06-07T13:32:47.410Z"
      },
      {
        "id": "5d31c2f251014050f413e808",
        "name": "version.gitlab.com",
        "status": "Operational",
        "status_code": 100,
        "containers": [
          {
            "id": "5c7d5baf70abc604c107381c",
            "name": "AWS",
            "updated": "2023-06-07T13:32:47.421Z",
            "status": "Operational",
            "status_code": 100
          }
        ],
        "updated": "2023-06-07T13:32:47.421Z"
      },
      {
        "id": "5d93c0defdc75b69cf385b1f",
        "name": "forum.gitlab.com",
        "status": "Operational",
        "status_code": 100,
        "containers": [
          {
            "id": "5b36e09b17b99c04bef1f946",
            "name": "Digital Ocean",
            "updated": "2023-06-07T13:32:47.417Z",
            "status": "Operational",
            "status_code": 100
          }
        ],
        "updated": "2023-06-07T13:32:47.417Z"
      },
      {
        "id": "63dc247523b01e05870db1ab",
        "name": "docs.gitlab.com",
        "status": "Operational",
        "status_code": 100,
        "containers": [
          {
            "id": "5b36e11140e76c04b7220a31",
            "name": "Google Compute Engine",
            "updated": "2023-06-07T13:32:47.569Z",
            "status": "Operational",
            "status_code": 100
          }
        ],
        "updated": "2023-06-07T13:32:47.569Z"
      },
      {
        "id": "5ed145987f9dc304bf8a9164",
        "name": "Canary",
        "status": "Operational",
        "status_code": 100,
        "containers": [
          {
            "id": "5b36e11140e76c04b7220a31",
            "name": "Google Compute Engine",
            "updated": "2023-06-07T13:32:47.560Z",
            "status": "Operational",
            "status_code": 100
          }
        ],
        "updated": "2023-06-07T13:32:47.560Z"
      }
    ],
    "incidents": [],
    "maintenance": {
      "active": [],
      "upcoming": [
        {
          "name": "CI Cluster PostgreSQL 14 Upgrade",
          "_id": "649d8d7a6c2f36053b37055d",
          "datetime_open": "2023-06-29T13:56:10.250Z",
          "datetime_planned_start": "2023-07-08T14:00:00.000Z",
          "datetime_planned_end": "2023-07-08T19:00:00.000Z",
          "messages": [
            {
              "details": "We will be undergoing scheduled maintenance to our CI database layer. GitLab.com will continue to be available during the maintenance window. We apologize in advance for any inconvenience this may cause. More details: https://gitlab.com/gitlab-com/gl-infra/production/-/issues/15945",
              "state": 100,
              "status": 200,
              "datetime": "2023-06-29T13:56:10.282Z"
            }
          ],
          "containers_affected": [
            {
              "name": "Google Compute Engine",
              "_id": "5b36e11140e76c04b7220a31"
            }
          ],
          "components_affected": [
            {
              "name": "Website",
              "_id": "5b36dc6502d06804c0834a07"
            },
            {
              "name": "API",
              "_id": "5b36e05f02d06804c0834a09"
            },
            {
              "name": "Git Operations",
              "_id": "5d2f74932676bc45e4927ead"
            },
            {
              "name": "CI/CD - GitLab SaaS Shared Runners",
              "_id": "5b36e06c633e9004b3d624ad"
            },
            {
              "name": "CI/CD - GitLab SaaS Private Runners",
              "_id": "60a6dc9479914205363d3b09"
            }
          ]
        }
      ]
    }
  }
}';
        $this->assertFalse($service->testCheckResponse($content));
    }

    public function testCheckResponsePartialOutage()
    {
        $service = new GitLabStatusServiceExtend();

        $content = '{
  "result": {
    "status_overall": {
      "updated": "2023-06-29T15:20:28.798Z",
      "status": "Operational",
      "status_code": 100
    },
    "status": [
      {
        "id": "5b36dc6502d06804c0834a07",
        "name": "Website",
        "status": "Operational",
        "status_code": 100,
        "containers": [
          {
            "id": "5b36e11140e76c04b7220a31",
            "name": "Google Compute Engine",
            "updated": "2023-06-24T18:39:28.191Z",
            "status": "Operational",
            "status_code": 100
          }
        ],
        "updated": "2023-06-24T18:39:28.191Z"
      },
      {
        "id": "5b36e05f02d06804c0834a09",
        "name": "API",
        "status": "Operational",
        "status_code": 100,
        "containers": [
          {
            "id": "5b36e11140e76c04b7220a31",
            "name": "Google Compute Engine",
            "updated": "2023-06-24T18:39:28.189Z",
            "status": "Operational",
            "status_code": 100
          }
        ],
        "updated": "2023-06-24T18:39:28.189Z"
      },
      {
        "id": "5d2f74932676bc45e4927ead",
        "name": "Git Operations",
        "status": "Planned Maintenance",
        "status_code": 200,
        "containers": [
          {
            "id": "5b36e11140e76c04b7220a31",
            "name": "Google Compute Engine",
            "updated": "2023-06-24T18:39:28.193Z",
            "status": "Operational",
            "status_code": 100
          }
        ],
        "updated": "2023-06-24T18:39:28.193Z"
      },
      {
        "id": "5b36e07afc1f0804be9d754d",
        "name": "Container Registry",
        "status": "Operational",
        "status_code": 100,
        "containers": [
          {
            "id": "5b36e11140e76c04b7220a31",
            "name": "Google Compute Engine",
            "updated": "2023-06-29T03:39:49.823Z",
            "status": "Operational",
            "status_code": 100
          }
        ],
        "updated": "2023-06-29T03:39:49.823Z"
      },
      {
        "id": "5b371ffc1d4f0004bf746dbf",
        "name": "GitLab Pages",
        "status": "Operational",
        "status_code": 100,
        "containers": [
          {
            "id": "5b36e11140e76c04b7220a31",
            "name": "Google Compute Engine",
            "updated": "2023-06-07T13:32:47.416Z",
            "status": "Operational",
            "status_code": 100
          }
        ],
        "updated": "2023-06-07T13:32:47.416Z"
      },
      {
        "id": "5b36e06c633e9004b3d624ad",
        "name": "CI/CD - GitLab SaaS Shared Runners",
        "status": "Operational",
        "status_code": 100,
        "containers": [
          {
            "id": "5b36e11140e76c04b7220a31",
            "name": "Google Compute Engine",
            "updated": "2023-06-24T18:39:28.192Z",
            "status": "Operational",
            "status_code": 100
          }
        ],
        "updated": "2023-06-24T18:39:28.192Z"
      },
      {
        "id": "60a6dc9479914205363d3b09",
        "name": "CI/CD - GitLab SaaS Private Runners",
        "status": "Operational",
        "status_code": 100,
        "containers": [
          {
            "id": "5b36e11140e76c04b7220a31",
            "name": "Google Compute Engine",
            "updated": "2023-06-24T18:39:28.190Z",
            "status": "Operational",
            "status_code": 100
          }
        ],
        "updated": "2023-06-24T18:39:28.190Z"
      },
      {
        "id": "5e29c1403f4deb04c0d7f035",
        "name": "CI/CD - Windows Shared Runners (Beta)",
        "status": "Operational",
        "status_code": 100,
        "containers": [
          {
            "id": "5b36e11140e76c04b7220a31",
            "name": "Google Compute Engine",
            "updated": "2023-06-07T13:32:47.409Z",
            "status": "Operational",
            "status_code": 100
          }
        ],
        "updated": "2023-06-07T13:32:47.409Z"
      },
      {
        "id": "61118ed2b2684c099775f18d",
        "name": "SAML SSO - GitLab SaaS",
        "status": "Operational",
        "status_code": 100,
        "containers": [
          {
            "id": "5b36e11140e76c04b7220a31",
            "name": "Google Compute Engine",
            "updated": "2023-06-07T13:32:47.421Z",
            "status": "Operational",
            "status_code": 100
          }
        ],
        "updated": "2023-06-07T13:32:47.421Z"
      },
      {
        "id": "5b371ff2ab905c04b1de922e",
        "name": "Background Processing",
        "status": "Operational",
        "status_code": 100,
        "containers": [
          {
            "id": "5b36e11140e76c04b7220a31",
            "name": "Google Compute Engine",
            "updated": "2023-06-08T12:30:42.144Z",
            "status": "Operational",
            "status_code": 100
          }
        ],
        "updated": "2023-06-08T12:30:42.144Z"
      },
      {
        "id": "5d02cb79b2e5f00a022b5fb4",
        "name": "GitLab Customers Portal",
        "status": "Operational",
        "status_code": 100,
        "containers": [
          {
            "id": "5b36e11140e76c04b7220a31",
            "name": "Google Compute Engine",
            "updated": "2023-06-07T13:32:47.419Z",
            "status": "Operational",
            "status_code": 100
          }
        ],
        "updated": "2023-06-07T13:32:47.419Z"
      },
      {
        "id": "5c38362c5495bf472f8dfbae",
        "name": "Support Services",
        "status": "Operational",
        "status_code": 100,
        "containers": [
          {
            "id": "5c3836366953ce47539ce53a",
            "name": "Zendesk",
            "updated": "2023-06-07T13:32:47.560Z",
            "status": "Operational",
            "status_code": 100
          }
        ],
        "updated": "2023-06-07T13:32:47.560Z"
      },
      {
        "id": "5c7d5bb83efc3204ba5f53c3",
        "name": "packages.gitlab.com",
        "status": "Operational",
        "status_code": 100,
        "containers": [
          {
            "id": "5c7d5baf70abc604c107381c",
            "name": "AWS",
            "updated": "2023-06-07T13:32:47.410Z",
            "status": "Operational",
            "status_code": 100
          }
        ],
        "updated": "2023-06-07T13:32:47.410Z"
      },
      {
        "id": "5d31c2f251014050f413e808",
        "name": "version.gitlab.com",
        "status": "Operational",
        "status_code": 100,
        "containers": [
          {
            "id": "5c7d5baf70abc604c107381c",
            "name": "AWS",
            "updated": "2023-06-07T13:32:47.421Z",
            "status": "Operational",
            "status_code": 100
          }
        ],
        "updated": "2023-06-07T13:32:47.421Z"
      },
      {
        "id": "5d93c0defdc75b69cf385b1f",
        "name": "forum.gitlab.com",
        "status": "Operational",
        "status_code": 100,
        "containers": [
          {
            "id": "5b36e09b17b99c04bef1f946",
            "name": "Digital Ocean",
            "updated": "2023-06-07T13:32:47.417Z",
            "status": "Operational",
            "status_code": 100
          }
        ],
        "updated": "2023-06-07T13:32:47.417Z"
      },
      {
        "id": "63dc247523b01e05870db1ab",
        "name": "docs.gitlab.com",
        "status": "Operational",
        "status_code": 100,
        "containers": [
          {
            "id": "5b36e11140e76c04b7220a31",
            "name": "Google Compute Engine",
            "updated": "2023-06-07T13:32:47.569Z",
            "status": "Operational",
            "status_code": 100
          }
        ],
        "updated": "2023-06-07T13:32:47.569Z"
      },
      {
        "id": "5ed145987f9dc304bf8a9164",
        "name": "Canary",
        "status": "Operational",
        "status_code": 100,
        "containers": [
          {
            "id": "5b36e11140e76c04b7220a31",
            "name": "Google Compute Engine",
            "updated": "2023-06-07T13:32:47.560Z",
            "status": "Operational",
            "status_code": 100
          }
        ],
        "updated": "2023-06-07T13:32:47.560Z"
      }
    ],
    "incidents": [],
    "maintenance": {
      "active": [],
      "upcoming": [
        {
          "name": "CI Cluster PostgreSQL 14 Upgrade",
          "_id": "649d8d7a6c2f36053b37055d",
          "datetime_open": "2023-06-29T13:56:10.250Z",
          "datetime_planned_start": "2023-07-08T14:00:00.000Z",
          "datetime_planned_end": "2023-07-08T19:00:00.000Z",
          "messages": [
            {
              "details": "We will be undergoing scheduled maintenance to our CI database layer. GitLab.com will continue to be available during the maintenance window. We apologize in advance for any inconvenience this may cause. More details: https://gitlab.com/gitlab-com/gl-infra/production/-/issues/15945",
              "state": 100,
              "status": 200,
              "datetime": "2023-06-29T13:56:10.282Z"
            }
          ],
          "containers_affected": [
            {
              "name": "Google Compute Engine",
              "_id": "5b36e11140e76c04b7220a31"
            }
          ],
          "components_affected": [
            {
              "name": "Website",
              "_id": "5b36dc6502d06804c0834a07"
            },
            {
              "name": "API",
              "_id": "5b36e05f02d06804c0834a09"
            },
            {
              "name": "Git Operations",
              "_id": "5d2f74932676bc45e4927ead"
            },
            {
              "name": "CI/CD - GitLab SaaS Shared Runners",
              "_id": "5b36e06c633e9004b3d624ad"
            },
            {
              "name": "CI/CD - GitLab SaaS Private Runners",
              "_id": "60a6dc9479914205363d3b09"
            }
          ]
        }
      ]
    }
  }
}';
        $this->assertFalse($service->testCheckResponse($content));
    }

    public function testCheckResponseMajorOutage()
    {
        $service = new GitLabStatusServiceExtend();

        $content = '{
  "result": {
    "status_overall": {
      "updated": "2023-06-29T15:20:28.798Z",
      "status": "Operational",
      "status_code": 100
    },
    "status": [
      {
        "id": "5b36dc6502d06804c0834a07",
        "name": "Website",
        "status": "Operational",
        "status_code": 100,
        "containers": [
          {
            "id": "5b36e11140e76c04b7220a31",
            "name": "Google Compute Engine",
            "updated": "2023-06-24T18:39:28.191Z",
            "status": "Operational",
            "status_code": 100
          }
        ],
        "updated": "2023-06-24T18:39:28.191Z"
      },
      {
        "id": "5b36e05f02d06804c0834a09",
        "name": "API",
        "status": "Operational",
        "status_code": 500,
        "containers": [
          {
            "id": "5b36e11140e76c04b7220a31",
            "name": "Google Compute Engine",
            "updated": "2023-06-24T18:39:28.189Z",
            "status": "Operational",
            "status_code": 100
          }
        ],
        "updated": "2023-06-24T18:39:28.189Z"
      },
      {
        "id": "5d2f74932676bc45e4927ead",
        "name": "Git Operations",
        "status": "Operational",
        "status_code": 500,
        "containers": [
          {
            "id": "5b36e11140e76c04b7220a31",
            "name": "Google Compute Engine",
            "updated": "2023-06-24T18:39:28.193Z",
            "status": "Operational",
            "status_code": 100
          }
        ],
        "updated": "2023-06-24T18:39:28.193Z"
      },
      {
        "id": "5b36e07afc1f0804be9d754d",
        "name": "Container Registry",
        "status": "Operational",
        "status_code": 100,
        "containers": [
          {
            "id": "5b36e11140e76c04b7220a31",
            "name": "Google Compute Engine",
            "updated": "2023-06-29T03:39:49.823Z",
            "status": "Operational",
            "status_code": 100
          }
        ],
        "updated": "2023-06-29T03:39:49.823Z"
      },
      {
        "id": "5b371ffc1d4f0004bf746dbf",
        "name": "GitLab Pages",
        "status": "Operational",
        "status_code": 100,
        "containers": [
          {
            "id": "5b36e11140e76c04b7220a31",
            "name": "Google Compute Engine",
            "updated": "2023-06-07T13:32:47.416Z",
            "status": "Operational",
            "status_code": 100
          }
        ],
        "updated": "2023-06-07T13:32:47.416Z"
      },
      {
        "id": "5b36e06c633e9004b3d624ad",
        "name": "CI/CD - GitLab SaaS Shared Runners",
        "status": "Operational",
        "status_code": 100,
        "containers": [
          {
            "id": "5b36e11140e76c04b7220a31",
            "name": "Google Compute Engine",
            "updated": "2023-06-24T18:39:28.192Z",
            "status": "Operational",
            "status_code": 100
          }
        ],
        "updated": "2023-06-24T18:39:28.192Z"
      },
      {
        "id": "60a6dc9479914205363d3b09",
        "name": "CI/CD - GitLab SaaS Private Runners",
        "status": "Operational",
        "status_code": 100,
        "containers": [
          {
            "id": "5b36e11140e76c04b7220a31",
            "name": "Google Compute Engine",
            "updated": "2023-06-24T18:39:28.190Z",
            "status": "Operational",
            "status_code": 100
          }
        ],
        "updated": "2023-06-24T18:39:28.190Z"
      },
      {
        "id": "5e29c1403f4deb04c0d7f035",
        "name": "CI/CD - Windows Shared Runners (Beta)",
        "status": "Operational",
        "status_code": 100,
        "containers": [
          {
            "id": "5b36e11140e76c04b7220a31",
            "name": "Google Compute Engine",
            "updated": "2023-06-07T13:32:47.409Z",
            "status": "Operational",
            "status_code": 100
          }
        ],
        "updated": "2023-06-07T13:32:47.409Z"
      },
      {
        "id": "61118ed2b2684c099775f18d",
        "name": "SAML SSO - GitLab SaaS",
        "status": "Operational",
        "status_code": 100,
        "containers": [
          {
            "id": "5b36e11140e76c04b7220a31",
            "name": "Google Compute Engine",
            "updated": "2023-06-07T13:32:47.421Z",
            "status": "Operational",
            "status_code": 100
          }
        ],
        "updated": "2023-06-07T13:32:47.421Z"
      },
      {
        "id": "5b371ff2ab905c04b1de922e",
        "name": "Background Processing",
        "status": "Operational",
        "status_code": 100,
        "containers": [
          {
            "id": "5b36e11140e76c04b7220a31",
            "name": "Google Compute Engine",
            "updated": "2023-06-08T12:30:42.144Z",
            "status": "Operational",
            "status_code": 100
          }
        ],
        "updated": "2023-06-08T12:30:42.144Z"
      },
      {
        "id": "5d02cb79b2e5f00a022b5fb4",
        "name": "GitLab Customers Portal",
        "status": "Operational",
        "status_code": 100,
        "containers": [
          {
            "id": "5b36e11140e76c04b7220a31",
            "name": "Google Compute Engine",
            "updated": "2023-06-07T13:32:47.419Z",
            "status": "Operational",
            "status_code": 100
          }
        ],
        "updated": "2023-06-07T13:32:47.419Z"
      },
      {
        "id": "5c38362c5495bf472f8dfbae",
        "name": "Support Services",
        "status": "Operational",
        "status_code": 100,
        "containers": [
          {
            "id": "5c3836366953ce47539ce53a",
            "name": "Zendesk",
            "updated": "2023-06-07T13:32:47.560Z",
            "status": "Operational",
            "status_code": 100
          }
        ],
        "updated": "2023-06-07T13:32:47.560Z"
      },
      {
        "id": "5c7d5bb83efc3204ba5f53c3",
        "name": "packages.gitlab.com",
        "status": "Operational",
        "status_code": 100,
        "containers": [
          {
            "id": "5c7d5baf70abc604c107381c",
            "name": "AWS",
            "updated": "2023-06-07T13:32:47.410Z",
            "status": "Operational",
            "status_code": 100
          }
        ],
        "updated": "2023-06-07T13:32:47.410Z"
      },
      {
        "id": "5d31c2f251014050f413e808",
        "name": "version.gitlab.com",
        "status": "Operational",
        "status_code": 100,
        "containers": [
          {
            "id": "5c7d5baf70abc604c107381c",
            "name": "AWS",
            "updated": "2023-06-07T13:32:47.421Z",
            "status": "Operational",
            "status_code": 100
          }
        ],
        "updated": "2023-06-07T13:32:47.421Z"
      },
      {
        "id": "5d93c0defdc75b69cf385b1f",
        "name": "forum.gitlab.com",
        "status": "Operational",
        "status_code": 100,
        "containers": [
          {
            "id": "5b36e09b17b99c04bef1f946",
            "name": "Digital Ocean",
            "updated": "2023-06-07T13:32:47.417Z",
            "status": "Operational",
            "status_code": 100
          }
        ],
        "updated": "2023-06-07T13:32:47.417Z"
      },
      {
        "id": "63dc247523b01e05870db1ab",
        "name": "docs.gitlab.com",
        "status": "Operational",
        "status_code": 100,
        "containers": [
          {
            "id": "5b36e11140e76c04b7220a31",
            "name": "Google Compute Engine",
            "updated": "2023-06-07T13:32:47.569Z",
            "status": "Operational",
            "status_code": 100
          }
        ],
        "updated": "2023-06-07T13:32:47.569Z"
      },
      {
        "id": "5ed145987f9dc304bf8a9164",
        "name": "Canary",
        "status": "Operational",
        "status_code": 100,
        "containers": [
          {
            "id": "5b36e11140e76c04b7220a31",
            "name": "Google Compute Engine",
            "updated": "2023-06-07T13:32:47.560Z",
            "status": "Operational",
            "status_code": 100
          }
        ],
        "updated": "2023-06-07T13:32:47.560Z"
      }
    ],
    "incidents": [],
    "maintenance": {
      "active": [],
      "upcoming": [
        {
          "name": "CI Cluster PostgreSQL 14 Upgrade",
          "_id": "649d8d7a6c2f36053b37055d",
          "datetime_open": "2023-06-29T13:56:10.250Z",
          "datetime_planned_start": "2023-07-08T14:00:00.000Z",
          "datetime_planned_end": "2023-07-08T19:00:00.000Z",
          "messages": [
            {
              "details": "We will be undergoing scheduled maintenance to our CI database layer. GitLab.com will continue to be available during the maintenance window. We apologize in advance for any inconvenience this may cause. More details: https://gitlab.com/gitlab-com/gl-infra/production/-/issues/15945",
              "state": 100,
              "status": 200,
              "datetime": "2023-06-29T13:56:10.282Z"
            }
          ],
          "containers_affected": [
            {
              "name": "Google Compute Engine",
              "_id": "5b36e11140e76c04b7220a31"
            }
          ],
          "components_affected": [
            {
              "name": "Website",
              "_id": "5b36dc6502d06804c0834a07"
            },
            {
              "name": "API",
              "_id": "5b36e05f02d06804c0834a09"
            },
            {
              "name": "Git Operations",
              "_id": "5d2f74932676bc45e4927ead"
            },
            {
              "name": "CI/CD - GitLab SaaS Shared Runners",
              "_id": "5b36e06c633e9004b3d624ad"
            },
            {
              "name": "CI/CD - GitLab SaaS Private Runners",
              "_id": "60a6dc9479914205363d3b09"
            }
          ]
        }
      ]
    }
  }
}';
        $this->assertFalse($service->testCheckResponse($content));
    }

    public function testCheckResponseNoComponent()
    {
        $service = new GitLabStatusServiceExtend();

        $content = '{
  "result": {
    "status_overall": {
      "updated": "2023-06-29T15:20:28.798Z",
      "status": "Operational",
      "status_code": 100
    },
    "incidents": [],
    "maintenance": {
      "active": [],
      "upcoming": [
        {
          "name": "CI Cluster PostgreSQL 14 Upgrade",
          "_id": "649d8d7a6c2f36053b37055d",
          "datetime_open": "2023-06-29T13:56:10.250Z",
          "datetime_planned_start": "2023-07-08T14:00:00.000Z",
          "datetime_planned_end": "2023-07-08T19:00:00.000Z",
          "messages": [
            {
              "details": "We will be undergoing scheduled maintenance to our CI database layer. GitLab.com will continue to be available during the maintenance window. We apologize in advance for any inconvenience this may cause. More details: https://gitlab.com/gitlab-com/gl-infra/production/-/issues/15945",
              "state": 100,
              "status": 200,
              "datetime": "2023-06-29T13:56:10.282Z"
            }
          ],
          "containers_affected": [
            {
              "name": "Google Compute Engine",
              "_id": "5b36e11140e76c04b7220a31"
            }
          ],
          "components_affected": [
            {
              "name": "Website",
              "_id": "5b36dc6502d06804c0834a07"
            },
            {
              "name": "API",
              "_id": "5b36e05f02d06804c0834a09"
            },
            {
              "name": "Git Operations",
              "_id": "5d2f74932676bc45e4927ead"
            },
            {
              "name": "CI/CD - GitLab SaaS Shared Runners",
              "_id": "5b36e06c633e9004b3d624ad"
            },
            {
              "name": "CI/CD - GitLab SaaS Private Runners",
              "_id": "60a6dc9479914205363d3b09"
            }
          ]
        }
      ]
    }
  }
}';
        $this->assertFalse($service->testCheckResponse($content));
    }
}
