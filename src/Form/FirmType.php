<?php

namespace App\Form;

use App\Entity\Firms;
use App\Entity\StoragePlans;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;

class FirmType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('addr1')
            ->add('addr2')
            ->add('city')
            ->add('state')
            ->add('zip')
            ->add('country')
            ->add('phone')
            ->add('logo', FileType::class, [
                'label' => 'Firm Logo',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => ['image/png', 'image/jpeg', 'image/svg+xml'],
                        'mimeTypesMessage' => 'Please upload a valid image file',
                    ])
                ]
            ])
            ->add('active', CheckboxType::class, [
                'required' => false,
                // 'empty_data' => 'true',
            ])
            ->add('account')
            ->add('qbbRemovalNum', IntegerType::class, [
                'required' => false,
                'empty_data' => '30', // must be a string, Symfony will cast to int
            ])
            ->add('otherRemovalNum', IntegerType::class, [
                'required' => false,
                'empty_data' => '30', // must be a string, Symfony will cast to int
            ])
            ->add('createdDate', DateTimeType::class, [
                'required' => false,
                'widget' => 'single_text',
                'empty_data' => (new \DateTime())->format('Y-m-d H:i:s'), // must be string
            ])
            ->add('updatedDate', DateTimeType::class, [
                'required' => false,
                'widget' => 'single_text',
                'empty_data' => (new \DateTime())->format('Y-m-d H:i:s'), // must be string
            ])
            ->add('storagePlan', EntityType::class, [
                'class' => StoragePlans::class,
                'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Firms::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'firm',
        ]);
    }
}
