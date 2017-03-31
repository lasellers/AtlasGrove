<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use AtlasGrove\TigerlineDownloads as TigerlineDownloads;
use AtlasGrove\Tigerline as Tigerline;

// php bin/console atlasgrove:shp

class SHPCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('atlasgrove:shp');
        $this->setDescription('lists shp from cache that are drawable by id');

        $this->addArgument('filter', InputArgument::OPTIONAL, "path filter");

        $this->addOption(
            'save',
            null,
            InputOption::VALUE_NONE,
            'If set, saves masterlist to file'
        );

        $this->addOption('path', null, InputOption::VALUE_NONE, 'Optional output path');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $util = new TigerlineDownloads($this->getContainer(), $io);
        $tigerline = new Tigerline($this->getContainer(), $io);

        $filter = $input->getArgument('filter') ?: "";
        $save = $input->getOption('save', false);
        $path = $input->getOption('path', false);

        if (strlen($path))
            extract($util->getSHPList($filter, true, $path));
        else
            extract($util->getSHPList($filter, true));

        $output->writeln("SHPs written to $file");
    }
}