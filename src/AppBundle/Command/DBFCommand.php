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

// php bin/console atlasgrove:dbf

class DBFCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('atlasgrove:dbf');
        $this->setDescription('lists dbf from cache that are drawable by id');
        
        $this->addArgument('filter', InputArgument::OPTIONAL,"path filter");
        
        $this->addOption(
        'save',
        null,
        InputOption::VALUE_NONE,
        'If set, saves masterlist to file'
        );
        
        //$this->addOption('path', 'p', InputOption::VALUE_REQUIRED, '', getcwd());
        $this->addOption('path', null, InputOption::VALUE_NONE, 'Optional output path');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        
        $util=new Utils($this->getContainer(),$io);
        $tigerline=new Tigerline($this->getContainer(),$io);
        
        $filter = $input->getArgument('filter')?:"";
        
        $save = $input->getOption('save',false);
        $path = $input->getOption('path',false);
        
        if(strlen($path))
        extract($util->getDBFList($filter,true,$path));
        else
            extract($util->getDBFList($filter,true));
        
        $output->writeln("DBFs written to $file");
    }
}