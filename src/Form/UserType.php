<?php

namespace App\Form;

use App\Entity\AdminUserProfiles;
use App\Entity\ClientUserProfiles;
use App\Entity\FirmUserProfiles;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'required' => true,
                'label' => 'Email Address',
            ])
            ->add('roles', ChoiceType::class, [
                'choices' => [
                    'Admin User' => 'ROLE_ADMIN',
                    'Firm User' => 'ROLE_FIRM',
                    'Client User' => 'ROLE_CLIENT',
                ],
                'multiple' => true,
                'expanded' => false,
                'label' => 'User Roles',
                'required' => false,

            ])
            ->add('password', PasswordType::class, [
                'required' => false,
                'label' => 'Password',
                'mapped' => false,
            ])
            ->add('isActive', CheckboxType::class, [
                'required' => false,
                'empty_data' => 'true',
            ])
            // ->add('createdDate', DateTimeType::class, [
            //     'required' => false,
            //     'widget' => 'single_text',
            //     'empty_data' => (new \DateTime())->format('Y-m-d H:i:s'), // must be string
            // ])
            // ->add('updatedDate', DateTimeType::class, [
            //     'required' => false,
            //     'widget' => 'single_text',
            //     'empty_data' => (new \DateTime())->format('Y-m-d H:i:s'), // must be string
            // ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'user',

        ]);
    }
}
