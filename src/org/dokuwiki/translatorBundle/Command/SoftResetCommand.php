<?php
namespace org\dokuwiki\translatorBundle\Command;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use App\Entity\RepositoryEntity;
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
            ->addArgument('type', InputArgument::REQUIRED, 'plugin, template or core')
            ->addArgument('name', InputArgument::REQUIRED, 'repository name');

    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     *
     * @throws ORMException
     */
    protected function execute(InputInterface $input, OutputInterface $output) {

        $this->output = $output;

        $name = $input->getArgument('name');
        $type = $input->getArgument('type');

        $repositoryTypes = array(RepositoryEntity::$TYPE_CORE, RepositoryEntity::$TYPE_PLUGIN, RepositoryEntity::$TYPE_TEMPLATE);
        if (!in_array($type, $repositoryTypes)) {
            $output->writeln(sprintf('Type must be %s, %s or %s', RepositoryEntity::$TYPE_CORE, RepositoryEntity::$TYPE_PLUGIN, RepositoryEntity::$TYPE_TEMPLATE));
            return;
        }
        try {
            $repo = $this->getEntityManager()->getRepository('dokuwikiTranslatorBundle:RepositoryEntity')
                ->getRepository($type, $name);
        } catch (NoResultException $e) {
            $output->writeln('nothing found');
            return;
        }
        try {
            $this->resetRepo($repo);
        }catch (OptimisticLockException $e) {
            $output->writeln('database locked');
            return;
        }

        $data = $this->getContainer()->getParameter('app.dataDir');
        $data .= sprintf('/%s/%s/', $type, $name);
        $fs = new Filesystem();
        if (is_dir($data .'tmp')) {
            // some files are write-protected by git - this removes write protection
            $fs->chmod($data . 'tmp', 0777, 0000, true);
            // https://bugs.php.net/bug.php?id=52176
            $fs->remove($data . 'tmp');
            $this->output->write('/tmp folder deleted. ');
        }

        if (is_file($data . 'locked')) {
            $fs->remove($data . 'locked');
            $this->output->write('Lock removed. ');
        }
        $this->output->writeln('done');
    }

    /**
     * @return EntityManager
     */
    private function  getEntityManager() {
        return $this->getContainer()->get('doctrine')->getManager();
    }

    /**
     * @param RepositoryEntity $repo
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    protected function resetRepo(RepositoryEntity $repo) {
        $repo->setState(RepositoryEntity::$STATE_ACTIVE);
        $repo->setErrorCount(0);
        $repo->setLastUpdate(0);
        $this->getEntityManager()->flush($repo);
        $this->output->write('Repository state, error count and update date reset. ');
    }


}
