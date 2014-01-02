<?php

namespace org\dokuwiki\translatorBundle\Services\GitHub;

class GitHubStatusServiceImpl implements GitHubStatusService {

    private $status = null;

    public function isFunctional() {
        if ($this->status === null) {
            $this->status = $this->checkFunctional();
        }
        return $this->status;
    }

    private function checkFunctional() {
        $content = file_get_contents('https://status.github.com/api/status.json');
        return $this->checkResponse($content);
    }

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