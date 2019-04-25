<?php

declare(strict_types=1);

namespace Monolith\Bundle\CMSBundle\Module;

use Knp\Menu\MenuItem;
use Monolith\Bundle\CMSBundle\Entity\Node;

trait ModuleBundleTrait
{
    // @todo Заменить на иконку модуля.
    protected $adminMenuBeforeCode = '<i class="fa fa-angle-right"></i>';

    /** @var bool */
    protected $is_enabled;

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->is_enabled;
    }

    /**
     * @param bool $is_enabled
     *
     * @return $this
     */
    public function setIsEnabled(bool $is_enabled)
    {
        $this->is_enabled = $is_enabled;

        return $this;
    }

    /**
     * Действие при создании ноды.
     *
     * @param Node $node
     */
    public function createNode(Node $node)
    {
    }

    /**
     * Действие при удалении ноды.
     *
     * @param Node $node
     */
    public function deleteNode(Node $node)
    {
    }

    /**
     * Действие при обновлении ноды.
     *
     * @param Node $node
     */
    public function updateNode(Node $node)
    {
    }

    /**
     * Получить виджеты для рабочего стола.
     *
     * @return array
     */
    public function getDashboard()
    {
        return [];
    }

    /**
     * Получить оповещения.
     *
     * @return array
     */
    public function getNotifications()
    {
        return [];
    }

    /**
     * @todo Получение списка доступных виджетов у модуля.
     *
     * @return array
     */
    public function getWidgets()
    {
        return [];
    }

    /**
     * Получить короткое имя (без суффикса ModuleBundle).
     * Сейчас используется:
     *  1) в админке для получения списка модулей
     *  2) для создания monolith_cms.modules_paths для подхвата роутингов модулей.
     *
     * @return string
     */
    final public function getShortName(): string
    {
        return substr($this->getName(), 0, -12);
    }

    /**
     * Есть ли у модуля административный раздел.
     *
     * @return bool
     */
    final public function hasAdmin(): bool
    {
        return $this->container->has('cms.router_module.'.strtolower($this->getShortName()).'.admin') ? true : false;
    }

    /**
     * Получить обязательные параметры.
     *
     * @return array
     *
     * @deprecated
     */
    public function getRequiredParams(): array
    {
        return [];
    }

    /**
     * @param MenuItem $menu
     * @param array $extras
     *
     * @return MenuItem
     */
    public function buildAdminMenu(MenuItem $menu, array $extras = [])
    {
        if ($this->hasAdmin()) {
            if (!isset($extras['beforeCode'])) {
                $extras['beforeCode'] = $this->adminMenuBeforeCode;
            }

            $menu->addChild($this->getShortName(), [
                'uri' => $this->container->get('router')->generate('cms_admin_index').$this->getShortName().'/',
            ])->setExtras($extras);
        }

        return $menu;
    }
}
