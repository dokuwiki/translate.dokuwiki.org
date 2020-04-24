<?php

namespace org\dokuwiki\translatorBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;

class LanguageNameEntityRepository extends EntityRepository {

    /**
     * @param string $code language code
     * @return LanguageNameEntity
     *
     * @throws NoResultException
     */
    public function getLanguageByCode($code) {
        $result = $this->findOneBy(
            array('code' => $code)
        );

        if (!$result) {
            throw new NoResultException();
        }
        return $result;
    }

    /**
     * @return array
     */
    public function getAvailableLanguages() {
        return $this->getEntityManager()->createQuery('
            SELECT languageName
            FROM dokuwikiTranslatorBundle:LanguageNameEntity languageName
            ORDER BY languageName.name ASC
        ')->getResult();
    }

}
