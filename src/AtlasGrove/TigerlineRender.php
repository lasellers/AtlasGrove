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

class TigerlineRender extends Tigerline
{
    private $zoom=0;
    
    private $compressed=false;
    private $logo=true;
    
    private $lastlat=0;
    private $lastlon=0;
    private $lastshape='';
    
    private $thickness;
    private $fonts;
    private $font;
    
    public function __construct($container,SymfonyStyle $io)
    {
        parent::__construct($container,$io);
        
        //
        $this->setThickness();
        $this->setCompressed(false);
        $this->setLogo(false);
        $this->setAspectType();
        $this->setLODType();
        $this->setRegionType();
        $this->resetstats();
        $this->font=$this->selectFont();
    }
    
    
    /*  public function setROI(array $roi=[])
    {
    $this->cull=$cull;
    }
    */
    public function setThickness(int $thickness=0)
    {
        $this->thickness=$thickness>1?$thickness:1;
    }
    
    
    public function getFont(): string
    {
        return $this->font;
    }
    
    public function getFonts(): array
    {
        return $this->fonts;
    }
    
    public function selectFont(): string
    {
        //
        $fontsPath=$this->rootDir."/Resources/fonts/\*.ttf";
        $this->fonts=glob($fontsPath);
        
        if(count($this->fonts) > 0)
        {
            $this->font = $this->fonts[rand(0, count($this->fonts)-1)];
        }
        
        //
        $this->font=$this->rootDir."/Resources/fonts/Tuffy.ttf";
        $this->logger->info("font={$this->font}");
        
        return $this->font;
    }
    
    private $aspectType; //None,Width,Height
    private $aspectTypes=["Width","Height","None"];
    public function setAspectType(string $type=null)
    {
        $type = UCWords(strtolower($type));
        $this->aspectType=in_array($type,$this->aspectTypes)?$type:$this->aspectTypes[0];
    }
    
    private $lodType;
    private $lodTypes=["Auto","0","1","2"];
    public function setLODType(string $type=null)
    {
        $type = UCWords(strtolower($type));
        $this->lodType=in_array($type,$this->lodTypes)?$type:$this->lodTypes[0];
    }
    
    private $regionType;
    private $regionTypes=["Clip","Roi"];
    public function setRegionType(string $type=null)
    {
        $type = UCWords(strtolower($type));
        $this->regionType=in_array($type,$this->regionTypes)?$type:$this->regionTypes[0];
    }
    
    public function setCompressed(bool $state=false)
    {
        $this->compressed=$state==true;
    }
    
    public function setLogo(bool $state=false)
    {
        $this->logo=$state==true;
    }
    
    private $stats;
    public function resetstats()
    {
        $this->stats=[
        'file'=>0,
        'cache'=>0,
        'shape'=>0,
        'text'=>0,
        'rejected text'=>0,
        'point'=>0,
        'line'=>0,
        'polyline'=>0,
        'polygon'=>0,
        'box'=>0,
        'points culled'=>0,
        'roi bounding box culled'=>0,
        'roi bounding boxes culled'=>0,
        'regions'=>0,
        'region ids'=>''
        ];
    }
    private function printRenderStatistics()
    {
        $this->io->section("Render Statistics");
        $this->io->table(
        ['Name','Value'],
        $this->arrayToNameValue($this->stats)
        );
    }
    
    
    //
    public function renderShape($id) {
        $cacheFilename=$this->cacheIdToFilename($id);
        $imageFilename=$this->getMapPath()."/{$id}.png";
        
        $this->io->section("Render shape {$id} to {$imageFilename}");
        
        $tigerlineCache = new TigerlineCache($this->container,$this->io);
        if($tigerlineCache)
        {
            $tigerlineCache->cacheShape($id);
            
            $this->stats['regions']=1;
            $this->stats['region ids']=$id;
            
            $this->renderImageFromSingleCache($cacheFilename,$imageFilename);
        }
        
        $this->printRenderStatistics();
    }
    
    
    public function renderShapes($files) {
        foreach ($files as $file) {
            {
                list($id)=explode("\t",$file);
                $this->renderShape($id);
            }
        }
    }
    
    
    // *****************************************************************************
    
