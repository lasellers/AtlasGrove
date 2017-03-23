<?php
namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use AtlasGrove\Tigerline as Tigerline;

use AtlasGrove\TigerlineCache as TigerlineCache;
use AtlasGrove\TigerlineRender as TigerlineRender;
// php bin/console atlasgrove:render

class RenderCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('atlasgrove:render');
        $this->setDescription('draws map');
        
        $this->addArgument('id', InputArgument::OPTIONAL,'id or x1,y1,x2,y2'); //ie 47, 47007, etc
        
        $this->addOption('force', null, InputOption::VALUE_NONE,'If set, uses force');
        
        $this->addOption('width',null, InputOption::VALUE_REQUIRED, 'If set, uses width');
        $this->addOption('height',null, InputOption::VALUE_REQUIRED, 'If set, uses height');
        
        $this->addOption('vga',null, InputOption::VALUE_NONE, 'If set, uses vga');
        $this->addOption('1080p',null, InputOption::VALUE_NONE, 'If set, uses 1080p');
        $this->addOption('4k',null, InputOption::VALUE_NONE, 'If set, uses 4k');
        $this->addOption('8k',null, InputOption::VALUE_NONE, 'If set, uses 8k');
        $this->addOption('16k',null, InputOption::VALUE_NONE, 'If set, uses 16k');
        
        $this->addOption('steps',null, InputOption::VALUE_NONE, 'If set, ...');
        $this->addOption('roads',null, InputOption::VALUE_NONE, 'If set, ...');
        
        $this->addOption('aspect',null, InputOption::VALUE_REQUIRED, 'If set, uses None,Width,Height');
        $this->addOption('lod',null, InputOption::VALUE_REQUIRED, 'If set, ...');
        $this->addOption('region',null, InputOption::VALUE_REQUIRED, 'If set, ...');
        $this->addOption('roi',null,InputOption::VALUE_NONE,'If set, ...');
        $this->addOption('test',null,InputOption::VALUE_REQUIRED,'If set, ...');
        
        $this->addOption('all',null, InputOption::VALUE_NONE, 'If set, ...');
        $this->addOption('states',null,InputOption::VALUE_NONE,'If set, uses states');
        $this->addOption('counties',null,InputOption::VALUE_NONE,'If set, uses counties');
        
        $this->addOption('png',null,InputOption::VALUE_NONE,'If set, outputs PNG files');
        $this->addOption('svg',null,InputOption::VALUE_NONE,'If set, outputs SVG files');
        
        $this->addOption('path', 'p', InputOption::VALUE_REQUIRED, '', getcwd());
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        
        $cache=new TigerlineCache($this->getContainer(),$io);
        $render=new TigerlineRender($this->getContainer(),$io);
        
        $id = $input->getArgument('id')?:"";
        
        $force = $input->getOption('force',false)?true:false;
        $io->note('Force '.($force?"YES":"NO"));
        if($force) {
            $render->setForce($force);
        }
        
        $width = $input->getOption('width');
        if($width>0) {
            $render->setWidth($width);
        }
        
        $height = $input->getOption('height');
        if($height>0) {
            $render->setHeight($height);
        }
        
        if($input->getOption('vga')) {
            $render->setHeight(640);
            $render->setWidth(480);
        }
        else if($input->getOption('1080p')) {
            $render->setHeight(1080);
            $render->setWidth(1920);
        }
        else if($input->getOption('4k')) {
            $render->setHeight(2160);
            $render->setWidth(3840);
        }
        else if($input->getOption('8k')) {
            $render->setHeight(4320);
            $render->setWidth(7680);
        }
        else if($input->getOption('16k')) {
            $render->setHeight(4320*2);
            $render->setWidth(7680*2);
        }
        
        $aspect = $input->getOption('aspect');
        if(strlen($aspect)>0) {
            $render->setAspectType($aspect);
        }
        
        $lod = $input->getOption('lod');
        if(strlen($lod)>0) {
            $render->setLODType($lod);
        }
        
        $region = $input->getOption('region');
        if(strlen($region)>0) {
            $render->setRegionType($region);
        }
        
        if($input->getOption('steps')) {
            $render->setSteps(true);
        }
        if($input->getOption('roads')) {
            $render->setDataLayers(['roads']);
        }
        //
        if(strlen($input->getOption('test'))>0) {
            
            switch($input->getOption('test')) {
                case "all":
                    $records=$render->renderShapeROI([
                    'Xmin'=> -130,
                    'Ymin'=> 20,
                    'Xmax'=>-70,
                    'Ymax'=> 50
                    ]);
                    break;
                
                case "us":
                    $records=$render->renderShapeROI([
                    'Xmin'=> -130,
                    'Ymin'=> 20,
                    'Xmax'=>-70,
                    'Ymax'=> 50
                    ]);
                    break;
                
                case "etn":
                    $records=$render->renderShapeROI([
                    'Xmin'=> -85-.5+.2+.1,
                    'Ymin'=> 34.982924+.4,
                    'Xmax'=>-81-.5-.2,
                    'Ymax'=> 36.678118+.4
                    ]);
                    break;
                
                case "tn":
                    $records=$render->renderShapeROI([
                    'Xmin'=> -90.31029799999999,
                    'Ymin'=> -81.6469,
                    'Xmax'=>-81.6469,
                    'Ymax'=> 36.678118
                    ]);
                    break;
                
                case "lod0":
                    $records=$render->renderShapeROI([
                    'Xmin'=> -85-.5+.2+.1,
                    'Ymin'=> 34.982924,
                    'Xmax'=>-81-.5-.2,
                    'Ymax'=> 36.678118+.3
                    ]);
                    break;
                
                case "narrow":
                    $records=$render->renderShapeROI([
                    'Xmin'=> -85-.5+.2+.1,
                    'Ymin'=> 34.982924+.4,
                    'Xmax'=>-81-.5-.2,
                    'Ymax'=> 36.678118+.4
                    ]);
                    break;
                
                case "blank":
                    $records=$render->renderShapeROI([
                    'Xmin'=> -100,
                    'Ymin'=> 60,
                    'Xmax'=>-100,
                    'Ymax'=> 60
                    ]);
                    break;
                
                default:
                    
            }
            
        } else if($input->getOption('states',false)||$input->getOption('all',false)) {
            $io->title('Render States Shapes');
            $files=$cache->cacheStatesList();
            $render->renderShapes($files);
        }
        
        else if($input->getOption('counties',false)||$input->getOption('all',false)) {
            $io->title('Render Counties Shapes');
            $files=$cache->cacheCountiesList();
            $render->renderShapes($files);
        }
        
        else if(strlen($id)>0) {
            echo $id."\n";
            if(is_numeric($id)) {
                echo $id."\n";
                $roi = $input->getOption('roi');
                echo $roi."\n";
                if($roi) {
                    $io->title("Cache Id {$id} ROI");
                    $render->renderShapeFromROI($id);
                } else {
                    $io->title("Cache Id {$id}");
                    $render->renderShape($id);
                }
                
            }
            else {
                
                $a=explode(",",$id);
                if(count($a)==4) {
                    $records=$render->renderShapeROI([
                    'Xmin'=> $a[0],
                    'Ymin'=> $a[1],
                    'Xmax'=> $a[2],
                    'Ymax'=> $a[3]
                    ]);
                }
            }
            
        }
        else {
            $io->title('Render all');
            
            $files=$cache->cacheStatesList();
            $render->renderShapes($files);
            
            $files=$cache->cacheCountiesList();
            $render->renderShapes($files);
        }
        
    }
}