<?php
namespace org\dokuwiki\translatorBundle\Command;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NoResultException;
use org\dokuwiki\translatorBundle\Entity\RepositoryEntity;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class SoftResetCommand extends ContainerAwareCommand {

    /**
     * @var OutputInterface
     */
    private $output;

    protected function configure() {
        $this->setName('dokuwiki:softReset')
            ->setDescription('Reset lock, tmp folder, error count and last updated')
            ->addArgument('type', InputArgument::REQUIRED, 'plugin or core')
            ->addArgument('name', InputArgument::REQUIRED, 'repository name');

    }

    protected function execute(InputInterface $input, OutputInterface $output) {

        $this->output = $output;

        $name = $input->getArgument('name');
        $type = $input->getArgument('type');

        if (!in_array($type, array(RepositoryEntity::$TYPE_CORE, RepositoryEntity::$TYPE_PLUGIN))) {
            $output->writeln(sprintf('Type must be %s or %s', RepositoryEntity::$TYPE_CORE, RepositoryEntity::$TYPE_PLUGIN));
            return;
        }
        try {
            $repo = $this->getEntityManager()->getRepository('dokuwikiTranslatorBundle:RepositoryEntity')
                ->getRepository($type, $name);
        } catch (NoResultException $e) {
            $output->writeln('nothing found');
            return;
        }

        $this->resetRepo($repo);
        $data = $this->getContainer()->getParameter('data');
        $data .= sprintf('/%s/%s/', $type, $name);
        $fs = new Filesystem();
        if (is_dir($data .'tmp')) {
            // some files are write-protected by git - this removes write protection
            $fs->chmod($data . 'tmp', 0777, 0000, true);
            // https://bugs.php.net/bug.php?id=52176
            $fs->remove($data . 'tmp');
        }

        if (is_file($data . 'locked')) {
            $fs->remove($data . 'locked');
        }

    }

    /**
     * @return EntityManager
     */
    private function  getEntityManager() {
        return $this->getContainer()->get('doctrine')->getManager();
    }

    /**
     * @param $repo
     */
    protected function resetRepo(RepositoryEntity $repo) {
        $repo->setState(RepositoryEntity::$STATE_ACTIVE);
        $repo->setLastUpdate(0);
        $this->getEntityManager()->flush($repo);
    }


}