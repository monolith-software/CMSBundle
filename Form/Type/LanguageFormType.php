<?php

declare(strict_types=1);

namespace Monolith\Bundle\CMSBundle\Form\Type;

use Monolith\Bundle\CMSBundle\Entity\Language;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LanguageFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('is_enabled')
            ->add('name', null, [
                'attr' => [
                    'autofocus' => 'autofocus',
                    'placeholder' => 'English'
                ]
            ])
            ->add('code', null, [
                'attr' => [
                    'placeholder' => 'en_US'
                ]
            ])
            ->add('position')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Language::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'monolith_cms_language';
    }
}
