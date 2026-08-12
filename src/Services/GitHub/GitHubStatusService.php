<?php

/** @noinspection PhpMultiplecClassDeclarationsInspection */

namespace App\Services\GitHub;

use App\Services\GitHostingProviderStatusService;
use JsonException;

class GitHubStatusService extends GitHostingProviderStatusService
{
    /**
     * GitHub status API.
     * @see https://www.githubstatus.com/api for more about the GitHub status api
     * (same api as https://kctbh9vrtdwd.statuspage.io/api/v2/summary.json)
     */
     protected const STATUS_URL = 'https://www.githubstatus.com/api/v2/summary.json';


    protected function checkResponse($content): bool
    {
        if (!$content) {
            return false;
        }
        try {
            $status = json_decode($content, null, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            return false;
        }
        if (!isset($status->components)) {
            return false;
        }

        $numberOfWorkingComponents = 0;
        foreach ($status->components as $component) {
            if ($component->name === 'API Requests' || $component->name === 'Git Operations') {
                if ($component->status === 'operational') {
                    $numberOfWorkingComponents++;
                }
            }
        }
        return $numberOfWorkingComponents === 2;
    }

}
