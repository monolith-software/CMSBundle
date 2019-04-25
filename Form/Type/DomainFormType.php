<?php

declare(strict_types=1);

namespace Monolith\Bundle\CMSBundle\Form\Type;

use Monolith\Bundle\CMSBundle\Entity\Domain;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DomainFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('is_enabled')
            ->add('name', null, [
                'attr' => [
                    'autofocus' => 'autofocus',
                ]
            ])
            ->add('paid_till_date')
            ->add('is_redirect')
            ->add('language')
            ->add('position')
            ->add('comment')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Domain::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'monolith_cms_domain';
    }
}
