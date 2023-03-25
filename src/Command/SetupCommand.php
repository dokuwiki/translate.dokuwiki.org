<?php

namespace App\Command;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use App\Entity\LanguageNameEntity;
use App\Repository\LanguageNameEntityRepository;
use App\Entity\RepositoryEntity;
use App\Repository\RepositoryEntityRepository;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SetupCommand extends ContainerAwareCommand {

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var \App\Repository\RepositoryEntityRepository
     */
    private $repositoryRepository;

    /**
     * @var \App\Repository\LanguageNameEntityRepository
     */
    private $languageRepository;

    /**
     * @var OutputInterface
     */
    private $output;

    public function __construct(Registry $doctrine) {
        $this->entityManager = $doctrine->getManager();

        parent::__construct();
    }

    protected function configure() {
        $this->setName('dokuwiki:setup')
             ->setDescription('Prepare software for first run');

    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     *
     * @throws NonUniqueResultException
     * @throws OptimisticLockException
     * @throws ORMException
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        $this->repositoryRepository = $this->entityManager->getRepository(RepositoryEntity::class);
        $this->languageRepository = $this->entityManager->getRepository(LanguageNameEntity::class);

        $this->output = $output;

        $this->addLanguageNames();
        $this->addDokuWikiRepo();
    }

    /**
     * @throws NonUniqueResultException
     * @throws OptimisticLockException
     * @throws ORMException
     */
    private function addDokuWikiRepo() {
        try {
            $this->repositoryRepository->getCoreRepository();
            $this->output->writeln('DokuWiki repository already exists');

        } catch(NoResultException $e) {
            $repository = new RepositoryEntity();
            $repository->setUrl('git://github.com/dokuwiki/dokuwiki.git');
            $repository->setBranch('master');
            $repository->setLastUpdate(0);
            $repository->setName('dokuwiki');
            $repository->setAuthor('');
            $repository->setDescription('');
            $repository->setTags('');
            $repository->setType(RepositoryEntity::$TYPE_CORE);
            $repository->setEmail('');
            $repository->setPopularity(0);
            $repository->setDisplayName('DokuWiki');
            $repository->setState(RepositoryEntity::$STATE_ACTIVE);
            $repository->setErrorMsg('');
            $repository->setErrorCount(0);
            $repository->setActivationKey('');
            $this->entityManager->persist($repository);
            $this->entityManager->flush();
            $this->output->writeln('Added DokuWiki repository');
        }

    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    private function addLanguageNames() {
        $names = array(
            'af' => 'Afrikaans',
            'ar' => 'Arabic',
            'az' => 'Azerbaijani',
            'ba' => 'Bashkir',
            'be' => 'Byelorussian',
            'bg' => 'Bulgarian',
            'bn' => 'Bengali; Bangla',
            'br' => 'Breton',
            'ca' => 'Catalan',
            'ca-valencia' => 'Valencian',
            'ckb' => 'Kurdish (Sorani)',
            'cs' => 'Czech',
            'cy' => 'Welsh',
            'da' => 'Danish',
            'de' => 'German',
            'de-informal' => 'German (informal)',
            'el' => 'Greek',
            'en' => 'English',
            'en-pirate' => 'Pirate',
            'eo' => 'Esperanto',
            'es' => 'Spanish',
            'et' => 'Estonian',
            'eu' => 'Basque',
            'fa' => 'Persian',
            'fi' => 'Finnish',
            'fo' => 'Faroese',
            'fr' => 'French',
            'fy' => 'Frisian',
            'ga' => 'Irish',
            'gl' => 'Galician',
            'he' => 'Hebrew',
            'hi' => 'Hindi',
            'hr' => 'Croatian',
            'hu' => 'Hungarian',
            'hu-formal' => 'Hungarian (formal)',
            'hy' => 'Armenian',
            'ia' => 'Interlingua',
            'id' => 'Indonesian',
            'id-ni' => 'Nias',
            'is' => 'Icelandic',
            'it' => 'Italian',
            'ja' => 'Japanese',
            'ka' => 'Georgian',
            'kk' => 'Kazakh',
            'km' => 'Khmer',
            'kn' => 'Kannada',
            'ko' => 'Korean',
            'ku' => 'Kurdish',
            'la' => 'Latin',
            'lb' => 'Luxembourgish',
            'lo' => 'Laothian',
            'lt' => 'Lithuanian',
            'lv' => 'Latvian, Lettish',
            'mg' => 'Malagasy',
            'mi' => 'Maori',
            'mk' => 'Macedonian',
            'ml' => 'Malayalam',
            'mr' => 'Marathi',
            'ms' => 'Malay',
            'mt' => 'Maltese',
            'my' => 'Burmese',
            'ne' => 'Nepali',
            'nl' => 'Dutch',
            'no' => 'Norwegian',
            'oc' => 'Occitan',
            'pa' => 'Punjabi',
            'pl' => 'Polish',
            'pt' => 'Portuguese',
            'pt-br' => 'Brazilian Portuguese',
            'ro' => 'Romanian',
            'ru' => 'Russian',
            'sa' => 'Sanskrit',
            'si' => 'Sinhalese',
            'sk' => 'Slovak',
            'sl' => 'Slovenian',
            'sn' => 'Shona',
            'so' => 'Somali',
            'sq' => 'Albanian',
            'sr' => 'Serbian',
            'su' => 'Sundanese',
            'sv' => 'Swedish',
            'ta' => 'Tamil',
            'te' => 'Telugu',
            'th' => 'Thai',
            'ti' => 'Tigrinya',
            'tn' => 'Setswana',
            'tr' => 'Turkish',
            'tt' => 'Tatar',
            'uk' => 'Ukrainian',
            'ur' => 'Urdu',
            'uz' => 'Uzbek',
            'vi' => 'Vietnamese',
            'vo' => 'Volapuk',
            'zh' => 'Chinese',
            'zh-tw' => 'Chinese Traditional'
        );

        $rtl = array(
            'ar', 'fa', 'he',
        );

        $count['existing'] = 0;
        $count['new'] = 0;
        foreach($names as $code => $name) {
            try {
                $this->languageRepository->getLanguageByCode($code);

                $count['existing']++;
            } catch(NoResultException $e) {
                //only add unknown languages
                $langNames = new LanguageNameEntity();
                $langNames->setCode($code);
                $langNames->setName($name);
                $langNames->setRtl(in_array($code, $rtl));
                $this->entityManager->persist($langNames);

                $count['new']++;
            }

        }

        $this->entityManager->flush();

        $msg = $count['new'] . ' languages added';
        if($count['existing']) {
            $msg .= ' (' . $count['existing'] . ' existing languages )';
        }
        $this->output->writeln($msg);

    }

}
