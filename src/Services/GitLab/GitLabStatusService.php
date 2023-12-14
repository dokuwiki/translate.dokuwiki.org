<?php

namespace App\Services\GitLab;

use JsonException;

class GitLabStatusService
{
    public const STATUS_OPERATIONAL = 100;
    private ?bool $status = null;

    /**
     * Check if GitLab is functional
     *
     * @return bool true if status is good, otherwise false
     */
    public function isFunctional(): bool
    {
        if ($this->status === null) {
            $this->status = $this->checkFunctional();
        }
        return $this->status;
    }

    /**
     * Retrieve status and check if GitLab is functional
     *
     * @return bool true if status is good, otherwise false
     */
    private function checkFunctional(): bool
    {
        // GitLab status page, see: https://status.gitlab.com/ (or https://status.io/pages/5b36dc6502d06804c08349f7)

        // more about the status.io status api, see: https://kb.status.io/developers/public-status-api/
        // https://4888742015139690.hostedstatus.com/1.0/status/5b36dc6502d06804c08349f7
        $content = file_get_contents('http://hostedstatus.com/1.0/status/5b36dc6502d06804c08349f7');

        return $this->checkResponse($content);
    }

    /**
     * Returns true if response status of API Requests is good, otherwise false
     *
     * @param string|false $content
     * @return bool
     */
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