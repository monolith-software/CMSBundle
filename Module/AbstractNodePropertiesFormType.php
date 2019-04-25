<?php

namespace Monolith\Bundle\CMSBundle\Module;

use Doctrine\ORM\EntityManager;
use Monolith\Bundle\CMSBundle\Container;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractNodePropertiesFormType extends AbstractType
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var KernelInterface
     */
    protected $kernel;

    /**
     * @param EntityManager   $em
     * @param KernelInterface $kernel
     */
    //public function __construct(EntityManager $em, KernelInterface $kernel)
    public function __construct()
    {
        $this->em       = Container::get('doctrine.orm.entity_manager');
        $this->kernel   = Container::get('kernel');
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
        ]);
    }

    /**
     * @param string $entityName
     *
     * @return array
     */
    protected function getChoicesByEntity($entityName, $only_for_site = false)
    {
        $criteria = [];

        if ($only_for_site) {
            $site  = Container::get('cms.context')->getSite();

            $criteria = ['site' => $site];
        }

        $choices = [];
        foreach ($this->em->getRepository($entityName)->findBy($criteria) as $choice) {
            $choices[(string) $choice] = $choice->getId();
        }

        return $choices;
    }

    /**
     * @return string
     */
    public static function getTemplate()
    {
        return '@CMS/Admin/Structure/node_properties_form.html.twig';
    }
}