    /**
    */
    private function getCachedShapeIDs(): array {
        $records=[];
        
        $finder = new Finder();
        $finder->files()->depth("== 0")->path("/^.+.txt/")->in($this->getOutputPath());
        foreach ($finder as $file) {
            $id=preg_replace("/[^0-9]/", "", $file->getRelativePathname());
            if($id>0) {
                $records[]=$id;
            }
        }
        return $records;
    }
    
    protected $cull;
    public function setCull(array $cull)
    {
        $this->cull=$cull;
    }
    
    /*
    protected function pointCulled(float $x, float $y): bool
    {
    if($x>=$this->cull['Xmin'] && $x<=$this->cull['Xmax'] && $y>=$this->cull['Ymin'] && $y<=$this->cull['Ymax']) {
    return false;
    }
    
    $this->stats['points culled']++;
    return true;
    }*/
    
    protected function inBounds(float $number, float $min, float $max): bool
    {
        return ($min<=$number && $number<=$max);
    }
    
    protected function boundingBoxOverlap(array $bound): bool
    {
        if(
        (
        ($this->inBounds($bound['Xmin'],$this->cull['Xmin'],$this->cull['Xmax'])) ||
        ($this->inBounds($bound['Xmax'],$this->cull['Xmin'],$this->cull['Xmax']))
        ) && (
        ($this->inBounds($bound['Ymin'],$this->cull['Ymin'],$this->cull['Ymax'])) ||
        ($this->inBounds($bound['Ymax'],$this->cull['Ymin'],$this->cull['Ymax']))
        )
        ) {
            $this->stats['roi bounding box culled']++;
            return true;
        }
        return false;
    }
    
    protected function boundingBoxesCulled(array $bounds): bool
    {
        foreach($bounds as $bound)
        {
            if(!$this->boundingBoxOverlap($bound)) {
                return false;
            }
        }
        
        $this->stats['roi bounding boxes culled']++;
        return true;
    }
    
    
    public function renderROICullIds(array $ids): array {
        $idsInBounds=[];
        
        foreach($ids as $id)
        {
            $cacheFilename=$this->cacheIdToFilename($id);
            
            $in = fopen($cacheFilename, "rb");
            if($in)
            {
                try {
                    $version = trim(fgets($in));
                    
                    $this->roi=json_decode(trim(fgets($in)),TRUE);
                    //                    $this->printROI();
                    $this->arrayToFloat($this->roi);
                    
                    $this->rois=json_decode(trim(fgets($in)),TRUE);
                    //                  $this->printROIs();
                    $this->arrayToFloat($this->rois);
                    
                    $this->clip=json_decode(trim(fgets($in)),TRUE);
                    //                $this->printClip();
                    $this->arrayToFloat($this->clip);
                    if(
                    $this->boundingBoxOverlap($this->roi)
                    // && !$this->boundingBoxesCulled($this->rois)
                    ) {
                        $idsInBounds[]=$id;
                    }
                }
                catch (\Symfony\Component\Debug\Exception\ContextErrorException $e)
                {
                    $this->logger->error($e->getMessage());
                }
                fclose($in);
            }
        }
        return $idsInBounds;
    }
    
    private function boundingBoxToFilename(array $bound)
    {
        return $bound['Xmin'].'.'.$bound['Ymin'].'.'.$bound['Xmax'].'.'.$bound['Ymax'];
    }
    
