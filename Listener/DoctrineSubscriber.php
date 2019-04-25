<?php

declare(strict_types=1);

namespace Monolith\Bundle\CMSBundle\Listener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Monolith\Bundle\CMSBundle\Entity\Folder;
use Monolith\Bundle\CMSBundle\Entity\Node;
use Monolith\Bundle\CMSBundle\Entity\Region;
use Monolith\Bundle\CMSBundle\Entity\Syslog;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @see http://devacademy.ru/posts/sobytiia-zhizniennogho-tsikla-v-doctrine-2/
 * @see https://www.doctrine-project.org/projects/doctrine-orm/en/2.6/reference/events.html
 */
class DoctrineSubscriber implements EventSubscriber
{
    use ContainerAwareTrait;

    protected $isCalled;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->isCalled = false;
        $this->container = $container;
    }

    /**
     * @return array
     */
    public function getSubscribedEvents(): array
    {
        return [
            'prePersist',
            'postPersist',
            'preUpdate',
            'preFlush',
            'onFlush',
            'postFlush',
        ];
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        /*
        if ($this->isCalled) {
            return;
        }

        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if (
                $entity instanceof Folder
                or $entity instanceof Node
                or $entity instanceof Region
            ) {

                $fp = fopen("d:\getScheduledEntityInsertions.txt", "a+");
                fputs ($fp, 'create -> '.get_class($entity).': '.$entity->getId()."\n");
                fclose ($fp);

//                $this->syslog($entity, 'create');
            }
        }
        */
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        /*
        $entity = $args->getEntity();

        if (
            $entity instanceof Folder
            or $entity instanceof Node
            or $entity instanceof Region
        ) {

            $fp = fopen("d:\postPersist.txt", "a+");
            fputs ($fp, 'create -> '.get_class($entity).': '.$entity->getId()."\n");
            fclose ($fp);

            $this->syslog($entity, 'postPersist');
        }
        */
    }
    
    public function postFlush(PostFlushEventArgs $args): void
    {
        /*
        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if (
                $entity instanceof Folder
                or $entity instanceof Node
                or $entity instanceof Region
            ) {

                $fp = fopen("d:\postFlush_getScheduledEntityInsertions.txt", "a+");
                fputs ($fp, 'create -> '.get_class($entity).': '.$entity->getId()."\n");
                fclose ($fp);

                //                $this->syslog($entity, 'create');
            }
        }
        */
    }

    public function preFlush(PreFlushEventArgs $args): void
    {
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function prePersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getEntity();

        if ($entity instanceof Folder) {
            $this->container->get('cms.folder')->checkRelations($entity);
        }
    }

    /**
     * @param PreUpdateEventArgs $args
     *
     * @throws \Exception
     */
    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getEntity();

        if ($entity instanceof Folder) {
            $this->container->get('cms.folder')->checkRelations($entity);
        }

        $this->syslog($entity, 'update');
    }

    /**
     * @param object $entity
     * @param string $action
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function syslog($entity, string $action): void
    {
        if ($this->isCalled) {
            return;
        }

        $this->isCalled = true;

        $this->container->get('cms.syslog')->add($entity, $action);
    }
}
