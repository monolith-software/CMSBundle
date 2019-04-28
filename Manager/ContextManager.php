<?php

declare(strict_types=1);

namespace Monolith\Bundle\CMSBundle\Manager;

use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\ORM\ORMException;
use FOS\UserBundle\Model\UserManagerInterface;
use Monolith\Bundle\CMSBundle\Entity\Domain;
use Monolith\Bundle\CMSBundle\Entity\Language;
use Monolith\Bundle\CMSBundle\Entity\Site;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

class ContextManager
{
    use ContainerAwareTrait;

    protected $current_folder_id    = 1;
    protected $current_folder_path  = '/';
    protected $current_node_id      = null;
    protected $domain               = null;
    protected $site                 = null;
    protected $template             = 'default';
    protected $userManager          = null;

    /**
     * ContextManager constructor.
     *
     * @param ContainerInterface $container
     * @param UserManagerInterface $userManager
     *
     * @todo кешироание
     */
    public function __construct(ContainerInterface $container, UserManagerInterface $userManager)
    {
        $this->container   = $container;
        $this->userManager = $userManager;

        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $this->container->get('doctrine.orm.entity_manager');
        $siteRepository = $em->getRepository('CMSBundle:Site');

        $request = $container->get('request_stack')->getMasterRequest();

        if ($request instanceof Request) {
            $hostname = $request->server->get('HTTP_HOST');

            $func = 'idn_to_ascii';
            if (strpos($hostname, 'xn--') !== false) {
                $func = 'idn_to_utf8';
            }

            $hostname = $func($hostname, 0, INTL_IDNA_VARIANT_UTS46);

            $this->domain = $em->getRepository('CMSBundle:Domain')->findOneBy(['name' => $hostname, 'is_enabled' => true]);

            if ($this->domain) {
                if ($this->domain->getParent()) { // Alias
                    $this->site = $siteRepository->findOneBy(['domain' => $this->domain->getParent()]);
                } else {
                    $this->site = $siteRepository->findOneBy(['domain' => $this->domain]);
                }
            }

            $this->setCurrentFolderPath($request->getBasePath().'/');
        }

        if (empty($this->site)) {
            try {
                $this->site = $siteRepository->findOneBy([], ['id' => 'ASC']);
            } catch (TableNotFoundException $e) {
                //echo "!!! Table 'cms_sites' Not Found.";
            }
        }
    }

    /**
     * @return array
     */
    public function getSiteSwitcher(): array
    {
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $this->container->get('doctrine.orm.entity_manager');

        $siteSwitcher = [];
        $sites = $em->getRepository('CMSBundle:Site')->findBy(['is_enabled' => true], ['position' => 'ASC', 'name' => 'ASC']);

        $rewriteSiteDomains = $this->container->getParameter('cms_sites_domains');

        foreach ($sites as $site) {
            $siteSwitcher[$site->getId()] = [
                'id'       => $site->getId(),
                'name'     => $site->getName(),
                'domain'   => (string) $site->getDomain(),
                'selected' => $site->getId() == $this->getSite()->getId() ? true : false,
            ];

            if (isset($rewriteSiteDomains[$site->getId()]) and !empty($rewriteSiteDomains[$site->getId()])) {
                $siteSwitcher[$site->getId()]['domain'] = $rewriteSiteDomains[$site->getId()];
            }
        }

        return $siteSwitcher;
    }
    
    /**
     * @param int $current_folder_id
     *
     * @return $this
     */
    public function setCurrentFolderId(int $current_folder_id): ContextManager
    {
        $this->current_folder_id = $current_folder_id;

        return $this;
    }

    /**
     * @return int
     */
    public function getCurrentFolderId(): int
    {
        return $this->current_folder_id;
    }

    /**
     * @param string $current_folder_path
     *
     * @return $this
     */
    public function setCurrentFolderPath(string $current_folder_path): ContextManager
    {
        $this->current_folder_path = $current_folder_path;

        return $this;
    }

    /**
     * @return string
     */
    public function getCurrentFolderPath(): string
    {
        return $this->current_folder_path;
    }

    /**
     * @param int $current_node_id
     *
     * @return $this
     */
    public function setCurrentNodeId(?int $current_node_id): ContextManager
    {
        $this->current_node_id = $current_node_id;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getCurrentNodeId(): ?int
    {
        return $this->current_node_id;
    }

    /**
     * @return string
     */
    public function getTemplate(): string
    {
        return $this->template;
    }

    /**
     * @param string $template
     *
     * @return $this
     */
    public function setTemplate(string $template): ContextManager
    {
        $this->template = $template;

        return $this;
    }

    /**
     * @return UserManagerInterface|null
     */
    public function getUserManager(): ?UserManagerInterface
    {
        return $this->userManager;
    }

    /**
     * @return Site|null
     */
    public function getSite(): ?Site
    {
        return $this->site;
    }

    /**
     * @return int|null
     */
    public function getSiteId(): ?int
    {
        return $this->site ? $this->site->getId() : null;
    }

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @return $this
     *
     * @deprecated
     */
    public function ____set($key, $value)
    {
        $this->$key = $value;

        return $this;
    }
}
