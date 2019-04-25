<?php

declare(strict_types=1);

namespace Monolith\Bundle\CMSBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PermissionsUpdateFoldersCommand extends ContainerAwareCommand
{
    protected function configure(): void
    {
        $this
            ->setName('cms:permissions:update-folders')
            ->setDescription('Update all folders permissions with default value from user groups.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        // @todo предупрежения, подтверждение, инвалидация кеша

        $this->getContainer()->get('cms.security')->updateAllFoldersByDefaults();
    }
}
