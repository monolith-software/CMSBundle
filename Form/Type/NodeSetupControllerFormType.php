<?php

declare(strict_types=1);

namespace Monolith\Bundle\CMSBundle\Form\Type;

use Monolith\Bundle\CMSBundle\Manager\NodeManager;
use Monolith\Bundle\CMSBundle\Manager\ThemeManager;
use Monolith\Bundle\CMSBundle\Entity\Node;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NodeSetupControllerFormType extends AbstractType
{
    /** @var NodeManager */
    protected $nodeManager;

    /** @var ThemeManager */
    protected $themeManager;

    /**
     * NodeSetupControllerFormType constructor.
     *
     * @param ThemeManager $themeManager
     * @param NodeManager  $nodeManager
     */
    public function __construct(ThemeManager $themeManager, NodeManager $nodeManager)
    {
        $this->nodeManager   = $nodeManager;
        $this->themeManager  = $themeManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $moduleThemes = [];
        foreach ($this->themeManager->getModuleThemes($options['data']->getModule()) as $theme) {
            $moduleThemes[$theme] = $theme;
        }

        $methods = [];
        /** @var \ReflectionMethod $method */
        foreach ($this->nodeManager->getReflectionMethods($options['data']) as $name => $method) {
            $methods[$name] = $name;
        }

        $builder
            ->add('controller', ChoiceType::class, [
                'choices' => $methods,
                'choice_translation_domain' => false,
            ])
            ->add('template', ChoiceType::class, [
                'choices'  => $moduleThemes,
                'required' => false,
                'choice_translation_domain' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Node::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'monolith_cms_node_setup_controller';
    }
}
