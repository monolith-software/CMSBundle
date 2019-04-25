<?php

declare(strict_types=1);

namespace Monolith\Bundle\CMSBundle\Listener;

use Monolith\Bundle\CMSBundle\Manager\ContextManager;
use Monolith\Bundle\CMSBundle\Manager\FolderManager;
use Monolith\Bundle\CMSBundle\Manager\ModuleManager;
use Monolith\Bundle\CMSBundle\Manager\NodeManager;
use Monolith\Bundle\CMSBundle\Twig\Loader\FilesystemLoader;
use SmartCore\Bundle\SettingsBundle\Manager\SettingsManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ModuleControllerModifierListener
{
    /** @var ContextManager */
    protected $contextManager;

    /** @var FolderManager */
    protected $folderManager;

    /** @var ModuleManager */
    protected $moduleManager;

    /** @var NodeManager */
    protected $nodeManager;

    /** @var SettingsManager  */
    protected $settingsManager;

    /** @var \Monolith\Bundle\CMSBundle\Twig\Loader\FilesystemLoader  */
    protected $twigLoader;

    /**
     * ModuleControllerModifierListener constructor.
     *
     * @param ContextManager   $contextManager
     * @param FolderManager    $folderManager
     * @param ModuleManager    $moduleManager
     * @param NodeManager      $nodeManager
     * @param SettingsManager  $settingsManager
     * @param FilesystemLoader $twigLoader
     */
    public function __construct(
        ContextManager $contextManager,
        FolderManager $folderManager,
        ModuleManager $moduleManager,
        NodeManager $nodeManager,
        SettingsManager $settingsManager,
        FilesystemLoader $twigLoader
    ) {
        $this->contextManager   = $contextManager;
        $this->folderManager    = $folderManager;
        $this->moduleManager    = $moduleManager;
        $this->nodeManager      = $nodeManager;
        $this->settingsManager  = $settingsManager;
        $this->twigLoader       = $twigLoader;
    }

    public function onView(GetResponseForControllerResultEvent $event): void
    {
        $response = new Response();
        $response->setContent($event->getControllerResult());

        $event->setResponse($response);
    }

    public function onController(FilterControllerEvent $event)
    {
        if (!is_array($controller = $event->getController())) {
            return;
        }

        $request = $event->getRequest();
        if ($request->attributes->has('node')) {
            /** @var $node \Monolith\Bundle\CMSBundle\Entity\Node */
            $node = $request->attributes->get('node');

            //if (is_numeric($node)) {
            //    $node = $this->engineNodeManager->get($node);
            //}

            /*
            if ($this->moduleManager->has($node->getModule())) {
                // @todo сделать проверку на соотвествие параметров в ноде с параметрами метода контроллера

                $isValidRequiredParams = true;
                foreach ($this->moduleManager->get($node->getModule())->getRequiredParams() as $param) {
                    if (null === $node->getParam($param)) {
                        $isValidRequiredParams = false;
                    }
                }

                if (!$isValidRequiredParams) {
                    $controller[0] = new FrontEndController();
                    $controller[1] = 'moduleNotConfiguredAction';
                    $event->setController($controller);

                    return;
                }
            }
            */

            // @todo сделать поддержку кириллических путей.
            $folderPath = substr(str_replace($request->getBaseUrl(), '', $this->folderManager->getUri($node)), 1);

            if (false !== strrpos($folderPath, '/', strlen($folderPath) - 1)) {
                $folderPath = substr($folderPath, 0, strlen($folderPath) - 1);
            }

            $routeParams = $node->getControllerParams();
            $routeParams['_folderPath'] = $folderPath;

            $request->attributes->set('_route_params', $routeParams);

            /*
            if (method_exists($controller[0], 'setNode')) {
                $controller[0]->setNode($node);
                $this->twigLoader->setModuleTheme($node);
            }
            */

            $this->twigLoader->setModuleTheme($node);

            $this->contextManager->setCurrentNodeId($node->getId());
            //$request->attributes->remove('_node');
        }
    }

    public function onRequest(GetResponseEvent $event): void
    {
        if (HttpKernelInterface::MASTER_REQUEST === $event->getRequestType()) {
            try {
                date_default_timezone_set($this->settingsManager->get('cms:timezone'));
            } catch (\Exception $e) {
                date_default_timezone_set('Europe/Moscow');
            }
        }

        /*
        if (HttpKernelInterface::SUB_REQUEST === $event->getRequestType()) {
            $controller = explode(':', $event->getRequest()->attributes->get('_controller'));

            if (is_numeric($controller[0])) {
                $node = $this->engineNodeManager->get($controller[0]);

                $controllerName = isset($controller[1]) ? $controller[1] : null;
                $actionName = isset($controller[2]) ? $controller[2] : 'index';

                foreach ($node->getControllerTemp($controllerName, $actionName) as $key => $value) {
                    $event->getRequest()->attributes->set($key, $value);
                }

                $event->getRequest()->attributes->set('_node', $node);
            }
        }
        */
    }

    public function onResponse(FilterResponseEvent $event): void
    {
        $this->contextManager->setCurrentNodeId(null);
        $this->twigLoader->setModuleTheme(null);
    }
}
