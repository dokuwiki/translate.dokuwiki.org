<?php

namespace App\Form;

use Gregwar\CaptchaBundle\Type\CaptchaType;
use App\Entity\RepositoryEntity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RepositoryCreateType extends AbstractType {

    public const ACTION_CREATE = 'create';
    public const ACTION_EDIT = 'edit';


    public function buildForm(FormBuilderInterface $builder, array $options) {
        if($options['action'] == RepositoryCreateType::ACTION_CREATE) {
            $builder->add('name', TextType::class, ['label' => ucfirst($options['type']) . ' name']);
        }
        $builder->add('email', TextType::class, ['label' => 'E-mail'])
                ->add('url', TextType::class, ['label' => 'Git clone url'])
                ->add('branch', TextType::class, ['label' => 'Main branch'])
                ->add('englishReadonly', CheckboxType::class, ['label' => 'English Readonly', 'required' => false])
                ->add('captcha', CaptchaType::class);
//                ->add('add', SubmitType::class, [
//                    'label' => "Add my " . ucfirst($options['type'])."!",
//                    "attr"=> ["class"=>"btn-primary"]
//                ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'type' => RepositoryEntity::TYPE_PLUGIN,
            'validation_groups' => [RepositoryEntity::TYPE_PLUGIN],
            'action' => RepositoryCreateType::ACTION_CREATE
        ]);
    }
}
