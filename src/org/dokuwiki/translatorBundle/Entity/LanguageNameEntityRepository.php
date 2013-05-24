<?php

namespace org\dokuwiki\translatorBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Symfony\Component\Validator\Tests\Constraints\CountryValidatorTest;

class LanguageNameEntityRepository extends EntityRepository {

    public function getLanguageByCode($code) {
        $result = $this->findOneBy(
            array('code' => $code)
        );

        if (!$result) throw new NoResultException();
        return $result;
    }

    public function getLanguageNameByCode($code) {
        try {
            $language = $this->getLanguageByCode($code);
            return $language->getName();
        } catch (NoResultException $e) {
            return $code;
        }
    }

    public function getAvailableLanguages() {
        try {
            return $this->getEntityManager()->createQuery('
                SELECT languageName
                FROM dokuwikiTranslatorBundle:LanguageNameEntity languageName
                ORDER BY languageName.name ASC
            ')->getResult();
        } catch (NoResultException $e) {
            return array();
        }
    }

}
