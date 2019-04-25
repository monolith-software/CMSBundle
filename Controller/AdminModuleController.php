<?php

declare(strict_types=1);

namespace Monolith\Bundle\CMSBundle\Controller;

use Monolith\Bundle\CMSBundle\Entity\Module;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Smart\CoreBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Security("is_granted('ROLE_ADMIN_MODULE') or has_role('ROLE_SUPER_ADMIN')")
 */
class AdminModuleController extends Controller
{
    public function indexAction($mode = 'all'): Response
    {
        //$modules = $this->getRepository(Module::class)->findBy([], ['name' => 'ASC']);

        $modules = [];
        foreach ($this->get('cms.module')->all() as $module) {
            if ($mode != 'all') {
                if ($mode == 'enabled' and !$module->isEnabled()) {
                    continue;
                }

                if ($mode == 'disabled' and $module->isEnabled()) {
                    continue;
                }
            }

            $data = [
                'bundle' => $module->getShortName(),
                'path' => $module->getPath(),
                'description' => '',
                'version' => '',
                'homepage' => '',
                'time' => '',
                'require' => '',
            ];

            $composerJson = $module->getPath().'/composer.json';

            if (file_exists($composerJson)) {
                $composerJson = json_decode(file_get_contents($composerJson), true);

                if (isset($composerJson['description'])) {
                    $data['description'] = $composerJson['description'];
                }

                if (isset($composerJson['extra']['description_'.$this->getParameter('locale')])) {
                    $data['description'] = $composerJson['extra']['description_'.$this->getParameter('locale')];
                }

                if (isset($composerJson['version'])) {
                    $data['version'] = $composerJson['version'];
                }

                if (isset($composerJson['homepage'])) {
                    $data['homepage'] = $composerJson['homepage'];
                }

                if (isset($composerJson['time'])) {
                    $data['time'] = $composerJson['time'];
                }

                if (isset($composerJson['require'])) {
                    $data['require'] = $composerJson['require'];
                }
            }

            $modules[$module->getName()] = $data;
        }

        ksort($modules);

        return $this->render('@CMS/Admin/Module/index.html.twig', [
            'modules' => $modules
        ]);
    }

    public function enabledAction(): Response
    {
        return $this->indexAction('enabled');
    }

    public function disabledAction(): Response
    {
        return $this->indexAction('disabled');
    }
}
