<?php

declare(strict_types=1);

namespace Monolith\Bundle\CMSBundle\Form\Type;

use Doctrine\ORM\EntityRepository;
use Monolith\Bundle\CMSBundle\Entity\Region;
use Monolith\Bundle\CMSBundle\Entity\UserGroup;
use Monolith\Bundle\CMSBundle\Form\Tree\FolderTreeType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RegionFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', null, ['attr' => ['autofocus' => 'autofocus']])
            ->add('description')
            ->add('position')
            ->add('folders', FolderTreeType::class, [
                //'attr'        => ['style' => 'height: 300px;'],
                'only_active' => true,
                'expanded'    => true,
                'multiple'    => true,
                'label'       => 'Inherit in folders',
                'required'    => false,
            ])
            ->add('groups_granted_read', EntityType::class, [
                'class' => UserGroup::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('e')
                        ->orderBy('e.position', 'ASC')
                        ->addOrderBy('e.title', 'ASC')
                        ;
                },
                'required'        => false,
                'expanded'        => true,
                'multiple'        => true,
                'choice_translation_domain' => false,
            ])
            ->add('groups_granted_write', EntityType::class, [
                'class' => UserGroup::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('e')
                        ->orderBy('e.position', 'ASC')
                        ->addOrderBy('e.title', 'ASC')
                        ;
                },
                'required'        => false,
                'expanded'        => true,
                'multiple'        => true,
                'choice_translation_domain' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Region::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'monolith_cms_region';
    }
}