    public function renderROI(array $cull) {
        $this->cull=$cull;
        
        //
        $this->clip['width']=$this->width;
        $this->clip['height']=$this->height;
        
        //
        if($this->compressed)
        $im=imagecreate($this->clip['width'],$this->clip['height']);
        else
            $im=imagecreatetruecolor($this->clip['width'],$this->clip['height']);
        if($im !== FALSE)
        {
            $cacheIds=$this->getCachedShapeIDs();
            $cacheIds=$this->renderROICullIds($cacheIds);
            
            $this->stats['regions']=count($cacheIds);
            $this->stats['region ids']=implode(',',$cacheIds);
            
            $rows=array_map( function ($a) { return [$a]; },$cacheIds);
            $this->io->table(['Id'],$rows);
            
            $partialImageFilename=$this->boundingBoxToFilename($this->cull);
            $imageFilename=$this->getMapPath()."/{$partialImageFilename}.png";
            
            $tigerlineCache = new TigerlineCache($this->container,$this->io);
            if($tigerlineCache)
            {
                foreach($cacheIds as $id) {
                    $this->io->section("Render shape {$id} to {$imageFilename}");
                    
                    $tigerlineCache->cacheShape($id);
                    
                    $cacheFilename=$this->getOutputPath()."/{$id}.txt";
                    
                    $this->renderImageFromROICache($im,$cacheFilename,$imageFilename);
                }
            }
            
            //
            imagepng($im,$imageFilename,9);
            imagedestroy($im);
            $this->logger->debug(">>>> makeImage $imageFilename");
        }
        
        $this->printRenderStatistics();
    }
    
    
    
    
    private function renderImageFromROICache($im, $cacheFilename,$imageFilename)
    {
        //
        $this->logger->info("renderImageFromCache: $cacheFilename to $imageFilename");
        
        //
        $in = fopen($cacheFilename, "rb");
        if($in)
        {
            $this->stats['file']++;
            $this->stats['cache']++;
            
            try {
                $size=filesize($cacheFilename);
                if($size<=0) {
                    // $this->printROI();
                    throw \Symfony\Component\Debug\Exception\ContextErrorException("Cached shapes file blank.");
                }
                
                //line 1 of cache is version
                $version= trim(fgets($in));
                
                //line 2 of cache is roi
                $this->roi=json_decode(trim(fgets($in)),TRUE);
                $this->arrayToFloat($this->roi);
                // $this->printROI();
                if(!$this->arrayIsFloat($this->roi)) {
                    throw \Symfony\Component\Debug\Exception\ContextErrorException("ROI is not valid.");
                }
                
                //line 3 of cache is rois
                $this->rois=json_decode(trim(fgets($in)),TRUE);
                
                //line 4 of cache is clip extended region
                $this->clip=json_decode(trim(fgets($in)),TRUE);
                $this->arrayToFloat($this->clip);
                if(!$this->arrayIsFloat($this->clip)) {
                    $this->printClip();
                    throw \Symfony\Component\Debug\Exception\ContextErrorException("Clip is not valid.");
                }
                
                //
                $select=$this->roi;
                
                //
                $dw=(float)($this->clip['Xmax']-$this->clip['Xmin']);
                $dh=(float)($this->clip['Ymax']-$this->clip['Ymin']);
                $this->clip['dw']=$dw;
                $this->clip['dh']=$dh;
                $this->clip['aspect']=$dh/$dw;
                /*   if( ($dh/$dw) > 3 || ($dw/$dh) > 3) {
                $this->printClip();
                throw \Symfony\Component\Debug\Exception\ContextErrorException("Clip aspect is exaggerated.");
                }
                */
                
                $this->clip['width']=$this->width;
                $this->clip['height']=$this->height;
                
                //
                $zoomw=(float)((float)$this->width/$dw);
                $zoomh=(float)((float)$this->height/$dh);
                if($zoomw<$zoomh)
                $zoom=$zoomw;
                else
                    $zoom=$zoomh;
                $this->clip['zoom']=$zoom;
                
                $this->io->table(
                ['width','height','dw','dh','zoomw','zoomh','zoom','aspectType'],
                [[$this->width,$this->height,$dw,$dh,$zoomw,$zoomh,$zoom,$this->aspectType]]
                );
                
                //
                //  $this->printClip();
                
                $this->renderImageFromCacheInner($im,$in,$imageFilename);
                
                fclose($in);
            }
            catch (\Symfony\Component\Debug\Exception\ContextErrorException $e)
            {
                $this->logger->error($e->getMessage());
            }
        }
    }
    
    
    // *****************************************************************************
    
    
    
