<?php

declare(strict_types=1);

namespace Monolith\Bundle\CMSBundle\Manager;

use Doctrine\ORM\EntityManager;
use Monolith\Bundle\CMSBundle\Annotation\NodePropertiesForm;
use Monolith\Bundle\CMSBundle\Cache\CacheWrapper;
use Monolith\Bundle\CMSBundle\CMSAppKernel;
use Monolith\Bundle\CMSBundle\Entity\Folder;
use Monolith\Bundle\CMSBundle\Entity\Node;
use Monolith\Bundle\CMSBundle\Entity\Region;
use Monolith\Bundle\CMSBundle\Form\Type\NodeDefaultPropertiesFormType;
use Monolith\Bundle\CMSBundle\Form\Type\NodeFormType;
use Monolith\Bundle\CMSBundle\Module\AbstractNodePropertiesFormType;
use Monolith\Bundle\CMSBundle\Module\ModuleBundle;
use Monolith\Bundle\CMSBundle\Twig\RegionRenderHelper;
use Symfony\Bundle\FrameworkBundle\Controller\ControllerTrait;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;

class NodeManager
{
    use ContainerAwareTrait;
    use ControllerTrait;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @var \Monolith\Bundle\CMSBundle\Repository\NodeRepository
     */
    protected $repository;

    /**
     * @var ContextManager
     */
    protected $context;

    /**
     * @var CMSAppKernel
     */
    protected $kernel;

    /**
     * Список всех нод, запрошенных в текущем контексте.
     *
     * @var Node[]
     *
     * @todo переименовать с $contextNodes
     */
    protected $nodes = [];

    /**
     * Коллекция фронтальных элементов управления.
     *
     * @var array
     *
     * @todo review
     */
    protected $front_controls = [];

    /**
     * Является ли нода только что созданной?
     *
     * Применяется для вызова метода createNode() модуля после создания ноды.
     *
     * @var bool
     *
     * @todo пересмотреть логику, может быть в сущности запоминать этот флаг?
     */
    protected $is_just_created = false;

    /**
     * @var CacheWrapper
     */
    protected $cache;

    /**
     * @param EntityManager $em
     * @param FormFactoryInterface $formFactory
     * @param KernelInterface $kernel
     * @param ContextManager $context
     * @param CacheWrapper $cache
     */
    public function __construct(
        EntityManager $em,
        FormFactoryInterface $formFactory,
        KernelInterface $kernel,
        ContextManager $context,
        CacheWrapper $cache
    ) {
        $this->context      = $context;
        $this->em           = $em;
        $this->formFactory  = $formFactory;
        $this->kernel       = $kernel;
        $this->repository   = $em->getRepository(Node::class);
        $this->cache        = $cache;
    }

    /**
     * @return Node
     */
    public function create(): Node
    {
        $this->is_just_created = true;

        return new Node();
    }

    /**
     * Creates and returns a Form instance from the type of the form.
     *
     * @param  mixed $data     The initial data for the form
     * @param  array $options  Options for the form
     *
     * @return \Symfony\Component\Form\FormInterface
     */
    public function createForm(Node $data = null, array $options = []): FormInterface
    {
        return $this->formFactory->create(NodeFormType::class, $data, $options);
    }

    /**
     * @param Region|int $region
     *
     * @return int
     */
    public function countInRegion($region): int
    {
        return $this->repository->countInRegion($region);
    }

    /**
     * @param  int $id
     *
     * @return Node|null
     */
    public function get(int $id): ?Node
    {
        if (null === $id) {
            return null;
        }

        if (isset($this->nodes[$id])) {
            return $this->nodes[$id];
        }

        $node = $this->repository->find($id);

        if (empty($node) or $node->isDeleted()) {
            return null;
        }

        return $node;
    }

    /**
     * @param Folder $folder
     *
     * @return array|Node[]
     */
    public function findInFolder(Folder $folder): array
    {
        return $this->repository->findBy(['folder' => $folder]);
    }

    /**
     * @param string $name
     *
     * @return array|Node[]
     */
    public function findByModule($name): array
    {
        return $this->repository->findBy(['module' => $name]);
    }

    /**
     * @param Node $node
     */
    public function update(Node $node): void
    {
        /** @var \Monolith\Bundle\CMSBundle\Module\ModuleBundle $module */
        $module = $this->kernel->getBundle($node->getModule());

        // Свежесозданная нода выполняет свои действия, а также устанавливает параметры по умолчанию.
        if ($this->is_just_created) {
            $module->createNode($node);

            $this->is_just_created = false;
        } else {
            $module->updateNode($node);
        }

        $this->em->persist($node);
        $this->em->flush($node);
    }

