<?php /** @noinspection PhpMultipleClassDeclarationsInspection */

namespace App\Repository;

use App\Entity\RepositoryEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use App\Entity\TranslationUpdateEntity;
use Doctrine\Persistence\ManagerRegistry;

class TranslationUpdateEntityRepository extends ServiceEntityRepository {

    private int $failedLangUpdateRetryAge;

    public function __construct(ManagerRegistry $registry, int $failedLangUpdateRetryAge) {
        parent::__construct($registry, TranslationUpdateEntity::class);

        $this->failedLangUpdateRetryAge = $failedLangUpdateRetryAge;
    }

    /**
     * @return TranslationUpdateEntity[]
     */
    public function getPendingTranslationUpdates(): array {
        $query = $this->getEntityManager()->createQuery(/** @lang DQL */
            'SELECT job
             FROM App\Entity\TranslationUpdateEntity job
             JOIN job.repository repository
             WHERE job.state = :stateUndone
                OR (job.state = :stateFailed AND job.updated < :timeToRetry)'
        );
        $query->setParameter('stateUndone', TranslationUpdateEntity::STATE_UNDONE);
        $query->setParameter('stateFailed', TranslationUpdateEntity::STATE_FAILED);
        $query->setParameter('timeToRetry', time() - $this->failedLangUpdateRetryAge);
        return $query->getResult();
    }

    public function clearUpdates(RepositoryEntity $repository): void
    {
        $query = $this->getEntityManager()->createQuery(/** @lang DQL */'
            DELETE FROM App\Entity\TranslationUpdateEntity updates
            WHERE updates.repository = :repository
        ');
        $query->setParameter('repository', $repository);
        $query->execute();
    }

}
