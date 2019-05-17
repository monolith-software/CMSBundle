<?php

declare(strict_types=1);

namespace Monolith\Bundle\CMSBundle\Controller;

use Monolith\Bundle\CMSBundle\Entity\AppearanceHistory;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Smart\CoreBundle\Controller\Controller;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\MimeType\FileinfoMimeTypeGuesser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Error\SyntaxError;
use Twig\Source;

/**
 * @Security("is_granted('ROLE_ADMIN_SYSTEM') or has_role('ROLE_SUPER_ADMIN')")
 */
class AdminThemeController extends Controller
{
    /**
     * @return Response
     */
    public function indexAction(): Response
    {
        return $this->render('@CMS/Admin/Theme/index.html.twig', [
            'themes' => $this->get('cms.theme')->all(),
        ]);
    }

    /**
     * @param string $theme
     * @param string $filename
     *
     * @return Response
     */
    public function getScreenshotAction(string $theme, string $filename): Response
    {
        $response = new Response();

        $themeDir = $this->container->getParameter('kernel.project_dir').'/themes/'.$theme;

        $screenshotsDir = $themeDir.'/screenshots/';

        if (file_exists($screenshotsDir.$filename)) {
            $mimeTypeGuesser = new FileinfoMimeTypeGuesser();

            $response->headers->set('Content-Type', $mimeTypeGuesser->guess($screenshotsDir.$filename));

            $response->setContent(file_get_contents($screenshotsDir.$filename));
        }

        return $response;
    }

    /**
     * @param string $theme
     *
     * @return Response
     */
    public function themeAction(string $name): Response
    {
        $theme = $this->get('cms.theme')->get($name);

        if (empty($theme)) {
            throw $this->createNotFoundException('Theme is not exist: '.$name);
        }

        $themeDir = $this->container->getParameter('kernel.project_dir').'/themes/'.$name;

        try {
            $bundles = Finder::create()->files()->in($themeDir.'/bundles/')->sortByName();
        } catch (\InvalidArgumentException $e) {
            $bundles = [];
        }

        try {
            $modules = Finder::create()->files()->in($themeDir.'/modules/')->sortByName();
        } catch (\InvalidArgumentException $e) {
            $modules = [];
        }

        try {
            $translations = Finder::create()->files()->in($themeDir.'/translations/')->sortByName();
        } catch (\InvalidArgumentException $e) {
            $translations = [];
        }

        try {
            $views = Finder::create()->files()->in($themeDir.'/views/')->sortByName();
        } catch (\InvalidArgumentException $e) {
            $views = [];
        }

        try {
            $public = Finder::create()->files()->in($themeDir.'/public/')->sortByName();
        } catch (\InvalidArgumentException $e) {
            $public = [];
        }

        return $this->render('@CMS/Admin/Theme/theme.html.twig', [
            'theme'         => $theme,
            'bundles'       => $bundles,
            'modules'       => $modules,
            'views'         => $views,
            'public'        => $public,
            'translations'  => $translations,
        ]);
    }

    /**
     * @param string $theme
     * @param string $dir
     * @param string $relativePathname
     *
     * @return Response
     */
    public function fileEditAction(Request $request, string $theme, string $dir, string $relativePathname)
    {
        $themeName = $theme;
        $theme = $this->get('cms.theme')->get($theme);

        if (empty($theme)) {
            throw $this->createNotFoundException('Theme is not exist: '.$theme);
        }

        $themeDir = $this->container->getParameter('kernel.project_dir').'/themes/'.$theme['dirname'];

        $filepath = $themeDir.'/'.$dir.'/'.$relativePathname;

        if (!file_exists($filepath)) {
            throw $this->createNotFoundException('File is not exist: '.$filepath);
        }

        if ($request->isMethod('POST')) {
            $code = $request->request->get('code');

            try {
                if (pathinfo($relativePathname)['extension'] == 'twig') {
                    $source = new Source($code, basename($relativePathname), $filepath);

                    $twig = $this->get('twig');
                    $twig->parse($twig->tokenize($source));

                    $twig->compileSource($source);
                }

                $history = new AppearanceHistory();
                $history
                    ->setPath('/Resources/views/')
                    ->setFilename($relativePathname.'.html.twig')
                    ->setCode($code)
                    ->setUser($this->getUser())
                ;

                $this->persist($history, true);

                file_put_contents($filepath, $code);

                $this->addFlash('success', 'Файл обновлён.');

                return $this->redirectToRoute('cms_admin_theme_show', ['name' => $themeName]);
            } catch (SyntaxError $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        $file = new File($filepath);

        $code = file_get_contents($filepath);

        $fileExtension = $file->getExtension();
        if ($fileExtension == 'yml') {
            $fileExtension = 'yaml';
        }

        return $this->render('@CMS/Admin/Theme/file_edit.html.twig', [
            'code' => $code,
            'dir'  => $dir,
            'fileExtension' => $fileExtension,
            'relativePathname' => $relativePathname,
            'theme' => $theme,
        ]);
    }
}
