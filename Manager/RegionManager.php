<?php

declare(strict_types=1);

namespace Monolith\Bundle\CMSBundle\Manager;

use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\ORM\EntityManager;
use Monolith\Bundle\CMSBundle\Entity\Region;
use Monolith\Bundle\CMSBundle\Form\Type\RegionFormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

class RegionManager
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @var \Doctrine\ORM\EntityRepository
     */
    protected $repository;

    /**
     * @var ContextManager
     */
    protected $cmsContext;

    /**
     * RegionManager constructor.
     *
     * @param EntityManager        $em
     * @param FormFactoryInterface $formFactory
     * @param ContextManager       $cmsContext
     */
    public function __construct(EntityManager $em, FormFactoryInterface $formFactory, ContextManager $cmsContext)
    {
        $this->em = $em;
        $this->cmsContext  = $cmsContext;
        $this->formFactory = $formFactory;
        $this->repository  = $em->getRepository(Region::class);

        $this->checkForDefault();
    }

    /**
     * @return Region[]
     */
    public function all(): array
    {
        return $this->repository->findBy(['site' => $this->cmsContext->getSite()], ['position' => 'ASC', 'name' => 'ASC']);
    }

    /**
     * Проверка на область по умолчанию.
     *
     * В случае если Область 'content' существует, возвращается TRUE.
     * Если нет, то создаётся и возвращается FALSE.
     *
     * @return bool
     */
    public function checkForDefault(): bool
    {
        try {
            if (!empty($this->cmsContext->getSite()) and
                empty($this->repository->findOneBy(['name' => 'content', 'site' => $this->cmsContext->getSite()]))) {
                $this->update(new Region('content', 'Content workspace', $this->cmsContext->getSite()));

                return false;
            }
        } catch (TableNotFoundException $e) {
            // @todo
        }

        return true;
    }
    
    /**
     * @param string|null $name
     * @param string|null $descr
     *
     * @return Region
     */
    public function create($name = null, $descr = null): Region
    {
        return new Region($name, $descr, $this->cmsContext->getSite());
    }

    /**
     * Creates and returns a Form instance from the type of the form.
     *
     * @param mixed $data    The initial data for the form
     * @param array $options Options for the form
     *
     * @return FormInterface
     */
    public function createForm($data = null, array $options = []): FormInterface
    {
        return $this->formFactory->create(RegionFormType::class, $data, $options);
    }

    /**
     * @param int $id
     *
     * @return Region|null
     */
    public function get(int $id): ?Region
    {
        return $this->repository->find($id);
    }

    /**
     * @param Region $entity
     */
    public function remove(Region $entity): void
    {
        if ('content' == $entity->getName()) {
            return;
        }

        $this->em->remove($entity);
        $this->em->flush($entity);
    }

    /**
     * @param Region $entity
     */
    public function update(Region $entity): void
    {
        $this->em->persist($entity);
        $this->em->flush($entity);
    }
}
