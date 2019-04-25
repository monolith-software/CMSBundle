<?php

declare(strict_types=1);

namespace Monolith\Bundle\CMSBundle\Controller;

use Monolith\Bundle\CMSBundle\Entity\Node;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Twig\Error\LoaderError;

class FrontEndController extends Controller
{
    /**
     * @param Request    $request
     * @param string     $slug
     * @param array|null $options
     *
     * @return array|string|RedirectResponse|Response
     * @throws LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function runAction(Request $request, string $slug, array $options = null)
    {
        $twig       = $this->get('twig');
        $cmsContext = $this->get('cms.context');
        $site       = $this->get('cms.context')->getSite();

        // Кеширование роутера.
        $cache_key = md5('site_id='.$site->getId().'cms_router='.$request->getBaseUrl().$slug);
        if (null === $router_data = $this->get('cms.cache')->get($cache_key)) {
            $router_data = $this->get('cms.router')->match($request->getBaseUrl(), $slug, HttpKernelInterface::MASTER_REQUEST, $options);
            $this->get('cms.cache')->set($cache_key, $router_data, ['folder', 'node']);
        }

        if ($router_data['status'] == 301 and $router_data['redirect_to']) {
            return new RedirectResponse($router_data['redirect_to'], $router_data['status']);
        }

        if (empty($router_data['folders'])) { // Случай пустой инсталляции, когда еще ни одна папка не создана.
             $this->get('cms.toolbar')->prepare();

            return $twig->render('@CMS/welcome.html.twig');
        }

        $cmsContext->setTemplate($router_data['template']);

        if (!$this->get('cms.security')->checkForFoldersRouterData($router_data['folders'], 'read')) {
            $router_data['status'] = 403;
        }

        if ($router_data['status'] == 404) {
            $this->get('monolog.logger.request')->error('Page not found: '.$request->getUri());

            throw new NotFoundHttpException('Page not found.');
        } elseif ($router_data['status'] == 403) {
            throw new AccessDeniedHttpException('Access Denied.');
        }

        $this->get('html')->setMetas($router_data['meta']);

        foreach ($router_data['folders'] as $folderId => $folderData) { // @todo учёт локали
            $this->get('cms.breadcrumbs')->add($this->get('cms.folder')->getUri($folderId), $folderData['title'], $folderData['description']);
        }

        $cmsContext->setCurrentFolderId($router_data['current_folder_id']);
        $cmsContext->setCurrentFolderPath($router_data['current_folder_path']);

        // Список нод кешируется только при GET запросах.
        $router_data['http_method'] = $request->getMethod();

        $nodes = $this->get('cms.node')->buildList($router_data);

        \Profiler::start('Build Modules Data');
        // Разложенные по областям, отрендеренные ноды
        $nodesResponses = $this->get('cms.node')->buildModulesData($request, $nodes);
        \Profiler::end('Build Modules Data');

        if ($nodesResponses instanceof Response) {
            return $nodesResponses;
        }

        $this->get('cms.toolbar')->prepare($this->get('cms.node')->getFrontControls());

        try {
            return $twig->render($cmsContext->getTemplate().'.html.twig', $nodesResponses);
        } catch (LoaderError $e) {
            if ($this->get('kernel')->isDebug()) {
                return $twig->render('@CMS/error.html.twig', ['errors' => [$e->getMessage()]]);
            }
        }

        return $twig->render('@CMS/welcome.html.twig');
    }

    /**
     * Обработчик POST запросов.
     *
     * @param Request $request
     * @param string $slug
     *
     * @return RedirectResponse|Response
     *
     * @todo продумать! здесь же происходит "магия" с /admin/login/check
     *
     */
    public function postAction(Request $request, $slug): Response
    {
        // Получение $node_id
        $data = $request->request->all();
        $node_id = null;
        foreach ($data as $key => $value) {
            if ($key == '_node_id') {
                $node_id = $data['_node_id'];
                unset($data['_node_id']);
                break;
            }

            if (is_array($value) and array_key_exists('_node_id', $value)) {
                $node_id = $data[$key]['_node_id'];
                unset($data[$key]['_node_id']);
                break;
            }
        }

        foreach ($data as $key => $value) {
            $request->request->set($key, $value);
        }

        $node = $this->get('cms.node')->get((int) $node_id);

        if (!$node instanceof Node or !$node->isActive()) {
            throw new AccessDeniedHttpException('Node is not active.');
        }

        // @todo сделать здесь проверку на права доступа, а также доступность ноды в запрошенной папке.

        return $this->forward($node->getId().':'.$node->getController(), ['slug' => $slug]);
    }

    /**
     * @param Node $node
     *
     * @return Response
     */
    public function moduleNotConfiguredAction(Node $node)
    {
        return new Response('Module "'.$node->getModule().'" not yet configured. Node: '.$node->getId().'<br />');
    }

    /**
     * @param Request $request
     * @param int $node_id
     * @param string $slug
     *
     * @return Response
     */
    public function apiAction(Request $request, $node_id, $slug = null)
    {
        // @todo сделать проверку, доступна ли нода в папке т.к. папка может быть выключенной или пользователь не имеет к ней прав.

        $node = $this->get('cms.node')->get((int) $node_id);

        if (null === $node) {
            return $this->apiNotFoundAction();
        }

        try {
            $controller = $this->get('cms.router')->matchModuleApi($node->getModule(), '/'.$slug, $request);
        } catch (MethodNotAllowedException $e) {
            return new JsonResponse([
                'status'  => 'error',
                'message' => 'MethodNotAllowedException.',
                'data'    => [],
            ], 404);
        }

        if (null === $controller) {
            return $this->apiNotFoundAction();
        }

        $controller['node'] = $node;

        $subRequest = $this->get('request_stack')->getCurrentRequest()->duplicate(
            $request->query->all(),
            $request->request->all(),
            $controller,
            $request->cookies->all(),
            $request->files->all(),
            $request->server->all()
        );

        return $this->get('http_kernel')->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
    }

    /**
     * @return JsonResponse
     */
    public function apiNotFoundAction()
    {
        return new JsonResponse([
            'status'  => 'error',
            'message' => 'Некорректный запрос.',
            'data'    => [],
        ], 404);
    }
}
