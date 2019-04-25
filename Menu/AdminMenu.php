<?php

declare(strict_types=1);

namespace Monolith\Bundle\CMSBundle\Menu;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Monolith\Bundle\CMSBundle\Container;
use Monolith\Bundle\CMSBundle\Module\ModuleBundle;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Monolith\Bundle\CMSBundle\Entity\Folder;

class AdminMenu implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @param FactoryInterface $factory
     * @param array $options
     *
     * @return ItemInterface
     */
    public function main(FactoryInterface $factory, array $options): ItemInterface
    {
        $menu = $factory->createItem('admin_main');

        $menu->setChildrenAttribute('class', isset($options['class']) ? $options['class'] : 'sidebar-menu'); // nav navbar-nav
        $menu->setChildrenAttribute('data-widget', 'tree');

        $menu->addChild('Control panel')->setAttributes(['class' => 'header']);

        $menu->addChild('Dashboard',        ['route' => 'cms_admin_index'])->setExtras(['beforeCode' => '<i class="fa fa-dashboard"></i>']);

        /** @var ModuleBundle $module */
        foreach ($this->container->get('cms.module')->all() as $module) {
            if ($module->isEnabled()) {
                $module->buildAdminMenu($menu);
            }
        }

        $this->system($menu);

        return $menu;
    }

    /**
     * @param ItemInterface $mainMenu
     *
     * @return ItemInterface
     */
    protected function system(ItemInterface $mainMenu)
    {
        $security = $this->container->get('cms.security');

        if ($security->isGranted('cms:admin.system')) {
            if ($this->container->get('smart_core.settings.manager')->get('cms:is_collapse_system_menu')) {
                $menu = $mainMenu->addChild('System', ['route' => 'cms_admin_system'])
                    ->setAttribute('class', 'treeview')
                    ->setExtras([
                        'afterCode'  => '<i class="fa fa-angle-left pull-right"></i>',
                        'beforeCode' => '<i class="fa fa-gear"></i>',
                    ])
                ;
                $menu->setChildrenAttribute('class', 'treeview-menu');
            } else {
                $mainMenu->addChild('System')->setAttributes(['class' => 'header']);

                $menu = $mainMenu;
            }

            $menu->addChild('Site structure',       ['route' => 'cms_admin_structure'])->setExtras(['beforeCode' => '<i class="fa fa-folder-open"></i>']);

//            if ($security->isSuperAdmin()) {
//                $menu->addChild('Mega Structure',   ['route' => 'cms_admin_megastructure'])->setExtras(['beforeCode' => '<i class="fa fa-building"></i>']);
//            }

            if ($security->isGranted('cms:admin.system.module')) {
                $menu->addChild('Modules',          ['route' => 'cms_admin_module'])->setExtras(['beforeCode' => '<i class="fa fa-building-o"></i>']);
            }

            $menu->addChild('Files',                ['route' => 'cms_admin_files'])->setExtras(['beforeCode' => '<i class="fa fa-download"></i>']);

            if ($security->isGranted('cms:admin.system.config')) {
                $menu->addChild('Configuration',    ['route' => 'smart_core_settings'])->setExtras(['beforeCode' => '<i class="fa fa-gears"></i>']);
            }

            if ($security->isGranted('cms:admin.system.language')) {
                $menu->addChild('Languages',        ['route' => 'cms_admin_language'])->setExtras(['beforeCode' => '<i class="fa fa-language"></i>'])
                    ->setAttributes(['title' => 'Настройка языковых и региональных параметров']);
            }

            if ($security->isGranted('cms:admin.system.site')) {
                $menu->addChild('Sites',            ['route' => 'cms_admin_site'])->setExtras(['beforeCode' => '<i class="fa fa-sitemap"></i>']);
            }

            if ($security->isGranted('cms:admin.system.development')) {
                $menu->addChild('Development',      ['route' => 'cms_admin_development'])->setExtras(['beforeCode' => '<i class="fa fa-connectdevelop"></i>']);
            }

            if ($security->isGranted('cms:admin.system.theme')) {
                $menu->addChild('Design themes',    ['route' => 'cms_admin_theme_index'])->setExtras(['beforeCode' => '<i class="fa fa-image"></i>']);
            }

            if ($security->isGranted('cms:admin.system.user')) {
                $menu->addChild('Users',            ['route' => 'cms_admin_user'])->setExtras(['beforeCode' => '<i class="fa fa-users"></i>']);
            }

            //$menu->addChild('Appearance',       ['route' => 'cms_admin_appearance'])->setExtras(['beforeCode' => '<i class="fa fa-image"></i>']);
            //$menu->addChild('Backup',           ['route' => 'cms_admin_backup'])->setExtras(['beforeCode' => '<i class="fa fa-file-archive-o "></i>']);
            //$menu->addChild('Reports',          ['route' => 'cms_admin_reports'])->setExtras(['beforeCode' => '<i class="fa fa-bar-chart"></i>']);
            //$menu->addChild('Help',             ['route' => 'cms_admin_help'])->setExtras(['beforeCode' => '<i class="fa fa-question"></i>']);

            return $menu;
        }
    }

    /**
     * @param FactoryInterface $factory
     * @param array            $options
     *
     * @return ItemInterface
     */
    public function user(FactoryInterface $factory, array $options): ItemInterface
    {
        $menu = $factory->createItem('admin_user');

        $menu->setExtra('select_intehitance', false);
        $menu->setChildrenAttribute('class', isset($options['class']) ? $options['class'] : 'nav nav-tabs');

        $menu->addChild('All users',    ['route' => 'cms_admin_user']);
        $menu->addChild('Create user',  ['route' => 'cms_admin_user_create']);

        $security = $this->container->get('cms.security');

        if ($security->isGranted('cms:admin.system.user_groups')) {
            $menu->addChild('Groups',       ['route' => 'cms_admin_user_groups']);
        }

        return $menu;
    }

    public function modules(FactoryInterface $factory, array $options): ItemInterface
    {
        $menu = $factory->createItem('admin_structure');

        $menu->setExtra('select_intehitance', false);
        $menu->setChildrenAttribute('class', isset($options['class']) ? $options['class'] : 'nav nav-tabs');
        $menu->addChild('Enables',          ['route' => 'cms_admin_module']);
        $menu->addChild('Disables',         ['route' => 'cms_admin_module_disabled']);
        $menu->addChild('All installed',    ['route' => 'cms_admin_module_all']);
//        $menu->addChild('Install new',      ['route' => 'cms_admin_module_install']);

        return $menu;
    }

    /**
     * Меню управления стуктурой (папки и блоки).
     *
     * @param FactoryInterface $factory
     * @param array $options
     *
     * @return ItemInterface
     */
    public function structure(FactoryInterface $factory, array $options): ItemInterface
    {
        $menu = $factory->createItem('admin_structure');

        $menu->setExtra('select_intehitance', false);
        $menu->setChildrenAttribute('class', isset($options['class']) ? $options['class'] : 'nav nav-tabs');
        $menu->addChild('Site structure', ['route' => 'cms_admin_structure']);
        $menu->addChild('Create folder',  ['route' => 'cms_admin_structure_folder_create']);
        $menu->addChild('Connect module', ['route' => 'cms_admin_structure_node_create']);
        $menu->addChild('Regions',        ['route' => 'cms_admin_structure_region']);
        $menu->addChild('Trash',          ['route' => 'cms_admin_structure_trash']);

        return $menu;
    }

    /**
     * Построение полной структуры, включая ноды.
     *
     * @param FactoryInterface  $factory
     * @param array             $options
     *
     * @return ItemInterface
     */
    public function structureTree(FactoryInterface $factory, array $options): ItemInterface
    {
        $menu = $factory->createItem('full_structure');
        $menu->setChildrenAttributes([
            'class' => 'filetree',
            'id'    => 'browser',
        ]);
        $menu->setExtra('translation_domain', false);

        $this->addChild($menu);

        return $menu;
    }

    /**
     * Рекурсивное построение дерева структуры сайта (разделы и ноды).
     *
     * @param ItemInterface $menu
     * @param Folder        $parent_folder
     */
    protected function addChild(ItemInterface $menu, Folder $parent_folder = null): void
    {
        $rootFolder = $this->container->get('cms.context')->getSite()->getRootFolder();

        if (empty($rootFolder)) {
            $rootFolder = [];
        } else {
            $rootFolder = [$rootFolder];
        }

        $folders = (null == $parent_folder)
            //? $this->container->get('cms.folder')->findByParent(null)
            ? $rootFolder
            : $parent_folder->getChildren();

        /** @var $folder Folder */
        foreach ($folders as $folder) {
            if ($folder->isDeleted()) {
                continue;
            }

            $uri = $this->container->get('router')->generate('cms_admin_structure_folder', ['id' => $folder->getId()]);

            $tpl = $folder->getTemplateSelf();
            if (!empty($tpl)) {
                $tpl = ', tpl_self: '.$tpl;
            }

            if (!empty($folder->getTemplateInheritable())) {
                $tpl .= ', tpl_inherit: '.$folder->getTemplateInheritable();
            }

            $label = $folder->getTitle().' <span style="color: #a8a8a8;">('.$this->container->get('cms.folder')->getUri($folder, false).$tpl.')</span>';

            if (!$folder->isActive()) {
                $label = '<span style="text-decoration: line-through;">'.$label.'</span>';
            }

            $position = $this->container->get('translator')->trans('Position');

            $menu->addChild($folder->getTitle(), ['uri' => $uri])
                ->setAttributes([
                    'class' => 'folder',
                    'title' => $folder->getDescription().' ('.$position.' '.$folder->getPosition().')',
                    'id'    => 'folder_id_'.$folder->getId(),
                ])
                ->setLabel($label)
                ->setExtra('translation_domain', false)
            ;

            /** @var $sub_menu ItemInterface */
            $sub_menu = $menu[$folder->getTitle()];

            $this->addChild($sub_menu, $folder);

            /** @var $node \Monolith\Bundle\CMSBundle\Entity\Node */
            foreach ($folder->getNodes() as $node) {
                if ($node->isDeleted()) {
                    continue;
                }

                $moduleName =  substr($node->getModule(), 0, -12);

                $label = $moduleName;

                if (!empty($node->getDescription())) {
                    $label .= ': '.$node->getDescription();
                }

                if ($node->getRegionName() !== 'content') {
                    $label .= ' <span style="color: #a8a8a8;">(область: '.$node->getRegionName().')</span>';
                }

                if ($node->isNotActive()) {
                    $label = '<span style="text-decoration: line-through;">'.$label.'</span>';
                }

                $bundle = $this->container->get('kernel')->getBundle($node->getModule());
                if ($bundle instanceof ModuleBundle and !$bundle->isEnabled()) {
                    $label = '<span style="background-color: #c14b40; color: white;">'.$label.'</span>';
                }

                $uri = $this->container->get('router')->generate('cms_admin_structure_node_properties', ['id' => $node->getId()]);
                $sub_menu
                    ->addChild($node->getId(), ['uri' => $uri])
                    ->setAttributes([
                        'title' => 'node: '.$node->getId().', position: '.$node->getPosition(),
                        'id'    => 'node_id_'.$node->getId(),
                    ])
                    ->setLabel($label)
                    ->setExtra('translation_domain', false)
                ;
            }
        }
    }
}
