<?php

namespace App\Services\GitHub;

class GitHubStatusService {

    private ?bool $status = null;

    /**
     * Check if GitHub is functional
     *
     * @return bool true if status is good, otherwise false
     */
    public function isFunctional() {
        if ($this->status === null) {
            $this->status = $this->checkFunctional();
        }
        return $this->status;
    }

    /**
     * Retrieve status and check if GitHub is functional
     *
     * @return bool true if status is good, otherwise false
     */
    private function checkFunctional() {
        // more about the GitHub status api, see: https://www.githubstatus.com/api
        // (same api as https://kctbh9vrtdwd.statuspage.io/api/v2/summary.json) https://www.githubstatus.com/api/v2/summary.json
        $content = file_get_contents('https://kctbh9vrtdwd.statuspage.io/api/v2/summary.json');

        return $this->checkResponse($content);
    }

    /**
     * Returns true if response status of API Requests is good, otherwise false
     *
     * @param string|false $content
     * @return bool
     */
    protected function checkResponse($content) {
        if (!$content) {
            return false;
        }
        $status = json_decode($content);
        if ($status === null || !isset($status->components)) {
            return false;
        }

        $numberOfWorkingComponents = 0;
        foreach($status->components as $component) {
            if ($component->name === 'API Requests' || $component->name === 'Git Operations') {
                if($component->status === 'operational') {
                    $numberOfWorkingComponents++;
                }
            }
        }
        return $numberOfWorkingComponents === 2;
    }

}