<?php
namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use AtlasGrove\TigerlineDownload as TigerlineDownload;

// php bin/console atlasgrove:download

class DownloadCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('atlasgrove:download');
        $this->setDescription('downloads Tiger/Line shapefiles to cache (parms: year, state)');
        
        $this->addArgument('state', InputArgument::OPTIONAL);
        $this->addArgument('year', InputArgument::OPTIONAL);
        
       // $this->addOption('path', 'p', InputOption::OPTIONAL, '', getcwd());
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        
        $download=new TigerlineDownload($this->getContainer(),$io);
        
        $state = $input->getArgument('state')?:"47";
        $year = $input->getArgument('year')?:"2007";
        
        $download->download($year,$state);
    }
}