    /**
     * Получить имя класса формы параметров подключения модуля.
     *
     * @param  string $module_name
     *
     * @return string
     */
    public function getPropertiesFormType(Node $node)
    {
        $cmsNode = $this->container->get('cms.node');

        $module_name = $node->getModule();

        try {
            $moduleNamespace = $this->kernel->getModule($module_name)->getNamespace();
        } catch (\InvalidArgumentException $e) {
            // Случай, когда запрашивается не подключенный модуль.
        }

        $form_class_name = '\\'.$moduleNamespace.'\Form\Type\NodePropertiesFormType';

        $method = $cmsNode->getReflectionMethod($node, $node->getController());
        if ($method) {
            $annotation = $this->container->get('annotations.reader')->getMethodAnnotation($method, NodePropertiesForm::class);

            if (empty($annotation)) {
                // Формирование формы на основе параметров метода.
                $form_class_name = '';
            } else {
                $form_class_name2 = '\\'.$moduleNamespace.'\Form\Type\\'.$annotation->class;

                if (class_exists($annotation->class)) {
                    $form_class_name = $annotation->class;
                } elseif (class_exists($form_class_name2)) {
                    $form_class_name = $form_class_name2;
                }
            }
        }

        if (class_exists($form_class_name)) {
            //return new $form_class_name($this->em, $this->kernel);
            return $form_class_name;
        }

        // @todo может быть гибче настраивать форму параметров по умолчанию?.
        return NodeDefaultPropertiesFormType::class;
    }

