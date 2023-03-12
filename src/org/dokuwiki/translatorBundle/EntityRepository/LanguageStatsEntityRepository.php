<?php

namespace org\dokuwiki\translatorBundle\EntityRepository;

use Doctrine\ORM\EntityRepository;
use org\dokuwiki\translatorBundle\Entity\RepositoryEntity;

class LanguageStatsEntityRepository extends EntityRepository {

    public function clearStats(RepositoryEntity $repository) {
        $query = $this->getEntityManager()->createQuery('
            DELETE FROM dokuwikiTranslatorBundle:LanguageStatsEntity langStats
            WHERE langStats.repository = :repository
        ');
        $query->setParameter('repository', $repository);
        $query->execute();
    }
}
