<?php

declare(strict_types=1);

namespace Monolith\Bundle\CMSBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CreateThemesSymlinkCommand extends ContainerAwareCommand
{
    protected function configure(): void
    {
        $this
            ->setName('cms:themes:create-symlinks')
            ->setDescription('Create symlinks from Themes public to SiteBundle public.')
            ->addOption('relative', null, InputOption::VALUE_NONE, 'Make relative symlinks')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        try  {
            $themes = $this->getContainer()->get('cms.theme')->createSymlinks(true);
        } catch (\InvalidArgumentException $e) {
            $output->writeln('   "SiteBundle" does not exist.');

            return;
        }

        $style = new TableStyle();
        $style
            ->setVerticalBorderChar(' ')
            ->setCrossingChar(' ')
        ;

        $table = new Table($output);
        $table
            ->setHeaders(['Theme', 'Target', 'Method'])
            ->setStyle($style)
        ;

        foreach ($themes as $data) {
            $table->addRow([
                $data['theme'],
                $data['target'],
                $data['method'],
            ]);
        }

        $table->render();
    }
}