    /**
     * Создание списка всех запрошеных нод, в каких областях они находятся и с какими
     * параметрами запускаются модули.
     *
     * @param  array  $router_data
     *
     * @return Node[]
     */
    public function buildList(array $router_data): array
    {
        if (!empty($this->nodes)) {
            return $this->nodes;
        }

        $cmsSecurity = $this->container->get('cms.security');

        $this->nodes = [];

        // Try to get nodes from cache
        if ($router_data['http_method'] == 'GET') {
            if ($cmsSecurity->isSuperAdmin()) {
                $userGroups = 'ROLE_SUPER_ADMIN';
            } else {
                $userGroups = serialize($cmsSecurity->getUserGroups());
            }

            $cache_key = md5('cms_node_list'.serialize($router_data).$userGroups);

            if (null === $this->nodes = $this->cache->get($cache_key)) {
                $this->nodes = [];
            } else {
                // Обход странного бага с кешем нод.
                foreach ($this->nodes as $node) {
                    if (empty($node->getRegion())) {
                        $this->nodes = [];

                        goto Bad_Cache;
                    }
                }

                return $this->nodes;

                Bad_Cache:
            }
        }

        \Profiler::start('Build Nodes List');

        $used_nodes = [];
        $lockout_nodes = [   // @todo блокировку нод.
            'single'  => [], // Блокировка нод в папке, без наследования.
            'inherit' => [], // Блокировка нод в папке, с наследованием.
            'except'  => []  // Блокировка всех нод в папке, кроме заданных.
        ];

        foreach ($router_data['folders'] as $folderId => $_dummy) {
            $folder = $this->em->find(Folder::class, $folderId);

            if (empty($folder)) {
                throw new \Exception('Папка не найдена! Не штатная ситуация.'); // @todo
            }

            // @todo блокировку нод.
            // Режим 'single' каждый раз сбрасывается и устанавливается заново для каждой папки.
            /*
            $lockout_nodes['single'] = [];
            if (isset($parsed_uri_value['lockout_nodes']['single']) and !empty($parsed_uri_value['lockout_nodes']['single'])) {
                //$lockout_nodes['single'] = $parsed_uri_value['lockout_nodes']['single'];
                $tmp = explode(',', $parsed_uri_value['lockout_nodes']['single']);
                foreach ($tmp as $single_value) {
                    $t = trim($single_value);
                    if (!empty($t)) {
                        $lockout_nodes['single'][trim($single_value)] = 'blocked'; // ставлю тупо 'blocked', но главное в массиве с блокировками, это индексы.
                    }
                }
            }

            // Блокировка нод в папке, с наследованием.
            if (isset($parsed_uri_value['lockout_nodes']['inherit']) and !empty($parsed_uri_value['lockout_nodes']['inherit'])) {
                $tmp = explode(',', $parsed_uri_value['lockout_nodes']['inherit']);
                foreach ($tmp as $inherit_value) {
                    $t = trim($inherit_value);
                    if (!empty($t)) {
                        $lockout_nodes['inherit'][trim($inherit_value)] = 'blocked'; // ставлю тупо 'blocked', но главное в массиве с блокировками, это индексы.
                    }
                }
            }

            // Блокировка всех нод в папке, кроме заданных.
            if (isset($parsed_uri_value['lockout_nodes']['except']) and !empty($parsed_uri_value['lockout_nodes']['except'])) {
                $tmp = explode(',', $parsed_uri_value['lockout_nodes']['except']);
                foreach ($tmp as $except_value) {
                    $t = trim($except_value);
                    if (!empty($t)) {
                        $lockout_nodes['except'][trim($except_value)] = 'blocked'; // ставлю тупо 'blocked', но главное в массиве с блокировками, это индексы.
                    }
                }
            }
            */

            if ($folder->getId() == $this->context->getCurrentFolderId()) { // Обработка текущей папки.
                $result = $this->repository->getInFolder($folder, $used_nodes);
            } elseif ($folder->getRegions()->count() > 0) { // В этой папке есть ноды, которые наследуются...
                $result = $this->repository->getInheritedInFolder($folder);
            } else { // В папке нет нод для сборки.
                continue;
            }

            while ($node_id = $result->fetchColumn(0)) {
                // Создаётся список нод, которые уже в включены.
                if ($folder->getRegions()->count() > 0) {
                    $used_nodes[] = $node_id;
                }

                $this->nodes[$node_id] = $node_id;
            }
        }

        foreach ($lockout_nodes['single'] as $node_id) {
            unset($this->nodes[$node_id]);
        }

        foreach ($lockout_nodes['inherit'] as $node_id) {
            unset($this->nodes[$node_id]);
        }

        if (!empty($lockout_nodes['except'])) {
            foreach ($this->nodes as $node_id) {
                if (!array_key_exists($node_id, $lockout_nodes['except'])) {
                    unset($this->nodes[$node_id]);
                }
            }
        }

        // Заполнение массива с нодами сущностями нод.
        foreach ($this->repository->findIn($this->nodes) as $node) {
            if (!$cmsSecurity->checkForRegionAccess($node->getRegion()) or !$cmsSecurity->checkForNodeAccess($node)) {
                unset($this->nodes[$node->getId()]);
                continue;
            }

            if (isset($router_data['node_routing']['controller'])
                and $router_data['node_routing']['node_id'] == $node->getId()
            ) {
                $node->setControllerTemp($router_data['node_routing']['controller']);
                $node->setPriority(255);
            }

            $this->nodes[$node->getId()] = $node;
        }

        \Profiler::end('Build Nodes List');

        // Store nodes to cache
        if ($router_data['http_method'] == 'GET' and $this->container->get('smart_core.settings.manager')->get('cms:enable_node_cache')) {
            $this->cache->set($cache_key, $this->nodes, ['folder', 'node']);
        }

        return $this->nodes;
    }

