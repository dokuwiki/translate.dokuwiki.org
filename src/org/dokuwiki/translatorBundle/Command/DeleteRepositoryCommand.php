<?php

namespace org\dokuwiki\translatorBundle\Command;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use org\dokuwiki\translatorBundle\Entity\RepositoryEntity;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class DeleteRepositoryCommand extends ContainerAwareCommand {

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var EntityManager
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager) {

        $this->entityManager = $entityManager;
        parent::__construct();
    }

    protected function configure() {
        $this->setName('dokuwiki:deleteRepo')
            ->setDescription('Delete a repository')
            ->addArgument('type', InputArgument::REQUIRED, 'template, plugin or core')
            ->addArgument('name', InputArgument::REQUIRED, 'repository name');

    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     *
     * @throws OptimisticLockException
     * @throws ORMException
     */
    protected function execute(InputInterface $input, OutputInterface $output) {

        $this->output = $output;

        $name = $input->getArgument('name');
        $type = $input->getArgument('type');

        $repositoryTypes = array(RepositoryEntity::$TYPE_CORE, RepositoryEntity::$TYPE_PLUGIN, RepositoryEntity::$TYPE_TEMPLATE);
        if (!in_array($type, $repositoryTypes)) {
            $output->writeln(sprintf('Type must be %s, %s or %s', RepositoryEntity::$TYPE_CORE, RepositoryEntity::$TYPE_PLUGIN,  RepositoryEntity::$TYPE_TEMPLATE));
            return;
        }
        try {
            $repo = $this->entityManager->getRepository('dokuwikiTranslatorBundle:RepositoryEntity')
                ->getRepository($type, $name);
        } catch (NoResultException $e) {
            $output->writeln('nothing found');
            return;
        }

        $this->entityManager->getRepository('dokuwikiTranslatorBundle:LanguageStatsEntity')
            ->clearStats($repo);
        $this->entityManager->remove($repo);
        $this->entityManager->flush();
        $data = $this->getContainer()->getParameter('data');
        $data .= sprintf('/%s/%s/', $type, $name);

        $fs = new Filesystem();
        if (is_dir($data)) {
            // some files are write-protected by git - this removes write protection
            $fs->chmod($data, 0777, 0000, true);
            // https://bugs.php.net/bug.php?id=52176
            $fs->remove($data);
        }

    }

}
