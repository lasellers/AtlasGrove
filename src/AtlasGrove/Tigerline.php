<?php
namespace AtlasGrove;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Debug\Exception\ContextErrorException;

use org\majkel\dbase\Table AS DBase;

use Monolog\Monolog;

class Tigerline
{
    protected $version="2.0.8";
    
    protected $container;
    protected $io;
    protected $logger;
    
    //
    protected $yearfp;
    protected $statefp="";
    protected $yearFolder;
    // protected $stateFolder="";
    
    //
    protected $roi;
    protected $clip;
    
    protected $width=640;
    protected $height=480;
    
    public $tigerline_subtypes=[
    ['prefix'=>'arealm','type'=>'A','nameField'=>'FULLNAME'],
    ['prefix'=>'pointlm','type'=>'M','nameField'=>'FULLNAME'],
    ['prefix'=>'areawater','type'=>'W','nameField'=>'FULLNAME'],
    ['prefix'=>'edges','type'=>'E','nameField'=>'FULLNAME']
    //['prefix'=>'faces','type'=>'R','nameField'=>'FULLNAME']
    //['prefix'=>'featnanes','type'=>'F','nameField'=>'FULLNAME']
    ];
    
    /**
    */
    public function __construct($container,SymfonyStyle $io=null)
    {
        $this->container=$container;
        $this->io=$io;
        
        $this->logger = $container->get('logger');
        $this->logger->info('Tigerline');
        
        $this->computeMostRecentCachedTigerlineYear();
    }
    
    public function computeMostRecentCachedTigerlineYear()
    {
        $finder = new Finder();
        $finder->directories()->depth("== 0")->path("/^TIGER[\d]{4}/")->in($this->getRootDataPath())->sort(function ($a, $b)
        {
            return strcmp($b->getRealpath(), $a->getRealpath());
        }
        );
        
        $iterator = $finder->getIterator();
        $iterator->rewind();
        $dir = $iterator->current();
        
        if($iterator->count()>0) {
            $this->yearFolder = $dir->getRelativePathname();
            $this->yearfp=preg_replace("/[^\d+]*/","",$this->yearFolder);
        }
        /*
        $matches = array_filter($this->years, function ($haystack) {
        return preg_match("/(.+)".$this->year."/",$haystack);
        });
        $yearFolder=each($matches)['value'];
        */
    }
    
    public function checkPath(string $dir): string
    {
        if(!file_exists($dir))
        {
            mkdir($dir);
        }
        return $dir;
    }
    
    public function getRootPath(): string
    {
        return $this->container->get('kernel')->getRootDir();
    }
    public function getCachePath(): string
    {
        if($this->container->getParameter('cache_stored_outside_project'))
        $dir=dirname(dirname($this->container->get('kernel')->getRootDir()))."/cache";
        else
            $dir=dirname($this->container->get('kernel')->getCacheDir());
        return $this->checkPath($dir);
    }
    public function getRootDataPath(): string
    {
        $dir=$this->getCachePath()."/data";
        return $this->checkPath($dir);
    }
    public function getDataPath(): string
    {
        return $this->getCachePath()."/data/{$this->yearFolder}";
    }
    public function getDataCachePath(): string
    {
        $dir=$this->getCachePath()."/data.cache";
        return $this->checkPath($dir);
    }
    public function getWebPath(): string
    {
        $dir=$this->container->getParameter('web_dir');
        return $this->checkPath($dir);
    }
    public function getMapPath(): string
    {
        $dir=$this->container->getParameter('map_dir');
        return $this->checkPath($dir);
    }
    
      public function cacheIdToFilename(int $id): string
    {
        return $this->getDataCachePath()."/{$id}.txt";
    }
    
    protected function arrayToNameValue($array)
    {
        $rows=[];
        foreach($array as $name=>$value) {
            $rows[]=[strlen($name)<=1?$name:UCWords(strtolower($name)),$value];
        }
        return $rows;
    }
    
    public function getYear(): string
    {
        return $this->yearfp;
    }
    public function getYearFolder(): string
    {
        return $this->yearFolder;
    }
    public function getState(): string
    {
        return $this->statefp;
    }
    
    public function getCacheTTL(): int
    {
        return intval($this->container->getParameter('cache_ttl'));//10 m ttl
    }
    
    public function setClip(array $clip=[])
    {
        $this->clip=$clip;
    }
    
    public function setYear(string $yearfp)
    {
        $this->yearfp=$yearfp;
    }
    
    public function setState(string $statefp)
    {
        $this->statefp=$statefp;
    }
    
    public function setWidth(int $width=0)
    {
        $this->width=$width>64?$width:64;
    }
    
    public function setHeight(int $height=0)
    {
        $this->height=$height>64?$height:64;
    }
    
    protected function removeUnsed(array $array)
    {
        return $array; //todo
    }
    
    protected function printArray(array $array, string $name="")
    {
        $this->io->table(
        array_merge([$name],array_keys($array)),
        [
        array_merge([''],array_values($array))
        ]
        );
    }
    protected function printROI()
    {
        $this->printArray($this->roi,"ROI");
    }
    protected function printROIs()
    {
        $this->printArray($this->rois,"ROIs");
    }
    protected function printClip()
    {
        $this->printArray($this->clip,"Clip");
    }
    
    protected function arrayToFloat(array &$array)
    {
        foreach($array as $key=>$value)
        {
            $array[$key]=(float)$value;
        }
    }
    
    protected function arrayIsFloat(array &$array)
    {
        foreach($array as $key=>$value)
        {
            if(!is_numeric($value)) {
                return false;
            }
        }
        return true;
    }

    protected function computeRegionMids(array $roi): array
    {
        $roi['Xmid']=(float)$roi['Xmin']+(((float)$roi['Xmax']-(float)$roi['Xmin'])/2);
        $roi['Ymid']=(float)$roi['Ymin']+(((float)$roi['Ymax']-(float)$roi['Ymin'])/2);
        return $roi;
    }
    
    protected function updateCacheClipBounds(float $x,float $y)
    {
        if($x<$this->clip['Xmin']) $this->clip['Xmin']=$x;
        if($y<$this->clip['Ymin']) $this->clip['Ymin']=$y;
        
        if($x>$this->clip['Xmax']) $this->clip['Xmax']=$x;
        if($y>$this->clip['Ymax']) $this->clip['Ymax']=$y;
    }
    
    protected function printMFHAResolution($mfha)
    {
        $w=$mfha['Xmax']-$mfha['Xmin'];
        $h=$mfha['Ymax']-$mfha['Ymin'];
        $this->io->note(" Resolution: $w , $h");
    }
    
}

/*
Shapes
L Line P polygon * point

Types
// A area landmark
// M landmark
// W water
// E edge

// C County
// P Place

// r road
// t railroad
// h hwy
// i interstate
// p parkway

INPUT:
tiger line 2007 shape files

state level, use only:
county and place

county:
EDGES, area landmark, point landmark, areawater

OUTPUT:
state/county shapes:

county.vec
state.vec

vec format:
version: 4 ascii (1)
type 2ascii (blank)
latitude 13ascii 4.8
longitude 13ascii 4.8
color 6ASCII FFFFFF
fillcolor 6ASCII FFFFFF (or blank)
name-length (3 ascii 0-999)
name data (ascii)
keywords-length: (3 ascii 0-999)
keywords:(ascii)
bounding-box x,y,x2,y2  13ascii 4.8
vector-length (4 ascii 0-9999) number of points
vector data. points x,y  13ascii 4.8
*/