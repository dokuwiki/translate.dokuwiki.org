<?php

namespace App\Form;

use Gregwar\CaptchaBundle\Type\CaptchaType;
use App\Entity\RepositoryEntity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RepositoryCreateType extends AbstractType {

    const ACTION_CREATE = 'create';
    const ACTION_EDIT = 'edit';


    public function buildForm(FormBuilderInterface $builder, array $options) {
        if($options['action'] == RepositoryCreateType::ACTION_CREATE) {
            $builder->add('name', TextType::class, array('label' => ucfirst($options['type']) . ' name'));
        }
        $builder->add('email', TextType::class, array('label' => 'E-mail'))
                ->add('url', TextType::class, array('label' => 'Git clone url'))
                ->add('branch', TextType::class, array('label' => 'Main branch'))
                ->add('englishReadonly', CheckboxType::class, array('label' => 'English Readonly', 'required' => false))
                ->add('captcha', CaptchaType::class);
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(
            array(
                'type' => RepositoryEntity::$TYPE_PLUGIN,
                'validation_groups' => array(RepositoryEntity::$TYPE_PLUGIN),
                'action' => RepositoryCreateType::ACTION_CREATE
            )
        );
    }
}
