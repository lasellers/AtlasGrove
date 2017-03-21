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
    protected $version="2.0.6";
    
    protected $container;
    protected $io;
    protected $logger;
    
    //
    private $cacheTTL=0;
    
    protected $rootDataDir="";
    protected $dataDir="";
    protected $rootDir="";
    protected $outputDir="";
    protected $webDir="";
    protected $mapDir="";
    
    protected $yearfp;
    protected $statefp="";
    
    //
    protected $roi;
    protected $clip;
    
    protected $width=640;
    protected $height=480;
    
    //
   // protected $cull;
    
    /**
    */
    public function __construct($container,SymfonyStyle $io)
    {
        //        parent::__construct($container,SymfonyStyle $io);
        
        $this->container=$container;
        $this->io=$io;
        
        $this->logger = $container->get('logger');
        $this->logger->info('Tigerline');
        
        //
        $this->rootDataDir=dirname($container->get('kernel')->getCacheDir())."/data";
        $this->rootDir=($container->get('kernel')->getRootDir());
        
        $this->outputDir=dirname($container->get('kernel')->getCacheDir())."/output";
        //        $this->outputDir=$container->getParameter('output_dir');
        if(!file_exists($this->outputDir))
        {
            mkdir($this->outputDir);
        }
        
        $this->webDir=$container->getParameter('web_dir');
        if(!file_exists($this->webDir))
        {
            mkdir($this->webDir);
        }
        
        $this->mapDir=$container->getParameter('map_dir');
        if(!file_exists($this->mapDir))
        {
            mkdir($this->mapDir);
        }
        
        $this->yearfp=$this->getMostRecentCachedTigerlineYear();
        
        $this->dataDir=dirname($container->get('kernel')->getCacheDir())."/data/tiger{$this->yearfp}";
        
        $this->cacheTTL=intval($container->getParameter('cache_ttl'));//10 m ttl
    }
    
    
    protected function arrayToNameValue($array)
    {
        $rows=[];
        foreach($array as $name=>$value) {
            $rows[]=[UCWords(strtolower($name)),$value];
        }
        return $rows;
    }
    

    
    public function getRootDataPath(): string
    {
        return $this->rootDataDir;
    }
    public function getDataPath(): string
    {
        return $this->dataDir;
    }
    public function getRootPath(): string
    {
        return $this->rootDir;
    }
    public function getOutputPath(): string
    {
        return $this->outputDir;
    }

    public function cacheIdToFilename(int $id): string
    {
      return $this->getOutputPath()."/{$id}.txt";
    }

    public function getWebPath(): string
    {
        return $this->webDir;
    }
    public function getMapPath(): string
    {
        return $this->mapDir;
    }
    public function getYear(): string
    {
        return $this->yearfp;
    }
    public function getState(): string
    {
        return $this->statefp;
    }
    public function getCacheTTL(): int
    {
        return $this->cacheTTL;
    }
    
    public function getMostRecentCachedTigerlineYear(): string
    {
        $finder = new Finder();
        
        $finder->directories()->depth("== 0")->path("/^tiger[\d]{4}/")->in($this->rootDataDir)->sort(function ($a, $b)
        {
            return strcmp($b->getRealpath(), $a->getRealpath());
        }
        );
        
        $iterator = $finder->getIterator();
        $iterator->rewind();
        $dir = $iterator->current();
        
        $tigeryear_folder=$dir->getRelativePathname();
        
        $this->yearfp=preg_replace("/[^\d+]*/","",$tigeryear_folder);
        
        return $this->yearfp;
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
    protected function printArray(array $array, string $name)
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
    
    
    
    
    // *****************************************************************************
    
    protected function updateCacheClipBounds(float $x,float $y)
    {
        if($x<$this->clip['Xmin']) $this->clip['Xmin']=$x;
        if($y<$this->clip['Ymin']) $this->clip['Ymin']=$y;
        
        if($x>$this->clip['Xmax']) $this->clip['Xmax']=$x;
        if($y>$this->clip['Ymax']) $this->clip['Ymax']=$y;
    }
    
    
    
    
    
    
    
    // *****************************************************************************



    /*
    
    protected function in_minres(array $mfha): bool
    {
        if(!isset($this->cull)) return false;
        
        // $this->printMFHAResolution($mfha);
        
        if($this->minimumResolution($mfha))
        {
            $this->cacheStats['files_minres_culled']++;
            return true;
        }
        return false;
    }
    */
    // *****************************************************************************
    
    
    
    protected function printMFHAResolution($mfha)
    {
        $w=$mfha['Xmax']-$mfha['Xmin'];
        $h=$mfha['Ymax']-$mfha['Ymin'];
        $this->io->note(" Resolution: $w , $h");
    }
    
    
    
    
    



// *****************************************************************************
public function getShx($filename): array
{
    try {
        $mfha=null;
        $shx=[];
        
        $this->cacheStats['shx']++;
        $size=filesize($filename);
        // $this->logger->debug("SHX::: $filename ($size bytes)");
        
        $handle = @fopen($filename, "r");
        if($handle !== FALSE) {
            $binarydata = @fread($handle, 100);
            
            //main field header
            $mfha = unpack(
            "NFileCode/NUnused4/NUnused8/NUnused12/NUnused16/NUnused20/NFileLength/IVersion/IShapeType/dXmin/dYmin/dXmax/dYmax/dZmin/dZmax/dMmin/dMmax",
            $binarydata);
            //  print_shapefile_contents_mainheader($mfha);
            // printMFHAResolution($mfha);
            
            $count=0;
            $pos=ftell($handle);
            while(!feof($handle) && ($pos+8)<$size)
            {
                $binarydata = fread($handle, 8);
                $pos=ftell($handle);
                $rh = unpack("NOffset/NContentLength",$binarydata);
                $this->cacheStats['shxrecords']++;
                
                $shx[]=array($rh['Offset'],$rh['ContentLength']);
                
                //   $this->logger->debug("+++ [".ftell($handle)."] Offset: ".$rh['Offset']."w ContentLength: ".$rh['ContentLength']."w");
                
                //
                fseek($handle,$pos + $rh['ContentLength']*2,SEEK_SET);
                $pos=ftell($handle);
                
                $count++;
            }
            
            $this->logger->debug("SHX>>> $count index count");
            
            fclose($handle);
        }
        
        return array('header'=>$mfha,'index'=>$shx);
    }
    catch (\Symfony\Component\Debug\Exception\ContextErrorException $e)
    {
        return ['header'=>[],'index'=>[]];
    }
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