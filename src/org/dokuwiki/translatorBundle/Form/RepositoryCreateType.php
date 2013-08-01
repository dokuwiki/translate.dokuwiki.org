<?php

namespace org\dokuwiki\translatorBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class RepositoryCreateType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->add('name', 'text', array('label' => 'Plugin name'));
        $builder->add('email', 'text', array('label' => 'E-mail'));
        $builder->add('url', 'text', array('label' => 'Git url'));
        $builder->add('branch', 'text', array('label' => 'Main branch'));
    }

    /**
     * Returns the name of this type.
     *
     * @return string The name of this type
     */
    public function getName() {
        return 'repository';
    }
}
