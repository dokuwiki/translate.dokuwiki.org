<?php

namespace org\dokuwiki\translatorBundle\Form;



use org\dokuwiki\translatorBundle\Entity\RepositoryEntity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class RepositoryRequestEditType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->add('captcha', 'captcha');
    }

    /**
     * Returns the name of this type.
     *
     * @return string The name of this type
     */
    public function getName() {
        return 'requesteditrepository';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(
            array(
                'type' => RepositoryEntity::$TYPE_PLUGIN,
                'validation_groups' => array(RepositoryEntity::$TYPE_PLUGIN)
            )
        );
    }
}