    private function renderImageFromSingleCache($cacheFilename,$imageFilename)
    {
        //
        $this->logger->info("renderImageFromSingleCache: $cacheFilename to $imageFilename");
        
        //
        $in = fopen($cacheFilename, "rb");
        if($in)
        {
            $this->stats['file']++;
            $this->stats['cache']++;
            
            try {
                $size=filesize($cacheFilename);
                if($size<=0) {
                    // $this->printROI();
                    throw \Symfony\Component\Debug\Exception\ContextErrorException("Cached shapes file blank.");
                }
                
                //line 1 of cache is version
                $version= trim(fgets($in));
                
                //line 2 of cache is roi
                $this->roi=json_decode(trim(fgets($in)),TRUE);
                $this->arrayToFloat($this->roi);
                $this->printROI();
                if(!$this->arrayIsFloat($this->roi)) {
                    throw \Symfony\Component\Debug\Exception\ContextErrorException("ROI is not valid.");
                }
                
                //line 3 of cache is rois
                $this->rois=json_decode(trim(fgets($in)),TRUE);
                
                //line 4 of cache is clip extended region
                $this->clip=json_decode(trim(fgets($in)),TRUE);
                $this->arrayToFloat($this->clip);
                if(!$this->arrayIsFloat($this->clip)) {
                    $this->printClip();
                    throw \Symfony\Component\Debug\Exception\ContextErrorException("Clip is not valid.");
                }
                
                //
                if(strcasecmp($this->regionType,'ROI')==0)
                {
                    $this->clip['Xmin']=$this->roi['Xmin'];
                    $this->clip['Ymin']=$this->roi['Ymin'];
                    $this->clip['Xmax']=$this->roi['Xmax'];
                    $this->clip['Ymax']=$this->roi['Ymax'];
                }
                
                //
                $select=$this->roi;
                
                //
                $dw=(float)($this->clip['Xmax']-$this->clip['Xmin']);
                $dh=(float)($this->clip['Ymax']-$this->clip['Ymin']);
                $this->clip['dw']=$dw;
                $this->clip['dh']=$dh;
                $this->clip['aspect']=$dh/$dw;
                /*   if( ($dh/$dw) > 3 || ($dw/$dh) > 3) {
                $this->printClip();
                throw \Symfony\Component\Debug\Exception\ContextErrorException("Clip aspect is exaggerated.");
                }
                */
                
                if(strcasecmp($this->aspectType,"Width")==0) {
                    $this->height=(int)abs($this->width*($dh/$dw));
                }
                else if(strcasecmp($this->aspectType,"Height")==0) {
                    $this->width=(int)abs($this->height*($dw/$dh));
                }
                $this->clip['width']=$this->width;
                $this->clip['height']=$this->height;
                
                //
                $zoomw=(float)((float)$this->width/$dw);
                $zoomh=(float)((float)$this->height/$dh);
                if($zoomw<$zoomh)
                $zoom=$zoomw;
                else
                    $zoom=$zoomh;
                $this->clip['zoom']=$zoom;
                
                $this->io->table(
                ['width','height','dw','dh','zoomw','zoomh','zoom','aspectType'],
                [[$this->width,$this->height,$dw,$dh,$zoomw,$zoomh,$zoom,$this->aspectType]]
                );
                
                //
                $this->printClip();
                
                //
                if($this->compressed)
                $im=imagecreate($this->clip['width'],$this->clip['height']);
                else
                    $im=imagecreatetruecolor($this->clip['width'],$this->clip['height']);
                if($im !== FALSE)
                {
                    $this->renderImageFromCacheInner($im,$in,$imageFilename);
                    
                    //
                    imagepng($im,$imageFilename,9);
                    imagedestroy($im);
                    $this->logger->debug(">>>> makeImage $imageFilename");
                }
                
                fclose($in);
            }
            catch (\Symfony\Component\Debug\Exception\ContextErrorException $e)
            {
                $this->logger->error($e->getMessage());
            }
        }
    }
    
