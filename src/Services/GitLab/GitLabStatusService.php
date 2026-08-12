<?php

/** @noinspection PhpMultipleClassDeclarationsInspection */

namespace App\Services\GitLab;

use App\Services\GitHostingProviderStatusService;
use JsonException;

class GitLabStatusService extends GitHostingProviderStatusService
{
    /**
     * GitLab status page.
     *
     * The URL to retrieve the status information in JSON is documented in the
     * status.io knowledge base article for the Web Status Widget.
     * @see https://status.gitlab.com/
     * @see https://status.io/pages/5b36dc6502d06804c08349f7
     * @see https://kb.status.io/developers/public-status-api/
     * @see https://kb.status.io/miscellaneous/status-widget/
     * @see https://kb.status.io/developers/status-codes/
     */
    protected const STATUS_URL = 'http://hostedstatus.com/1.0/status/5b36dc6502d06804c08349f7';
    public const STATUS_OPERATIONAL = 100;

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
        if (!isset($status->result) || !isset($status->result->status)) {
            return false;
        }

        $numberOfWorkingComponents = 0;
        foreach ($status->result->status as $component) {
            if ($component->name === 'API' || $component->name === 'Git Operations') {
                if ($component->status_code === self::STATUS_OPERATIONAL) {
                    $numberOfWorkingComponents++;
                }
            }
        }
        return $numberOfWorkingComponents === 2;
    }

}
