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
use AtlasGrove\TigerlineCache as TigerlineCache;

// php bin/console atlasgrove:cache

class CacheCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('atlasgrove:cache');
        $this->setDescription('lists AtlasGrove cached tigerline files');
        
        $this->addArgument('id', InputArgument::OPTIONAL,"id filter");
        $this->addArgument('filter', InputArgument::OPTIONAL,"path filter");
        
        $this->addOption('force', null, InputOption::VALUE_NONE,'If set, uses force');
        
        $this->addOption('states-list', null, InputOption::VALUE_NONE,'If set, uses ..');
        $this->addOption('counties-list', null, InputOption::VALUE_NONE,'If set, uses ..');
        
        $this->addOption('states', null, InputOption::VALUE_NONE,'If set, uses ..');
        $this->addOption('counties', null, InputOption::VALUE_NONE,'If set, uses ..');
        
        $this->addOption('all', null, InputOption::VALUE_NONE,'If set, uses ..');
        
        //$this->addOption('path', 'p', InputOption::VALUE_REQUIRED, '', getcwd());
        $this->addOption('path', null, InputOption::VALUE_NONE, 'Optional output path');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        
       // $util=new Utils($this->getContainer(),$io);
        $tigerline=new TigerlineCache($this->getContainer(),$io);
        
        $id = $input->getArgument('id')?:"";
        $filter = $input->getArgument('filter')?:"";
        
        $force = $input->getOption('force',false)?true:false;
        $io->note('Force '.($force?"YES":"NO"));
        
        if($input->getOption('states-list',false)||$input->getOption('all',false)||!($id>0))
        {
            $io->title('Cache States List');
            $files=$tigerline->cacheStatesList();
            
            foreach($files as &$file)
            {
                $file=explode("\t",$file);
            }
            $io->table(['id','folder','name'],$files);
        }
        
        if($input->getOption('counties-list',false)||$input->getOption('all',false)||!($id>0))
        {
            $io->title('Cache Counties List');
            $files=$tigerline->cacheCountiesList();
            
            foreach($files as &$file)
            {
                $file=explode("\t",$file);
            }
            $io->table(['id','county','full'],$files);
        }
        
        if($input->getOption('states',false)||$input->getOption('all',false)||!($id>0))
        {
            $io->title('Cache States Shapes');
            $files=$tigerline->cacheStatesList();
            
            $records=$tigerline->cacheShapes($files,$force);
        }
        
        if($input->getOption('counties',false)||$input->getOption('all',false)||!($id>0))
        {
            $io->title('Cache Counties Shapes');
            $files=$tigerline->cacheCountiesList();
            
            $records=$tigerline->cacheShapes($files,$force);
        }
        
        if($id>0) {
            $io->title("Cache Id {$id}");
            
            $records=$tigerline->cacheShape($id,$force);
            //  $io->table(['id','county','full'],$records);
        }
        
    }
}