    private function renderImageFromCacheInner($im,$in,$imageFilename)
    {
        //
        $backgroundcolor=0xffffff;
        $color=0x000000;
        
        imagefill($im, 0, 0, $backgroundcolor);
        imagesetthickness($im,1);
        // \imageantialias($im,true);
        
        //
        $this->arrayToFloat($this->clip);
        $this->arrayToFloat($this->roi);
        
        //
        $this->setThickness(10);
        
        $c=imagecolorallocate($im,255,0,0);
        $this->renderShapeBox($im,$this->clip['Xmin'],$this->clip['Ymin'],$this->clip['Xmax'],$this->clip['Ymax'],$c);
        
        $c=imagecolorallocate($im,255,255,0);
        $this->renderShapeBox($im,$this->roi['Xmin'],$this->roi['Ymin'],$this->roi['Xmax'],$this->roi['Ymax'],$c);
        
        $this->setThickness();
        
        //
        $text='';
        $lines=0;
        while (!feof($in))
        {
            //
            $oldtext=$text;
            $text = trim(fgets($in));
            $data = trim(fgets($in));
            //if(feof($in)) return;
            $lines++;
            if($text=='.') $text=$oldtext;
            
            // [shape][type][csv]
            $shape=$data[0];
            $type=$data[1];
            $csv=substr($data,2);
            
            $a=explode(',',$csv);
            $count=count($a);
            
            $select=$this->clipROI($a);
            
            imagesetthickness($im,$this->getThickness($shape,$type));
            
            $textcolor=$this->getTextColor($im,$type);
            $color=$this->getColor($im,$type);
            $fillcolor=$this->getFillColor($im,$type);
            
            //$c=imagecolorallocatealpha($im,0,0,255,0);
            //$this->renderShapeBox($im,$select['Xmin'],$select['Ymin'],$select['Xmax'],$select['Ymax'],$color,$this->roi,$zoom,$this->height);
            
            // draw shape
            $this->stats['shape']++;
            
            switch($shape)
            {
                //point 1
                case '*':
                    if(1==($count%2)) {
                        $count--;
                }
                if(!($count>=0)) {
                    throw \Symfony\Component\Debug\Exception\ContextErrorException("Point count {$count}.");
                }
                
                for($i=0;$i<$count;$i+=2)
                {
                    $this->renderShapePoint($im,$a[$i],$a[$i+1],$color);
                }
                break;
            
            //3 line
            case 'L':
                if(!($count>=2)) {
                throw \Symfony\Component\Debug\Exception\ContextErrorException("Polyline count {$count}.");
            }
            
            $this->stats['polyline']++;
            
            $lat1=$a[0]; $lon1=$a[0+1];
            for($i=0;$i<$count;$i+=2)
            {
                $lat2=$a[$i]; $lon2=$a[$i+1];
                $this->renderShapeThickline($im,$lat1,$lon1,$lat2,$lon2,$color,$this->thickness);
                $lat1=$lat2; $lon1=$lon2;
            }
            break;
        
        case 'P': // polygon
            
            if(!($count>=3)) {
            throw \Symfony\Component\Debug\Exception\ContextErrorException("Polygon count {$count}.");
        }
        
        $this->renderShapePolygon($im,$a,$color,$fillcolor);
        break;
    
    default:
        $this->logger->error("shape=$shape type=$type count=$count");
}

// draw text
if(strlen($text)>0) {
    $this->renderText($im,$text,$textcolor,$select);
}
}

//
if($this->logo)
{
    $color = imageColorAllocateAlpha($im, 48, 64, 32,90);
    imagettftext($im, 20, 0, 10, $this->clip['height']-12, $color, $this->font,
    "Rendered ".date('r',time())." - US Census Data Tiger/Line {$this->yearfp} - $this->width x $this->height ($lines)"
    );
}

}


//
private function clipROI(array $a): array
{
    $this->roi=[
    'Xmin'=>null,
    'Ymin'=>null,
    'Xmax'=>null,
    'Ymax'=>null,
    ];
    
    $count=count($a);
    if(($count%2)==1) { $count--; }
    for($i=0;$i<$count;$i+=2)
    {
        $lat=$a[$i];
        $lon=$a[$i+1];
        
        if($this->roi['Xmin']==null || $lat<$this->roi['Xmin']) $this->roi['Xmin']=$lat;
        if($this->roi['Ymin']==null || $lon<$this->roi['Ymin']) $this->roi['Ymin']=$lon;
        if($this->roi['Xmax']==null || $lat>$this->roi['Xmax']) $this->roi['Xmax']=$lat;
        if($this->roi['Ymax']==null || $lon>$this->roi['Ymax']) $this->roi['Ymax']=$lon;
    }
    
    return $this->computeRegionMids($this->roi);
}


//
private function renderText($im,string $text,$color,array $select)
{
    $text=trim($text);
    if($text=='') return;
    
    $lat=(($select['Xmax']-$select['Xmin'])/2)+$select['Xmin'];
    $lon=(($select['Ymax']-$select['Ymin'])/2)+$select['Ymin'];
    
    $w1=($this->clip['Xmax']-$this->clip['Xmin']);
    $w2=($select['Xmax']-$select['Xmin']);
    $fontsize = (($w2/$w1)*$this->clip['width'])*.1; //rand(3,30);
    if($fontsize<2) {
        $this->stats['rejected text']++;
        return;
    }
    
    $this->stats['text']++;
    
    $rotation=0; //rand(0,360);
    
    $a=imagettfbbox($fontsize, $rotation, $this->font, $text);
    
    //$dy=abs($a[6]-$a[1]);
    //$this->logger->info("fs=".$this->fontsize);
    
    $x=(int)(($lat-$this->clip['Xmin'])*$this->clip['zoom']);
    $y=(int)($this->clip['height']-($lon-$this->clip['Ymin'])*$this->clip['zoom']);
    $x-=(($a[2]-$a[0])/2);
    
    //$this->logger->info("lat=$lat lon=$lon x=$x y=$y s=$this->fontsize r=$rotation ".$a[0]."-".$a[1]." text=$text");
    // $this->io->note("lat=$lat lon=$lon x=$x y=$y s=$fontsize r=$rotation ".$a[0]."-".$a[1]." text=$text");
    
    $r=($color>>16)&255;
    $g=($color>>8)&255;
    $b=($color&255);
    $textcolor = imageColorAllocateAlpha($im, $r, $g, $b,30);
    
    imagettftext($im, $fontsize, $rotation, $x, $y, $textcolor, $this->font, $text);
}

private function renderShapePoint($im,float $lat,float $lon,$color)
{
    $this->stats['point']++;
    
    $x=(int)(($lat-$this->clip['Xmin'])*$this->clip['zoom']);
    $y=(int)($this->clip['height']-($lon-$this->clip['Ymin'])*$this->clip['zoom']);
    
    imagesetpixel($im,$x,$y,$color);
}

private function renderShapeBox($im, float $lat1, float $lon1, float $lat2, float $lon2, $color)
{
    $this->stats['box']++;
    
    $this->renderShapeLine($im,$lat1,$lon1,$lat2,$lon1,$color);
    $this->renderShapeLine($im,$lat1,$lon2,$lat2,$lon2,$color);
    $this->renderShapeLine($im,$lat1,$lon1,$lat1,$lon2,$color);
    $this->renderShapeLine($im,$lat2,$lon1,$lat2,$lon2,$color);
}

private function renderShapeLine($im, float $lat1, float $lon1, float $lat2,float $lon2, $color)
{
    $this->stats['line']++;
    
    if($lat1<$this->clip['Xmin'] && $lat2<$this->clip['Xmin']) return;
    if($lat1>$this->clip['Xmax'] && $lat2>$this->clip['Xmax']) return;
    
    if($lon1<$this->clip['Ymin'] && $lon2<$this->clip['Ymin']) return;
    if($lon1>$this->clip['Ymax'] && $lon2>$this->clip['Ymax']) return;
    
    $x1=(int)(($lat1-$this->clip['Xmin'])*$this->clip['zoom']);
    $y1=(int)(($lon1-$this->clip['Ymin'])*$this->clip['zoom']);
    $x2=(int)(($lat2-$this->clip['Xmin'])*$this->clip['zoom']);
    $y2=(int)(($lon2-$this->clip['Ymin'])*$this->clip['zoom']);
    imageline($im,$x1,$this->clip['height']-$y1,$x2,$this->clip['height']-$y2,$color);
}

private function renderShapeThickline($im,float $lat1,float $lon1,float $lat2,float $lon2,$color,$thickness)
{
    if($this->thickness<=1) {
        return $this->renderShapeLine($im,$lat1,$lon1,$lat2,$lon2,$color);
    }
    
    $t=$this->thickness/2.0;
    
    $xa=(int)(($lat1-$this->clip['Xmin'])*$this->clip['zoom']);
    $ya=(int)(($lon1-$this->clip['Ymin'])*$this->clip['zoom']);
    $xb=(int)(($lat2-$this->clip['Xmin'])*$this->clip['zoom']);
    $yb=(int)(($lon2-$this->clip['Ymin'])*$this->clip['zoom']);
    
    $a = (atan2(($yb - $ya), ($xb - $xa))); //radians
    
    $pi2=3.14159/2;
    
    $p[0]=                 $xa+(cos($a+$pi2)*$t);
    $p[1]=$this->clip['height']-($ya+(sin($a+$pi2)*$t));
    
    $p[2]=                 $xa+(cos($a-$pi2)*$t);
    $p[3]=$this->clip['height']-($ya+(sin($a-$pi2)*$t));
    
    $p[4]=                 $xb+(cos($a-$pi2)*$t);
    $p[5]=$this->clip['height']-($yb+(sin($a-$pi2)*$t));
    
    $p[6]=                 $xb+(cos($a+$pi2)*$t);
    $p[7]=$this->clip['height']-($yb+(sin($a+$pi2)*$t));
    
    imagefilledpolygon($im, $p, count($p)/2, $color);
}


private function renderShapePolygon($im, array $a, $color, $fillcolor)
{
    $this->stats['polygon']++;
    
    $count=count($a);
    for($i=0;$i<$count;$i+=2)
    {
        $a[$i]=(int)(($a[$i+0]-$this->clip['Xmin'])*$this->clip['zoom']);
        $a[$i+1]=(int)($this->clip['height']-($a[$i+1]-$this->clip['Ymin'])*$this->clip['zoom']);
    }
    
    imagefilledpolygon($im, $a, count($a)/2, $fillcolor);
    imagepolygon($im, $a, count($a)/2, $color);
}

private function getTextColor($im,string $type,$alpha=50)
{
    switch($type)
    {
        case 'A': return imagecolorallocatealpha($im,255,128,64,$alpha);
            break; //area landmark
        case 'M': return imagecolorallocatealpha($im,128,128,0,$alpha);
            
            break; //landmark
        case 'W': return imagecolorallocatealpha($im,64,0,200,$alpha);
            break; //water
        case 'E': return imagecolorallocatealpha($im,64,0,0,$alpha);
            break;
        
        case 'r': return imagecolorallocatealpha($im,128,64,0,$alpha);
            break;
        case 't': return imagecolorallocatealpha($im,64,64,0,$alpha);
            break;
        case 'h': return imagecolorallocatealpha($im,255,64,0,$alpha);
            break;
        case 'i': return imagecolorallocatealpha($im,255,64,0,$alpha);
            break;
        case 'p': return imagecolorallocatealpha($im,255,64,0,$alpha);
            break;
        
        case 'C': return imagecolorallocatealpha($im,124,80,60,$alpha);
            break;
        case 'P': return imagecolorallocatealpha($im,255,255,64,$alpha);
            break;
        
        default:
            return imagecolorallocatealpha($im,255,0,0,$alpha);
    }
}

private function getColor($im,string $type,$alpha=0)
{
    switch($type)
    {
        case 'A': return imagecolorallocatealpha($im,255,128,64,$alpha);
            break; //area landmark
        case 'M': return imagecolorallocatealpha($im,0,128,0,$alpha);
            break; //landmark
        case 'W': return imagecolorallocatealpha($im,0,0,200,$alpha);
            break; //water
        case 'E': return imagecolorallocatealpha($im,0,0,0,$alpha);
            break;
        
        case 'r': return imagecolorallocatealpha($im,128,64,0,$alpha);
            break;
        case 't': return imagecolorallocatealpha($im,64,64,0,$alpha);
            break;
        case 'h': return imagecolorallocatealpha($im,255,64,0,$alpha);
            break;
        case 'i': return imagecolorallocatealpha($im,255,64,0,$alpha);
            break;
        case 'p': return imagecolorallocatealpha($im,255,64,0,$alpha);
            break;
        case 'C': return imagecolorallocatealpha($im,60,80,60,$alpha);
            break; //county
        case 'P': return imagecolorallocatealpha($im,255,255,0,$alpha);
            break; //
        
        default:
            return imagecolorallocatealpha($im,255,0,0,$alpha);
    }
}

private function getFillColor($im,string $type,$alpha=90)
{
    //    echo "type=$type\n";
    switch($type)
    {
        case 'A': return imagecolorallocatealpha($im,255,128,64,$alpha);
            break; //area landmark
        case 'M': return imagecolorallocatealpha($im,0,128,0,$alpha);
            break; //landmark
        case 'W': return imagecolorallocatealpha($im,0,0,200,$alpha);
            break; //water
        case 'E': return imagecolorallocatealpha($im,200,100,100,$alpha);
            break; //county
        case 'r': return imagecolorallocatealpha($im,128,64,0,$alpha);
            break;
        case 't': return imagecolorallocatealpha($im,64,64,0,$alpha);
            break;
        case 'h': return imagecolorallocatealpha($im,255,64,0,$alpha);
            break;
        case 'i': return imagecolorallocatealpha($im,255,64,0,$alpha);
            break;
        case 'p': return imagecolorallocatealpha($im,255,64,0,$alpha);
            break;
        case 'C': return imagecolorallocatealpha($im,130,160,120,98);
            break;
        case 'P': return imagecolorallocatealpha($im,255,255,0,98);
            break;
        default:
            return imagecolorallocatealpha($im,255,0,0,98);
    }
    
}

//
private function getThickness(string $shape,string $type): int
{
    switch($shape)
    {
        case '*': $this->thickness=3;
            break;
        case 'L': $this->thickness=2;
            break;
        case 'P': $this->thickness=1;
            break;
        default: $this->thickness=1;
    }
    switch($type)
    {
        case 'r': $this->thickness=2;
            break;
        case 'h': $this->thickness=5;
            break;
        case 'i': $this->thickness=3;
            break;
        case 'C': $this->thickness=6;
            break;
        default:
    }
    // imagesetthickness($im,$this->thickness);
    return $this->thickness;
}


}