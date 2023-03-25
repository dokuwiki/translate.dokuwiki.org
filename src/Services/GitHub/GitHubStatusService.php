<?php

namespace App\Services\GitHub;

class GitHubStatusService {

    private $status = null;

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

        foreach($status->components as $component) {
            if ($component->name === 'API Requests') {
                return $component->status === 'operational';
            }
        }
        return false;
    }

}