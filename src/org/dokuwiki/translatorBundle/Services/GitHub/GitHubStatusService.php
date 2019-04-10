<?php

namespace org\dokuwiki\translatorBundle\Services\GitHub;

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
        $content = file_get_contents('https://status.github.com/api/status.json');
        return $this->checkResponse($content);
    }

    /**
     * Returns true if response status is good, otherwise false
     *
     * @param string|false $content
     * @return bool
     */
    protected function checkResponse($content) {
        if (!$content) {
            return false;
        }
        $status = json_decode($content);
        if ($status === null) {
            return false;
        }
        return ($status->status === 'good');
    }

}