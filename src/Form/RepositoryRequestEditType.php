<?php

namespace App\Form;



use Gregwar\CaptchaBundle\Type\CaptchaType;
use App\Entity\RepositoryEntity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RepositoryRequestEditType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options): void {
        $builder->add('captcha', CaptchaType::class)
                ->add('add', SubmitType::class, [
                    'label' => "Request Setting Edit URL",
                    "attr"=> ["class"=>"btn-primary"]
                ]);
    }

    public function configureOptions(OptionsResolver $resolver): void {
        $resolver->setDefaults([
            'type' => RepositoryEntity::TYPE_PLUGIN,
            'validation_groups' => [RepositoryEntity::TYPE_PLUGIN]
        ]);
    }
}
