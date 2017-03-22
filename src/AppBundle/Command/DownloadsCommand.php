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

// php bin/console atlasgrove:downloads

class DownloadsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('atlasgrove:downloads');
        $this->setDescription('lists AtlasGrove downloaded tigerline files');
        
        $this->addArgument('year', InputArgument::OPTIONAL,"year");
        $this->addArgument('state', InputArgument::OPTIONAL,"state");
        
        $this->addOption('states', null, InputOption::VALUE_NONE, 'If set, shows just states');
        
        $this->addOption(
        'years',
        null,
        InputOption::VALUE_NONE,
        'If set, shows just years'
        );
        $this->addOption(
        'counties',
        null,
        InputOption::VALUE_NONE,
        'If set, shows just counties'
        );
        $this->addOption(
        'files',
        null,
        InputOption::VALUE_NONE,
        'If set, shows just files'
        );
        
        //$this->addOption('path', 'p', InputOption::VALUE_REQUIRED, '', getcwd());
        $this->addOption('path', null, InputOption::VALUE_NONE, 'Optional output path');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        
        $tigerlineDownloads=new TigerlineDownloads($this->getContainer(),$io);
        $tigerline=new Tigerline($this->getContainer(),$io);
        
        $filter="";
        
        $year = $input->getArgument('year')?:"";
        $state = $input->getArgument('state')?:"";
        if(strlen($year)>0 && strlen($state)>0) {
            $tigerline->download($year,$state);
        }
        else if($input->getOption('years',false))
        {
            extract($tigerlineDownloads->getCacheYears($filter,true));
            $output->writeln("Wrote {$file}");
        }
        else if($input->getOption('states',false))
        {
            extract($tigerlineDownloads->getCacheStates($filter,true));
            $output->writeln("Wrote {$file}");
        }
        else if($input->getOption('counties',false))
        {
            extract($tigerlineDownloads->getCacheCounties($filter,true));
            $output->writeln("Wrote {$file}");
        }
        else if($input->getOption('files',false))
        {
            extract($tigerlineDownloads->getCacheFiles($filter,true));
            $output->writeln("Wrote {$file}");
        }
        else
        {
            extract($tigerlineDownloads->getCacheYears($filter,true));
            $output->writeln("Wrote {$file}");
            
            extract($tigerlineDownloads->getCacheStates($filter,true));
            $output->writeln("Wrote {$file}");
            
            extract($tigerlineDownloads->getCacheCounties($filter,true));
            $output->writeln("Wrote {$file}");
            
            extract($tigerlineDownloads->getCacheFiles($filter,true));
            $output->writeln("Wrote {$file}");
        }
        
    }
}