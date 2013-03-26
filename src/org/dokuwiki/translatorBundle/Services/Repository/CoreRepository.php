<?php
namespace org\dokuwiki\translatorBundle\Services\Repository;

use org\dokuwiki\translatorBundle\Entity\RepositoryEntity;
use Doctrine\ORM\NoResultException;

class CoreRepository extends Repository {

    /**
     * @return string Relative path to the language folder. i.e. lang/ for plugins
     */
    protected function getLanguageFolder() {
        return array(
            'inc/lang',
            'lib/plugins/acl/lang',
            'lib/plugins/authad/lang',
            'lib/plugins/authldap/lang',
            'lib/plugins/authmysql/lang',
            'lib/plugins/authpgsql/lang',
            'lib/plugins/plugin/lang',
            'lib/plugins/popularity/lang',
            'lib/plugins/revert/lang',
            'lib/plugins/popularity/lang',
            'lib/plugins/usermanager/lang',
            'lib/plugins/popularity/lang'
        );
    }

    /**
     * @throws RepositoryManagerException
     * @return RepositoryEntity Database entity of the current repository
     */
    protected function getEntity() {
        try {
            $query = $this->entityManager->createQuery(
                    'SELECT repository
                     FROM dokuwikiTranslatorBundle:RepositoryEntity repository
                     WHERE repository.type = \'core\' AND repository.name = \'dokuwiki\''
            );
            return $query->getSingleResult();
        } catch (NoResultException $e) {
            throw new RepositoryManagerException('No entity for core repository found!', 0, $e);
        }

    }
}