    /**
     * Сборка "областей" из подготовленного списка нод.
     * По мере прохождения, подключаются и запускаются нужные модули с нужными параметрами.
     *
     * @param Request $request
     * @param Node[]  $nodes
     *
     * @return array|Response|RedirectResponse
     *
     * // @todo убрать из контента обёртки для фронт админки
     */
    public function buildModulesData(Request $request, array $nodes)
    {
        $prioritySorted = [];
        $nodesResponses = [];

        foreach ($nodes as $node) {
            if (!isset($nodesResponses[$node->getRegionName()])) {
                $nodesResponses[$node->getRegionName()] = new RegionRenderHelper();
            }

            $prioritySorted[$node->getPriority()][$node->getId()] = $node;
            $nodesResponses[$node->getRegionName()]->{$node->getId()} = new Response();
        }

        krsort($prioritySorted);

        foreach ($prioritySorted as $nodes) {
            /** @var \Monolith\Bundle\CMSBundle\Entity\Node $node */
            foreach ($nodes as $node) {
                if ($this->isGranted('ROLE_ADMIN') and $node->getIsUseEip()) {
                    $node->setEip(true);
                }

                // Выполняется модуль, все параметры ноды берутся в \Monolith\Bundle\CMSBundle\Listener\ModuleControllerModifierListener
                \Profiler::start($node->getId().' '.$node->getModule(), 'node');

                if ($node->getController() === null) { // @todo убрать
                    $moduleResponse = new Response('Module controller "'.$node->getModule().'" is unavailable.');
                } elseif ($this->container->get('cms.module')->has($node->getModule())) {
                    $moduleResponse = $this->forward($node->getId(), [
                        '_route' => 'cms_frontend_run',
                        '_route_params' => $node->getParams() + $request->attributes->get('_route_params'),
                    ], $request->query->all());

                    // Обрамление ноды пользовательским кодом.
                    $moduleResponse->setContent($node->getCodeBefore().$moduleResponse->getContent().$node->getCodeAfter());
                } else {
                    $moduleResponse = new Response('Module "'.$node->getModule().'" is unavailable.');
                }

                \Profiler::end($node->getId().' '.$node->getModule(), 'node');

                if ($moduleResponse instanceof RedirectResponse
                    or ($moduleResponse instanceof Response and $moduleResponse->isNotFound())
                    or 0 === strpos($moduleResponse->getContent(), '<!DOCTYPE ') // @todo Пока так определяются ошибки от симфони.
                ) {
                    return $moduleResponse;
                }

                // @todo сделать отправку front_controls в ответе метода.
                if ($this->isGranted('ROLE_ADMIN')) {
                    $this->front_controls['node']['__node_'.$node->getId()] = $node->getFrontControls();
                    $this->front_controls['node']['__node_'.$node->getId()]['cms_node_properties'] = [
                        'title' => 'Параметры модуля '.$node->getModule(),
                        'uri'   => $this->generateUrl('cms_admin_structure_node_properties', ['id' => $node->getId()]),
                    ];
                }

                if ($this->isGranted('ROLE_ADMIN') and $node->getIsUseEip()) {
                    $moduleResponse->setContent(
                        "\n<div class=\"cms-frontadmin-node\" id=\"__node_{$node->getId()}\" data-module=\"{$node->getModule()}\">\n".$moduleResponse->getContent()."\n</div>\n"
                    );
                }

                $nodesResponses[$node->getRegionName()]->{$node->getId()} = $moduleResponse;
            }
        }

        return $nodesResponses;
    }

    /**
     * @param Node $node
     *
     * @return \ReflectionMethod[]
     */
    public function getReflectionMethods(Node $node): array
    {
        $bundle = $this->container->get('kernel')->getBundle($node->getModule());
        $controllersDir = $bundle->getPath().'/Controller/';

        $methods = [];

        /** @var SplFileInfo $file */
        foreach (Finder::create()->in($controllersDir)->files() as $file) {
            $controllerName = substr($file->getRelativePathname(), 0, -4);

            $className = $bundle->getNamespace().'\\Controller\\'.str_replace('/', '\\', $controllerName);

            if (class_exists($className)) {
                $reflector = new \ReflectionClass($className);

                foreach ($reflector->getMethods() as $method) {
                    if ($method->isPublic()
                        and !$method->isAbstract()
                        and $method->class == $className
                        and 'Action' == substr($method->getName(), -6)
                    ) {
                        foreach ($method->getParameters() as $parameter) {
                            if (!empty($parameter->getType()) and $parameter->getType()->getName() == 'Monolith\Bundle\CMSBundle\Entity\Node') {
                                $methods[substr($controllerName, 0, -10).':'.substr($method->name, 0, -6)] = $method;
                            }
                        }
                    }
                }
            }
        }

        return $methods;
    }

    /**
     * @param Node   $node
     * @param string $controller
     *
     * @return null|\ReflectionMethod
     */
    public function getReflectionMethod(Node $node, string $controller): ?\ReflectionMethod
    {
        $methods = $this->getReflectionMethods($node);

        if (isset($methods[$controller])) {
            return $methods[$controller];
        }

        return null;
    }

    /**
     * @return mixed
     */
    public function getFrontControls(): array
    {
        return isset($this->front_controls['node']) ? $this->front_controls['node'] : [];
    }

    /**
     * @return Node[]
     */
    public function getNodes(): array
    {
        return $this->nodes;
    }

    /**
     * @param Node $node
     */
    public function remove(Node $node): void
    {
        try {
            $module = $this->kernel->getBundle($node->getModule());

            if ($module instanceof ModuleBundle) {
                $module->deleteNode($node);
            }
        } catch (\InvalidArgumentException $e) {
            // do nothing
        }

        $this->em->remove($node);
        $this->em->flush($node);
    }
}
