<?php

namespace org\dokuwiki\translatorBundle\Command;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use org\dokuwiki\translatorBundle\Entity\LanguageNameEntity;
use org\dokuwiki\translatorBundle\Entity\RepositoryEntity;

class SetupCommand extends ContainerAwareCommand {

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var OutputInterface
     */
    private $output;

    protected function configure() {
        $this->setName('dokuwiki:setup')
            ->setDescription('Prepare software for first run');

    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $this->entityManager = $this->getContainer()->get('doctrine')->getManager();
        $this->output = $output;

        $this->addLanguageNames();
        $this->addDokuWikiRepo();
    }

    private function addDokuWikiRepo() {
        $repository = new RepositoryEntity();
        $repository->setUrl('git://github.com/splitbrain/dokuwiki.git');
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

        foreach ($names as $code => $name) {
            $langNames = new LanguageNameEntity();
            $langNames->setCode($code);
            $langNames->setName($name);
            $this->entityManager->persist($langNames);
        }

        $this->entityManager->flush();
        $this->output->writeln('languages added');
    }

}
