<?php
namespace AtlasGrove;
//todo: refactor for clarity and conistency, et. add "tiles".  add SVG output option. move colors out of code to yaml parameters. add data layer selection.
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
    private $force=false;
    
    private $lastLat=0;
    private $lastLon=0;
    private $lastShape='';
    
    private $thickness;
    private $steps;
    
    private $fonts;
    private $font;
    
    public function __construct($container,SymfonyStyle $io=null)
    {
        parent::__construct($container,$io);
        
        //
        $this->setThickness();
        $this->setAspectType();
        $this->setLODType();
        $this->setRegionType();
        $this->font=$this->getFont();
        
        $this->resetstats();
    }
    
    private function getImageQuality()
    {
        return $this->container->getParameter('image_quality');
    }
    public function setImageQuality(bool $state=false)
    {
        return $this->container->getParameter('image_quality',$state==true);
    }
    
    private function getImageCompressed()
    {
        return $this->container->getParameter('image_compressed');
    }
    public function setImageCompressed(bool $state=false)
    {
        return $this->container->getParameter('image_compressed',$state==true);
    }
    
    private function getImageLogo()
    {
        return $this->container->getParameter('image_logo');
    }
    public function setImageLogo(bool $state=false)
    {
        return $this->container->getParameter('image_logo',$state==true);
    }
    
    private function getColor(string $type)
    {
        return $this->container->getParameter('color_'.$type);
    }
    public function setColor(string $type, int $number=0)
    {
        return $this->container->getParameter('color_'.$type,$number);
    }
    
    public function setThickness(int $thickness=0)
    {
        $this->thickness=$thickness>1?$thickness:1;
    }
    
    public function setSteps(bool $state=false)
    {
        $this->steps=$state;
    }
    
    public function setForce(bool $state=false)
    {
        $this->force=$state;
    }
    
    public function getFont(): string
    {
        if($this->fonts===null) {
            //
            $fontsPath=$this->getRootPath()."/Resources/fonts/\*.ttf";
            $this->fonts=glob($fontsPath);
            
            if(count($this->fonts) > 0)
            {
                $this->font = $this->fonts[rand(0, count($this->fonts)-1)];
            }
            
            //
            $this->font=$this->getRootPath()."/Resources/fonts/Tuffy.ttf";
            $this->logger->info("font={$this->font}");
        }
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
    
    private $validMapObjectType="";
    public function setDataLayers(string $type=null)
    {
        if($type==''|| 0 == strcasecmp($type,'All'))
        {
            $this->validMapObjectType=['A','M','W','E','r','t','h','i','p','C','P','B1','B2','B3'];
            return;
        }
        
        //
        $this->validMapObjectType=[];
        
        $types=explode(",",$type);
        foreach($types as $type)
        {
            $type = UCWords(strtolower(trim($type)));
            
            switch($type) {
                case 'Road':
                    $valids=['r','h','i','p'];
                    break;
                case 'Rail':
                    $valids=['t'];
                    break;
                case 'Water':
                    $valids=['W'];
                    break;
                case 'Area':
                    $valids=['A','E'];
                    break;
                case 'Landmark':
                    $valids=['C','P'];
                    break;
                case 'Border':
                    $valids=['B1','B2','B3'];
                    break;
                default:
                    $valids=[];
            }
            
            $this->validMapObjectType=array_merge($this->validMapObjectType,$valids);
        }
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
        'points'=>0,
        'lines'=>0,
        'polylines'=>0,
        'polygons'=>0,
        'box'=>0,
        'points culled'=>0,
        'roi bounding box culled'=>0,
        'roi bounding boxes culled'=>0,
        'regions'=>0,
        'region ids'=>'',
        'A'=>0,
        'M'=>0,
        'W'=>0,
        'E'=>0,
        'i'=>0,
        'h'=>0,
        'p'=>0,
        'r'=>0,
        't'=>0,
        'C'=>0,
        'P'=>0,
        'B1'=>0,'B2'=>0,'B3'=>0
        ];
    }
    private function printStatistics()
    {
        $this->io->section("Render Statistics");
        $this->io->table(
        ['Name','Value'],
        $this->arrayToNameValue($this->stats)
        );
    }
    
    public function renderShapeFromROI($id) {
        $roi=$this->getROIFromId($id);
        $this->renderShapeROI($roi);
    }
    
    //
    public function renderShape($id) {
        $cacheFilename=$this->cacheIdToFilename($id);
        if(!file_exists($cacheFilename)) {
            return;
        }
        
        $imageFilename=$this->getMapPath()."/{$id}.png";
        if(file_exists($imageFilename)&&$this->force==false) {
            return;
        }
        
        $this->io->section("Render shape {$id} to {$imageFilename}");
        
        $tigerlineCache = new TigerlineCache($this->container,$this->io);
        if($tigerlineCache)
        {
            $tigerlineCache->cacheShape($id);
            
            $this->stats['regions']=1;
            $this->stats['region ids']=$id;
            
            $this->renderImageFromSingleCache($cacheFilename,$imageFilename);
        }
        
        $this->printStatistics();
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
        $finder->files()->depth("== 0")->path("/^.+.txt/")->in($this->getDataCachePath());
        foreach ($finder as $file) {
            $id=preg_replace("/[^0-9]/", "", $file->getRelativePathname());
            if($id>0) {
                $records[]=$id;
            }
        }
        //sort so that states are rendered before county details
        sort($records);
        return $records;
    }
    
    public function cullCachedShapedIDs(array $ids): array {
        $idsInBounds=[];
        $this->rois=[];
        
        foreach($ids as $id)
        {
            $cacheFilename=$this->cacheIdToFilename($id);
            
            $in = fopen($cacheFilename, "rb");
            if($in)
            {
                try {
                    $version = trim(fgets($in));
                    
                    $this->roi=json_decode(trim(fgets($in)),TRUE);
                    $this->arrayToFloat($this->roi);
                    if(!$this->boundingBoxCulled($this->roi)) {
                        $idsInBounds[]=$id;
                    }
                }
                catch (Symfony\Component\Debug\Exception\ContextErrorException $e)
                {
                    $this->logger->error($e->getMessage());
                }
                fclose($in);
            }
        }
        
        return $idsInBounds;
    }
    
    public function cacheIdToFilename(int $id): string
    {
        $file=parent::cacheIdToFilename($id);
        $tigerlineCache = new TigerlineCache($this->container,$this->io);
        if($tigerlineCache)
        {
            $tigerlineCache->cacheShape($id);
        }
        return $file;
    }
    
    public function getROIsToROI(array $ids): array {
        //1
        $this->rois=[];
        
        foreach($ids as $id)
        {
            $cacheFilename=$this->cacheIdToFilename($id);
            
            $in = fopen($cacheFilename, "rb");
            if($in)
            {
                try {
                    $version = trim(fgets($in));
                    
                    $roi=json_decode(trim(fgets($in)),TRUE);
                    $this->arrayToFloat($roi);
                    if(!$this->boundingBoxCulled($roi)) {
                        $this->rois=array_merge([$roi],$this->rois,json_decode(trim(fgets($in)),TRUE));
                    }
                }
                catch (Symfony\Component\Debug\Exception\ContextErrorException $e)
                {
                    $this->logger->error($e->getMessage());
                }
                fclose($in);
            }
        }
        
        // 2
        $inclusiveRoi=[];
        $keys=array_keys($this->rois[0]);
        foreach($keys as $key)
        {
            $inclusiveRoi[$key]=$this->rois[0][$key];
        }
        
        // 3
        foreach($this->rois as $roi)
        {
            if($roi['Xmin']<$inclusiveRoi['Xmin']) {
                $inclusiveRoi['Xmin']=$roi['Xmin'];
            }
            if($roi['Xmax']>$inclusiveRoi['Xmax']) {
                $inclusiveRoi['Xmax']=$roi['Xmax'];
            }
            if($roi['Ymin']<$inclusiveRoi['Ymin']) {
                $inclusiveRoi['Ymin']=$roi['Ymin'];
            }
            if($roi['Ymax']>$inclusiveRoi['Ymax']) {
                $inclusiveRoi['Ymax']=$roi['Ymax'];
            }
        }
        
        //
        $this->clip['Xmin']=$inclusiveRoi['Xmin'];
        $this->clip['Ymin']=$inclusiveRoi['Ymin'];
        $this->clip['Xmax']=$inclusiveRoi['Xmax'];
        $this->clip['Ymax']=$inclusiveRoi['Ymax'];
        
        return $this->clip;
    }
    
    private function getROIFromId($id): array
    {
        $cacheFilename=$this->cacheIdToFilename($id);
        
        $in = fopen($cacheFilename, "rb");
        if($in)
        {
            try {
                $version = trim(fgets($in));
                
                $roi=json_decode(trim(fgets($in)),TRUE);
                $this->arrayToFloat($roi);
            }
            catch (Symfony\Component\Debug\Exception\ContextErrorException $e)
            {
                $this->logger->error($e->getMessage());
            }
            fclose($in);
        }
        
        return $roi;
    }
    
    protected $cull;
    public function setCull(array $cull)
    {
        $this->cull=$cull;
    }
    
    protected function inBounds(float $number, float $min, float $max): bool
    {
        return ($min<=$number && $number<=$max);
    }
    
    protected function boundingBoxCulled(array $bound): bool
    {
        if(count($bound)==4)
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
                return false;
            }
        }
        $this->stats['roi bounding box culled']++;
        return true;
    }
    
    private function boundingBoxToPartialFilename(array $bound)
    {
        return $bound['Xmin'].','.$bound['Ymin'].','.$bound['Xmax'].','.$bound['Ymax'];
    }
    
    //
    public function renderShapeROI(array $cull) {
        $this->cull=$cull;
        
        //
        $this->clip=$cull;
        $this->clip['width']=$this->width;
        $this->clip['height']=$this->height;
        
        //
        if($this->getImageCompressed())
        $im=imagecreate($this->clip['width'],$this->clip['height']);
        else
            $im=imagecreatetruecolor($this->clip['width'],$this->clip['height']);
        if($im !== FALSE)
        {
            $cachedIds=$this->cullCachedShapedIDs($this->getCachedShapeIDs());
            $this->clip=$this->getROIsToROI($cachedIds);
            
            $this->stats['regions']=count($cachedIds);
            $this->stats['region ids']=implode(',',$cachedIds);
            
            $rows=array_map( function ($a) { return [$a]; },$cachedIds);
            $this->io->table(['Id'],$rows);
            
            $partialImageFilename=$this->boundingBoxToPartialFilename($this->cull);
            $imageFilename=$this->getMapPath()."/{$partialImageFilename}.png";
            if(file_exists($imageFilename)&&$this->force==false) {
                return;
            }
            
            $tigerlineCache = new TigerlineCache($this->container,$this->io);
            if($tigerlineCache)
            {
                if($this->steps) {
                    $step=1;
                }
                foreach($cachedIds as $id) {
                    $this->io->section("Render shape {$id} to {$imageFilename}");
                    
                    $tigerlineCache->cacheShape($id);
                    
                    $cacheFilename=$this->cacheIdToFilename($id);
                    
                    $this->renderImageFromROICache($im,$cacheFilename,$imageFilename);
                    
                    if($this->steps) {
                        $stepImageFolder=$this->getMapPath()."/steps/";
                        $this->checkPath($stepImageFolder);
                        $stepImageFilename=$stepImageFolder."/{$partialImageFilename}_{$step}.png";
                        if(file_exists($stepImageFilename)&&$this->force==false) {
                            return;
                        }
                        
                        imagepng($im,$stepImageFilename,$this->getImageQuality()); //temp
                        $step++;
                        
                        $this->logger->debug("Wrote step image $stepImageFilename");
                        $this->io->note(">>>> Wrote step image $stepImageFilename");
                    }
                }
            }
            
            //
            imagepng($im,$imageFilename,$this->getImageQuality());
            imagedestroy($im);
            
            $this->logger->debug("Wrote image $imageFilename");
            $this->io->note(">>>> Wrote image $imageFilename");
        }
        
        $this->printStatistics();
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
                fgets($in); //ignore
                
                //line 4 of cache is clip extended region
                fgets($in); //ignore
                
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
                
                $this->renderImageFromCacheInner($im,$in,$imageFilename);
                
                fclose($in);
            }
            catch (Symfony\Component\Debug\Exception\ContextErrorException $e)
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
                    return;
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
                    return;
                    throw \Symfony\Component\Debug\Exception\ContextErrorException("ROI is not valid.");
                }
                
                //line 3 of cache is rois
                $this->rois=json_decode(trim(fgets($in)),TRUE);
                
                //line 4 of cache is clip extended region
                $this->clip=json_decode(trim(fgets($in)),TRUE);
                if(!is_array($this->clip)) {
                    return;
                    throw \Symfony\Component\Debug\Exception\ContextErrorException("Clip is not valid.");
                }
                $this->arrayToFloat($this->clip);
                if(!$this->arrayIsFloat($this->clip)) {
                    return;
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
                if($this->getImageCompressed())
                $im=imagecreate($this->clip['width'],$this->clip['height']);
                else
                    $im=imagecreatetruecolor($this->clip['width'],$this->clip['height']);
                if($im !== FALSE)
                {
                    $this->renderImageFromCacheInner($im,$in,$imageFilename);
                    
                    //
                    imagepng($im,$imageFilename,$this->getImageQuality());
                    imagedestroy($im);
                    
                    $this->logger->debug("Wrote image $imageFilename");
                    $this->io->note(">>>> Wrote image $imageFilename");
                }
                
            }
            catch (Symfony\Component\Debug\Exception\ContextErrorException $e) {
                $this->logger->error($e->getMessage());
                
                fclose($in);
                
                if(file_exists($cacheFilename)) {
                    $this->logger->error("Cache file $cachefile deleted because of read error.");
                    unlink($cacheFilename);
                }
            }
            finally {
                if(!is_resource($in)){
                    fclose($in);
                }
            }
        }
    }
    
    private function isValidMapObjectType($motype)
    {
        if($this->validMapObjectType==''||in_array($motype,$this->validMapObjectType)) {
            return true;
        }
        return false;
    }
    
    //
    private function renderImageFromCacheInner($im,$in,$imageFilename)
    {
        //
        $backgroundcolor=$this->getColor('background');
        imagefill($im, 0, 0, $backgroundcolor);
        //imageantialias($im,true);
        
        //
        $this->arrayToFloat($this->clip);
        $this->arrayToFloat($this->roi);
        
        //
        imagesetthickness($im,1);
        $this->setThickness(1);
        // $this->setThickness(10);
        
        if($this->isValidMapObjectType('B1')) {
            $this->renderShapeBox($im,$this->clip['Xmin'],$this->clip['Ymin'],$this->clip['Xmax'],$this->clip['Ymax'],$this->getColor('clip_box'));
        }
        
        if($this->isValidMapObjectType('B2')) {
            $this->renderShapeBox($im,$this->roi['Xmin'],$this->roi['Ymin'],$this->roi['Xmax'],$this->roi['Ymax'],$this->getColor('roi_box'));
        }
        
        $this->setThickness(1);
        
        //
        $text='';
        $lines=0;
        while (!feof($in)) {
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
            
            $this->stats[$type]++;
            
            $a=explode(',',substr($data,2));
            $count=count($a);
            
            $select=$this->clipROI($a);
            
            imagesetthickness($im,$this->getThicknessByShapeAndType($shape,$type));
            
            $textcolor=$this->getTextColorByType($im,$type);
            $linecolor=$this->getLineColorByType($im,$type);
            $fillcolor=$this->getFillColorByType($im,$type);
            
            if($this->isValidMapObjectType('B3')) {
                $this->renderShapeBox($im,$select['Xmin'],$select['Ymin'],$select['Xmax'],$select['Ymax'],$this->getColor('select_box'));
            }
            
            //'A','M','W','E','r','t','h','i','p','C','P'
            if($this->isValidMapObjectType($type)) {
                
                // draw shape
                $this->stats['shape']++;
                
                switch($shape) {
                    //point 1
                    case '*':
                        $this->renderShapePoints($im,$a,$linecolor,$fillcolor);
                        break;
                    
                    //3 line
                    case 'L':
                        $this->renderShapePolyline($im,$a,$linecolor,$fillcolor);
                        break;
                    
                    case 'P': // polygon
                        $this->renderShapePolygon($im,$a,$linecolor,$fillcolor);
                        break;
                    
                    default:
                        $this->logger->error("shape=$shape type=$type count=$count");
                }
                
                // draw text
                if(strlen($text)>0) {
                    $this->renderText($im,$text,$textcolor,$select);
                }
            }
        }
        
        //
        if($this->getImageLogo())
        {
            $logo_color=$this->getColor('logo');
            // color = imageColorAllocateAlpha($im, 48, 64, 32,90);
            $size=(sqrt($this->width)/2.0); $angle=0; $x=0; $y = $this->clip['height']-$size;
            imagettftext($im, $size, $angle, $x, $y, $logo_color, $this->font,
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
    private function renderText($im,string $text,$fontcolor,array $select)
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
        
        $r=($fontcolor>>16)&255;
        $g=($fontcolor>>8)&255;
        $b=($fontcolor&255);
        $textcolor = imageColorAllocateAlpha($im, $r, $g, $b,30);
        
        imagettftext($im, $fontsize, $rotation, $x, $y, $textcolor, $this->font, $text);
    }
    
    
    private function renderShapePoint($im,float $lat,float $lon, int $linecolor)
    {
        $this->stats['point']++;
        
        $x=(int)(($lat-$this->clip['Xmin'])*$this->clip['zoom']);
        $y=(int)($this->clip['height']-($lon-$this->clip['Ymin'])*$this->clip['zoom']);
        
        imagesetpixel($im,$x,$y,$linecolor);
    }
    
    
    private function renderShapeBox($im, float $lat1, float $lon1, float $lat2, float $lon2, int $linecolor)
    {
        $this->stats['box']++;
        
        $this->renderShapeLine($im,$lat1,$lon1,$lat2,$lon1,$linecolor);
        $this->renderShapeLine($im,$lat1,$lon2,$lat2,$lon2,$linecolor);
        $this->renderShapeLine($im,$lat1,$lon1,$lat1,$lon2,$linecolor);
        $this->renderShapeLine($im,$lat2,$lon1,$lat2,$lon2,$linecolor);
    }
    
    private function renderShapeLine($im, float $lat1, float $lon1, float $lat2,float $lon2, int $linecolor)
    {
        $this->stats['lines']++;
        
        if($lat1<$this->clip['Xmin'] && $lat2<$this->clip['Xmin']) return;
        if($lat1>$this->clip['Xmax'] && $lat2>$this->clip['Xmax']) return;
        
        if($lon1<$this->clip['Ymin'] && $lon2<$this->clip['Ymin']) return;
        if($lon1>$this->clip['Ymax'] && $lon2>$this->clip['Ymax']) return;
        
        $x1=(int)(($lat1-$this->clip['Xmin'])*$this->clip['zoom']);
        $y1=(int)(($lon1-$this->clip['Ymin'])*$this->clip['zoom']);
        $x2=(int)(($lat2-$this->clip['Xmin'])*$this->clip['zoom']);
        $y2=(int)(($lon2-$this->clip['Ymin'])*$this->clip['zoom']);
        imageline($im,$x1,$this->clip['height']-$y1,$x2,$this->clip['height']-$y2,$linecolor);
    }
    
    private function renderShapeThickline($im,float $lat1,float $lon1,float $lat2,float $lon2,$linecolor,$thickness)
    {
        if($this->thickness<=1) {
            return $this->renderShapeLine($im,$lat1,$lon1,$lat2,$lon2,$linecolor);
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
        
        imagefilledpolygon($im, $p, count($p)/2, $linecolor);
    }
    
    private function renderShapePoints($im, array $a, int $linecolor)
    {
        $count=count($a);
        
        if(1==($count%2)) {
            $count--;
        }
        if(!($count>=0)) {
            throw \Symfony\Component\Debug\Exception\ContextErrorException("Point count {$count}.");
        }
        
        $this->stats['points']++;
        
        for($i=0;$i<$count;$i+=2)
        {
            $this->renderShapePoint($im,$a[$i],$a[$i+1],$linecolor);
        }
    }
    
    private function renderShapePolyline($im, array $a, $linecolor, $fillcolor)
    {
        $count=count($a);
        
        if(!($count>=2)) {
            throw \Symfony\Component\Debug\Exception\ContextErrorException("Polyline count {$count}.");
        }
        
        $this->stats['polylines']++;
        
        $lat1=$a[0]; $lon1=$a[0+1];
        for($i=0;$i<$count;$i+=2)
        {
            $lat2=$a[$i]; $lon2=$a[$i+1];
            $this->renderShapeThickline($im,$lat1,$lon1,$lat2,$lon2,$linecolor,$this->thickness);
            $lat1=$lat2; $lon1=$lon2;
        }
    }
    
    private function renderShapePolygon($im, array $a, $linecolor, $fillcolor)
    {
        $count=count($a);
        
        if(!($count>=3)) {
            throw \Symfony\Component\Debug\Exception\ContextErrorException("Polygon count {$count}.");
        }
        
        $this->stats['polygons']++;
        
        $count=count($a);
        for($i=0;$i<$count;$i+=2)
        {
            $a[$i]=(int)(($a[$i+0]-$this->clip['Xmin'])*$this->clip['zoom']);
            $a[$i+1]=(int)($this->clip['height']-($a[$i+1]-$this->clip['Ymin'])*$this->clip['zoom']);
        }
        
        imagefilledpolygon($im, $a, count($a)/2, $fillcolor);
        imagepolygon($im, $a, count($a)/2, $linecolor);
    }
    
    //todo: move colors to parameters and a state-optional color code?
    private function getTextColorByType($im,string $type,$alpha=50)
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
    
    // A M W E
    // i h p r t
    
    private function getLineColorByType($im,string $type,$alpha=0)
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
    
    private function getFillColorByType($im,string $type,$alpha=70)
    {
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
    private function getThicknessByShapeAndType(string $shape,string $type): int
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
    
    /*
    
    protected function minimumResolutionCull(array $roi): bool
    {
    return false;
    
    $minRes=0.00001;
    
    if(!is_array($this->clip)) {
    return false;
    }
    //$this->printArray($roi); $this->printClip();
    
    $rw=abs($roi['Xmax']-$roi['Xmin']);
    $rh=abs($roi['Ymax']-$roi['Ymin']);
    // echo "rw rh =  :$rw $rh = \n";
    
    if($rw==0 || $rh==0) {
    return true;
    }
    
    $dw=(float)abs($this->clip['Xmax']-$this->clip['Xmin']);
    $dh=(float)abs($this->clip['Ymax']-$this->clip['Ymin']);
    
    //  echo "dw/dh =  :$dw $dh = \n";
    
    if($dw==0 || $dh==0) {
    return true;
    }
    
    $w=$rw/$dw;
    $h=$rh/$dh;
    // echo "rw/dw =  :".($w)." = \n";
    //echo "rh/dh =  : ".($h)." = \n";
    
    if( ($w < $minRes) && ($h < $minRes)) {
    return true;
    }
    
    return false;
    }
    
    */
}