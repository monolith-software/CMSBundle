<?php

declare(strict_types=1);

namespace Monolith\Bundle\CMSBundle\Form\Extension;

use Monolith\Bundle\CMSBundle\Manager\ContextManager;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;

class FormTypeNodeIdExtension extends AbstractTypeExtension
{
    /**
     * @var ContextManager
     */
    protected $context;

    public function __construct($context)
    {
        $this->context = $context;
    }

    /**
     * Adds a Node ID field to the root form view.
     *
     * @param FormView      $view    The form view
     * @param FormInterface $form    The form
     * @param array         $options The options
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $data = $this->context->getCurrentNodeId();

        if (!$view->parent && $options['compound'] and !empty($data)) {
            $factory = $form->getConfig()->getFormFactory();

            $form = $factory->createNamed($options['node_id_field_name'], 'hidden', $data, ['mapped' => false]);

            $view->children[$options['node_id_field_name']] = $form->createView($view);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'node_id_field_name' => '_node_id',
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function getExtendedType()
    {
        return 'form'; // extend the general "form" type, not some specific form
    }
}
