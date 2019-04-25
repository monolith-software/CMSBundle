<?php

declare(strict_types=1);

namespace Monolith\Bundle\CMSBundle\Form\Type;

use Doctrine\ORM\EntityRepository;
use Monolith\Bundle\CMSBundle\Entity\Folder;
use Monolith\Bundle\CMSBundle\Entity\UserGroup;
use Monolith\Bundle\CMSBundle\Form\Tree\FolderTreeType;
use SmartCore\Bundle\SeoBundle\Form\Type\MetaFormType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FolderFormType extends AbstractType
{
    use ContainerAwareTrait;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $templates = ['' => ''];

        $currentThemeDir = $this->container->get('kernel')->getBundle('CMSBundle')->getThemeDir().'/views';

        if (file_exists($currentThemeDir)) {
            $finder = new Finder();
            $finder->files()->sortByName()->depth('== 0')->name('*.html.twig')->in($currentThemeDir);

            /** @var \Symfony\Component\Finder\SplFileInfo $file */
            foreach ($finder as $file) {
                $name = str_replace('.html.twig', '', $file->getFilename());
                $templates[$name] = $name;
            }
        }

        $routedNodes = ['' => ''];
        foreach ($this->container->get('cms.node')->findInFolder($options['data']) as $node) {
            if (!$this->container->has('cms.router_module.'.substr(strtolower($node->getModule()), 0, -12))) {
                continue;
            }

            $nodeTitle = $node->getModule().' (node: '.$node->getId().')';

            if ($node->getDescription()) {
                $nodeTitle .= ' ('.$node->getDescription().')';
            }

            $routedNodes[$nodeTitle] = $node->getId();
        }

        $builder
            ->add('title', null, ['attr' => ['autofocus' => 'autofocus']])
            ->add('uri_part')
            ->add('description')
            ->add('parent_folder', FolderTreeType::class)
            ->add('router_node_id', ChoiceType::class, [
                'choices'  => $routedNodes,
                'required' => false,
                'choice_translation_domain' => false,
            ])
            ->add('position')
            ->add('is_active', null, ['required' => false])
            ->add('is_file',   null, ['required' => false])
            ->add('template_inheritable', ChoiceType::class, [
                'choices'  => $templates,
                'required' => false,
                'choice_translation_domain' => false,
            ])
            ->add('template_self', ChoiceType::class, [
                'choices'  => $templates,
                'required' => false,
                'choice_translation_domain' => false,
            ])
            ->add('meta', MetaFormType::class, ['label' => 'Meta tags'])
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

            //->add('lockout_nodes', 'text')
            //->addEventSubscriber(new FolderSubscriber())
        ;

        if (count($templates) == 1) {
            $builder->remove('template_self');
            $builder->remove('template_inheritable');
        }

        if (count($routedNodes) == 1) {
            $builder->remove('router_node_id');
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Folder::class,
        ]);
    }

    public function getBlockPrefix()
    {
        return 'monolith_cms_folder';
    }
}
