<?php

declare(strict_types=1);

namespace Monolith\Bundle\CMSBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NodeDefaultPropertiesFormType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'monolith_cms_default_node_properties';
    }

    /**
     * @return string
     */
    public static function getTemplate(): string
    {
        return '@CMS/Admin/Structure/node_properties_form.html.twig';
    }
}
