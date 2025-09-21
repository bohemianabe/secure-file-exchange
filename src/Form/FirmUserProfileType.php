<?php

namespace App\Form;

use App\Entity\Firms;
use App\Entity\FirmUserProfiles;
use App\Entity\User;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FirmUserProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName')
            ->add('lastName')
            ->add('title')
            ->add('phone')
            ->add('bulkAction')
            ->add('seeAllFiles')
            ->add('contactUser')
            ->add('userType')
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
            // ->add('firm', EntityType::class, [
            //     'class' => Firms::class,
            //     'choice_label' => 'id',
            // ])
            // ->add('user', EntityType::class, [
            //     'class' => User::class,
            //     'choice_label' => 'id',
            // ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FirmUserProfiles::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'firm_user_profile',
        ]);
    }
}
