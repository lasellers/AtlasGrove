<?php
namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use AtlasGrove\TigerlineDownloads as TigerlineDownloads;
use AtlasGrove\TigerlineDownload as TigerlineDownload;

// php bin/console atlasgrove:download

class DownloadCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('atlasgrove:download');
        $this->setDescription('downloads Tiger/Line shapefiles to cache (parms: year, state)');
        
        $this->addArgument('year', InputArgument::REQUIRED);
        $this->addArgument('state', InputArgument::REQUIRED);
        
        $this->addOption('path', 'p', InputOption::VALUE_REQUIRED, '', getcwd());
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        
        $util=new Utils($this->getContainer(),$io);
        $tigerline=new TigerlineDownload($this->getContainer(),$io);
        
        $year = $input->getArgument('year')?:"";
        $state = $input->getArgument('state')?:"";
        
        $tigerline->download($year,$state);
    }
}