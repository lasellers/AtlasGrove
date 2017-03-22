<?php
namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use AtlasGrove\TigerlineCache as TigerlineCache;
use AtlasGrove\TigerlineRender as TigerlineRender;

// php bin/console atlasgrove:status

class StatusCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('atlasgrove:status');
        $this->setDescription('shows AtlasGrove status');
        
        //$this->addOption('path', 'p', InputOption::VALUE_REQUIRED, '', getcwd());
        $this->addOption('path', null, InputOption::VALUE_NONE, 'Optional output path');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        
        $tigerlineCache=new TigerlineCache($this->getContainer(),$io);
        $tigerlineRender=new TigerlineRender($this->getContainer(),$io);
        
        $io->table(
        ['Name','Value'],
        [
        ['Root Path',$tigerlineCache->getRootPath()],
        ['Root Data Path',$tigerlineCache->getRootDataPath()],
        ['Data Path',$tigerlineCache->getDataPath()],
        ['Output Path',$tigerlineCache->getOutputPath()],
        ['Web Path',$tigerlineCache->getWebPath()],
        ['Map Path',$tigerlineCache->getMapPath()],
        ['State',$tigerlineCache->getState()],
        ['Year',$tigerlineCache->getYear()],
        ['Cache TTL',$tigerlineCache->getCacheTTL()],
        ['Font',$tigerlineRender->getFont()]
        ]
        );
        
    }
}