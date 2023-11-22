<?php /** @noinspection PhpMultipleClassDeclarationsInspection */

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NoResultException;
use App\Entity\LanguageNameEntity;
use Doctrine\Persistence\ManagerRegistry;

class LanguageNameEntityRepository extends ServiceEntityRepository {

    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, LanguageNameEntity::class);
    }

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
        return $this->getEntityManager()->createQuery(/** @lang DQL */'
            SELECT languageName
            FROM App\Entity\LanguageNameEntity languageName
            ORDER BY languageName.name ASC
        ')->getResult();
    }

}
