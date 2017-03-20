<?php
namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use AtlasGrove\Utils as Utils;
use AtlasGrove\Tigerline as Tigerline;

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
        
        $util=new Utils($this->getContainer(),$io);
        $tigerline=new Tigerline($this->getContainer(),$io);
        
        $io->table(
        ['Name','Value'],
        [
        ['Root Path',$tigerline->getRootPath()],
        ['Root Data Path',$tigerline->getRootDataPath()],
        ['Data Path',$tigerline->getDataPath()],
        ['Output Path',$tigerline->getOutputPath()],
        ['Web Path',$tigerline->getWebPath()],
        ['Map Path',$tigerline->getMapPath()],
        ['State',$tigerline->getState()],
        ['Year',$tigerline->getYear()],
        ['Font',$tigerline->getFont()],
        ['Cache TTL',$tigerline->getCacheTTL()]
        ]
        );
        
    }
}