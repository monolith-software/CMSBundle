<?php

declare(strict_types=1);

namespace Monolith\Bundle\CMSBundle\Form\Type;

use Doctrine\ORM\EntityRepository;
use Monolith\Bundle\CMSBundle\Container;
use Monolith\Bundle\CMSBundle\Entity\UserGroup;
use Monolith\Bundle\CMSBundle\Manager\ModuleManager;
use Monolith\Bundle\CMSBundle\Manager\ThemeManager;
use Smart\CoreBundle\Form\DataTransformer\HtmlTransformer;
use Monolith\Bundle\CMSBundle\Entity\Node;
use Monolith\Bundle\CMSBundle\Entity\Region;
use Monolith\Bundle\CMSBundle\Form\Tree\FolderTreeType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NodeFormType extends AbstractType
{
    /** @var ModuleManager */
    protected $moduleManager;

    /** @var ThemeManager */
    protected $themeManager;

    /**
     * NodeFormType constructor.
     *
     * @param ModuleManager $moduleManager
     */
    public function __construct(ModuleManager $moduleManager, ThemeManager $themeManager)
    {
        $this->moduleManager = $moduleManager;
        $this->themeManager  = $themeManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $modules = [];
        foreach ($this->moduleManager->all() as $module_name => $module) {
            if ($module->isEnabled()) {
                $modules[$module->getShortName()] = $module_name;
            }
        }

        $moduleThemes = [];
        foreach ($this->themeManager->getModuleThemes($options['data']->getModule()) as $theme) {
            $moduleThemes[$theme] = $theme;
        }

        $builder
            ->add('module', ChoiceType::class, [
                'choices' => $modules,
                'data' => 'TexterModuleBundle', // @todo !!! настройку модуля по умолчанию.
                'choice_translation_domain' => false,
            ])
            ->add('controller', null, [
                'attr' => ['readonly' => true]
            ])
            ->add('folder', FolderTreeType::class)
            ->add('region', EntityType::class, [
                'class' => Region::class,
                'query_builder' => function (EntityRepository $er) {
                    $site = Container::get('cms.context')->getSiteId();

                    return $er->createQueryBuilder('b')->where('b.site = :site')->orderBy('b.position', 'ASC')->setParameter('site', $site);
                },
                'required' => true,
            ])
            ->add('controls_in_toolbar', ChoiceType::class, [
                'choices' => [
                    'No' => Node::TOOLBAR_NO,
                    'Only in self folder' => Node::TOOLBAR_ONLY_IN_SELF_FOLDER,
                    //Node::TOOLBAR_ALWAYS => 'Всегда', // @todo
                ],
            ])
            ->add('template', ChoiceType::class, [
                'choices'  => $moduleThemes,
                'required' => false,
                'choice_translation_domain' => false,
            ])
            ->add('description')
            ->add('position')
            ->add('priority')
            ->add($builder->create('code_before')->addViewTransformer(new HtmlTransformer(false)))
            ->add($builder->create('code_after')->addViewTransformer(new HtmlTransformer(false)))
            ->add('is_active', null, ['required' => false])
            ->add('is_cached', null, ['required' => false])
            ->add('is_use_eip', null, ['required' => false])
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

        if (empty($moduleThemes)) {
            $builder->remove('template');
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Node::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'monolith_cms_node';
    }
}
