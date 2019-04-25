<?php

declare(strict_types=1);

namespace Monolith\Bundle\CMSBundle\Form\Type;

use Doctrine\ORM\EntityRepository;
use Monolith\Bundle\CMSBundle\Entity\Permission;
use Monolith\Bundle\CMSBundle\Entity\UserGroup;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserGroupFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', null, ['attr' => ['autofocus'   => 'autofocus', 'placeholder' => 'Arbitrary string']])
            ->add('name',  null, ['attr' => ['placeholder' => 'Latin characters in lowercase and underlining']])
            ->add('position')
            ->add('is_default_folders_granted_read')
            ->add('is_default_folders_granted_write')
            ->add('is_default_nodes_granted_read')
            ->add('is_default_nodes_granted_write')
            ->add('is_default_regions_granted_read')
            ->add('is_default_regions_granted_write')
            ->add('permissions', EntityType::class, [
                'class' => Permission::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('e')
                        ->orderBy('e.bundle', 'ASC')
                        ->addOrderBy('e.position', 'ASC')
                        ;
                },
                'expanded'    => true,
                'multiple'    => true,
                'required'    => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => UserGroup::class,
        ]);
    }

    public function getBlockPrefix()
    {
        return 'monolith_cms_user_group';
    }
}
