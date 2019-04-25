<?php

declare(strict_types=1);

namespace Monolith\Bundle\CMSBundle\Manager;

use Monolith\Bundle\CMSBundle\Entity\Folder;
use Monolith\Bundle\CMSBundle\Entity\Node;
use Monolith\Bundle\CMSBundle\Entity\Region;
use Monolith\Bundle\CMSBundle\Entity\Syslog;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SyslogManager
{
    use ContainerAwareTrait;

    /**
     * SyslogManager constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function add($entity, string $action)
    {
        if ($entity instanceof Folder) {
            $bundle = 'CMS';
            $class  = 'Folder';
        } elseif ($entity instanceof Node) {
            $bundle = 'CMS';
            $class  = 'Node';
        } elseif ($entity instanceof Region) {
            $bundle = 'CMS';
            $class  = 'Region';
        } else {
            return;
        }

        if (!$this->container->has('security.token_storage')) {
            $user = null;
        }

        if (null === $token = $this->container->get('security.token_storage')->getToken()) {
            $user = null;
        } elseif (!is_object($user = $token->getUser())) {
            // e.g. anonymous authentication
            $user = null;
        }

        //$fp = fopen("d:\syslog_persist.txt", "a+");
        //fputs ($fp, 'update -> '.get_class($entity).': '.$entity->getId()." by {$user->getId()}\n");
        //fclose ($fp);

        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $this->container->get('doctrine.orm.entity_manager');

        $uow = $em->getUnitOfWork();
        $uow->computeChangeSets();

        if ($uow->isEntityScheduled($entity)) {
            $old = [];
            $new = [];

            $changes = $uow->getEntityChangeSet($entity);
            /*
            if (isset($changes['connection_error_datetime'])) {
                unset($changes['connection_error_datetime']);
            }
            */

            if (!empty($changes)) {
                foreach ($changes as $key => $val) {
                    if ($val[0] instanceof \DateTime) {
                        $val[0] = $val[0]->format('Y-m-d H:i:s');
                    }

                    if ($val[1] instanceof \DateTime) {
                        $val[1] = $val[1]->format('Y-m-d H:i:s');
                    }

                    if (is_array($val[0])) {
                        $old[$key] = $val[0];
                    } elseif ($val[0] instanceof Folder) {
                        $old[$key] = $val[0]->getId();
                    } else {
                        $old[$key] = (string) $val[0];
                    }

                    if (is_array($val[1])) {
                        $new[$key] = $val[1];
                    } elseif ($val[1] instanceof Folder) {
                        $new[$key] = $val[1]->getId();
                    } else {
                        $new[$key] = (string) $val[1];
                    }
                }

                if (empty($this->container->get('request_stack')->getMasterRequest())) {
                    $ip = '127.0.0.1';
                } else{
                    $ip = $this->container->get('request_stack')->getMasterRequest()->getClientIp();
                }

                $syslog = new Syslog();
                $syslog
                    ->setUser($user)
                    ->setOldValue($old)
                    ->setNewValue($new)
                    ->setAction($action)
                    ->setBundle($bundle)
                    ->setEntity($class)
                    ->setEntityId($entity->getId())
                    ->setIpAddress($ip)
                ;

                $em->persist($syslog);
                $em->flush($syslog);
            }
        }
    }

    /**
     * @param $entity
     *
     * @return Syslog|null
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function create($entity): ?Syslog
    {
        if (!$this->container->has('security.token_storage')) {
            $user = null;
        }

        if (null === $token = $this->container->get('security.token_storage')->getToken()) {
            $user = null;
        }

        if (!is_object($user = $token->getUser())) {
            // e.g. anonymous authentication
            $user = null;
        }

        $syslog = new Syslog();
        $syslog
            ->setUser($user)
            ->setAction('create')
            ->setOldValue([])
            ->setEntityId(0)
            ->setIpAddress($this->container->get('request_stack')->getMasterRequest()->getClientIp())
        ;

        if ($entity instanceof Folder) {
            $bundle = 'CMS';
            $class  = 'Folder';

            $entity->updatePermissionsCache();

            $data = [
                'title' => $entity->getTitle(),
                'uri_part' => $entity->getUriPart(),
                'parent_folder' => $entity->getParentFolder() ? $entity->getParentFolder()->getId() : null,
                'user' => $user ? $user->getId() : null,
                'is_file' => $entity->isFile(),
                'meta' => $entity->getMeta(),
                'redirect_to' => $entity->getRedirectTo(),
                'template_inheritable' => $entity->getTemplateInheritable(),
                'template_self' => $entity->getTemplateSelf(),
                'is_active' => $entity->getIsActive(),
                'description' => $entity->getDescription(),
                'position' => $entity->getPosition(),
                'permissions_cache' => $entity->getPermissionsCache(),
            ];
        } elseif ($entity instanceof Node) { // @todo
            $bundle = 'CMS';
            $class  = 'Node';
        } elseif ($entity instanceof Region) { // @todo
            $bundle = 'CMS';
            $class  = 'Region';
        } else {
            return null;
        }

        $syslog
            ->setNewValue($data)
            ->setBundle($bundle)
            ->setEntity($class)
        ;

        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $this->container->get('doctrine.orm.entity_manager');

        $em->persist($syslog);
        $em->flush($syslog);

        return $syslog;
    }

    /**
     * @param Syslog $syslog
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function updateSyslogEntity(Syslog $syslog)
    {
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $this->container->get('doctrine.orm.entity_manager');

        $em->persist($syslog);
        $em->flush($syslog);
    }
}
