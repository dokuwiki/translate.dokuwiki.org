<?php
namespace org\dokuwiki\translatorBundle\Command;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NoResultException;
use org\dokuwiki\translatorBundle\Entity\RepositoryEntity;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EditRepoEntityCommand extends ContainerAwareCommand {

    /**
     * @var OutputInterface
     */
    private $output;

    protected function configure() {
        $this->setName('dokuwiki:editRepo')
            ->setDescription('Let edit some properties of repository. Supported: giturl, branch, state, englishReadonly')
            ->addArgument('type', InputArgument::REQUIRED, 'plugin, template or core')
            ->addArgument('name', InputArgument::REQUIRED, 'repository name')
            ->addArgument('property', InputArgument::REQUIRED, 'property name')
            ->addArgument('value', InputArgument::REQUIRED, 'string or true/false');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {

        $this->output = $output;

        $name = $input->getArgument('name');
        $type = $input->getArgument('type');

        $repositorytypes = [
            RepositoryEntity::$TYPE_CORE,
            RepositoryEntity::$TYPE_PLUGIN,
            RepositoryEntity::$TYPE_TEMPLATE
        ];
        if (!in_array($type, $repositorytypes)) {
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

        $property = $input->getArgument('property');
        $value = $input->getArgument('value');
        $this->editRepo($repo, $property, $value);
    }

    /**
     * @return EntityManager
     */
    private function  getEntityManager() {
        return $this->getContainer()->get('doctrine')->getManager();
    }

    /**
     * @param RepositoryEntity $repo
     * @param string $property
     * @param string $value
     */
    protected function editRepo(RepositoryEntity $repo, $property, $value) {

        switch($property) {
            case 'giturl':
                $repo->setUrl($value);
                break;

            case 'branch':
                $repo->setBranch($value);
                break;

            case 'state':
                $possibleStates = [
                    RepositoryEntity::$STATE_ACTIVE,
                    RepositoryEntity::$STATE_ERROR,
                    RepositoryEntity::$STATE_INITIALIZING,
                    RepositoryEntity::$STATE_WAITING_FOR_APPROVAL
                ];
                if(!in_array($value, $possibleStates)) {
                    $this->output->writeln('State unknown');
                    return;
                }
                $repo->setState($value);
                break;

            case 'englishReadonly':
                $repo->setEnglishReadonly($value === 'true' ? true : false);
                break;

            default:
                $this->output->writeln('property unknown');
                return;
        }

        $this->getEntityManager()->flush($repo);
        $this->output->writeln('done');
    }


}