<?php

namespace org\dokuwiki\translatorBundle\Entity;

use Doctrine\ORM\EntityRepository;

class LanguageNameEntityRepository extends EntityRepository {

    public function getLanguageNameByCode($code) {
        $result = $this->findOneBy(
            array('code' => $code)
        );

        if (!$result) return $code;
        return $result->getName();
    }

}
