<?php
namespace AtlasGrove;
// too: refactor for consistency and error handling. remove precision? add better checks for file integrity. remove old bloat. add "us" level to cache.

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

class TigerlineCache extends Tigerline
{
    protected $out;
    protected $opt;
    
    protected $roi;
    protected $clip;
    
    public function __construct($container,SymfonyStyle $io=null)
    {
        parent::__construct($container,$io);
        
        $this->resetStats();
    }
    
    private $stats;
    public function resetStats()
    {
        //
        $this->stats['dbf files']=0;
        $this->stats['shx files']=0;
        $this->stats['shp files']=0;
        $this->stats['shapes']=0;
        
        $this->stats['point']=0;
        $this->stats['polyline']=0;
        $this->stats['polygon']=0;
        
        $this->stats['points']=0;
        $this->stats['points out']=0;
        $this->stats['points roi culled']=0;
        
        $this->stats['arealm files']=0;
        $this->stats['pointlm files']=0;
        $this->stats['areawater files']=0;
        $this->stats['edges files']=0;
        
        $this->stats['county records']=0;
        $this->stats['place records']=0;
        
        $this->stats['records']=0;
        $this->stats['dbf records']=0;
        $this->stats['shx records']=0;
        $this->stats['shp records']=0;
    }
    
    
    protected function printStatistics()
    {
        $this->io->section('Cache Statistics');
        
        $header=['Name','Count','Result'];
        $data=[];
        
        $data[]=["Shapes",$this->stats['shapes'],""];
        
        try {
            if($this->stats['points']==0)
            $result=0;
            else
                $result = (100*($this->stats['points out']/$this->stats['points']));
        } catch(DivisionByZeroError $e)
        {
            $result=0;
        }
        $data[]=["Points: (in)",$this->stats['points']." (out) ".$this->stats['points out'],"%$result"];
        
        try {
            if($this->stats['points']==0)
            $result=0;
            else
                $result=(100*($this->stats['points roi culled']/$this->stats['points']));
        } catch(DivisionByZeroError $e)
        {
            $result=0;
        }
        $data[]=["Points ROI Culled",$this->stats['points roi culled'],"%$result"];
        
        $this->io->table($header,$data);
        
        //
        $this->io->section("Total Cache Statistics");
        $this->io->table(
        ['Name','Count'],
        $this->arrayToNameValue($this->stats)
        );
    }
    
    private function getCachePrecision()
    {
        return $this->container->getParameter('cache_precision');
    }
    public function setCachePrecision(int $number=0)
    {
        return $this->container->getParameter('cache_precision',$number);
    }
    
    private function getCacheNoDups()
    {
        return $this->container->getParameter('cache_nodups');
    }
    public function setCacheNoDups(int $number=0)
    {
        return $this->container->getParameter('cache_nodups',$number);
    }
    
    // why?
    protected function clipPrecision(float $number): float
    {
        $precision=$this->getCachePrecision();
        if($precision>0) {
            return sprintf("%4.".$precision."f",$number);
        }
        return $number;
    }
    
    protected function setShapePoly(float $lat=0, float $lon=0, string $shape='')
    {
        $this->lastLat=$lat;
        $this->lastLon=$lon;
        $this->lastShape=$shape;
    }
    
    
    //
    private function correctTextAbbreviations(string $text): string
    {
        $abbr=[
        'Rd'=>'Road',
        'Frk'=>'Fork',
        'Ln'=>'Lane',
        'Circ'=>'Circle',
        'Hwy'=>'Highway',
        'Dr'=>'Drive',
        'Crk'=>'Creek',
        'Lk'=>'Lake',
        'Frst'=>'Forest',
        'Riv'=>'River',
        'Rfg'=>'Refuge',
        'Plat'=>'Plateau',
        'Pk'=>'Park',
        'Rdg'=>'Ridge',
        'Hosp'=>'Hospital',
        'Br'=>'Brook',
        'Byu'=>'Bayou',
        'Rlwy'=>'Railway',
        'Frwy'=>'Freeway',
        'Trl'=>'Trail',
        'Pk'=>'Park',
        'Plnt'=>'Plant',
        'Byp'=>'By-pass',
        'Pkwy'=>'Park-way',
        'Expwy'=>'Express-way'
        ];
        
        $text=trim($text);
        if($text=='') return "";
        
        $a=explode(' ',$text);
        foreach($a as $k=>$v)
        {
            $end=$a[$k];
            if(isset($abbr[$end])) {
                $a[$k]=$abbr[$end];
            }
        }
        return implode(' ',$a);
    }
    
    
    public function cacheShapes(array $files,bool $force=false)
    {
        foreach($files as $file)
        {
            list($id)=explode("\t",$file);
            $this->cacheShape($id,$force);
        }
    }
    
    public function cacheShape(int $id,bool $force=false): bool
    {
        $cacheFilename=$this->cacheIdToFilename($id);
        
        $this->rois=[];
        
        $this->io->section("Cache Shape {$id} to {$cacheFilename}");
        
        //
        if($force==false && file_exists($cacheFilename)) {
            $in = fopen($cacheFilename, "r");
            if($in)
            {
                $version = trim(fgets($in));
                fclose($in);
            }
            if($version===$this->version) {
                $this->io->note("$cacheFilename already cached version {$version}.");
                return true;
            }
            $this->io->note("$cacheFilename version {$version} -- Current version {$this->version}.");
        }
        
        //
        $this->out = fopen($cacheFilename, "w");
        if($this->out !== FALSE) {
            $files=$this->getFilesForId($id);
            
            foreach($files as $file)
            {
                // note: refactor here
                // list($mfha,$mfhaRecords)=$this->cacheGetShapefile($file['dbf']);
                
                //mfha mfharecords
                $this->cacheShapefileContents(
                $file,
                $cacheFilename
                );
            }
            
            fclose($this->out);
            
            $this->io->note("Wrote $cacheFilename.");
        }
        
        // note: move to func
        // rewrite lines 3 and 4
        $lines=explode("\n",file_get_contents($cacheFilename));
        $lines[2]=json_encode($this->rois);
        $lines[3]=json_encode($this->clip);
        file_put_contents($cacheFilename,implode("\n",$lines));
        $this->io->note("Rewrote $cacheFilename.");
        
        //
        $this->printStatistics();
        return false;
    }
    
    
    //
    protected function cacheShapefileContents(array $fileArray, string $cacheFilename)
    {
        extract($fileArray);
        
        if(!file_exists($dbfFilename))
        {
            // $this->io->error("Could not find file $dbfFilename.");
            return;
        }
        if(!file_exists($shpFilename))
        {
            // $this->io->error("Could not find file $shpFilename.");
            return;
        }
        
        $dbfRecords = DBase::fromFile($dbfFilename);
        
        $this->stats['dbf files']++;
        $this->stats['dbf records']+=count($dbfRecords);
        $this->stats[$prefix.' files']++;
        
        $record=each($dbfRecords);
        $fields=array_keys($record);
        
        //foreach ($dbfRecords as $record)
        // {
        //   $fields=array_keys($record->getArrayCopy());
        //   break;
        // }
        
        // $record_numbers = count($dbfRecords);
        
        //
        
        $shpHandle = fopen($shpFilename, "rb");
        if($shpHandle === FALSE)
        {
            $this->io->error("Could not open shape file $shpFilename.");
            return;
        }
        
        $shpFileSize=filesize($shpFilename);
        
        $this->stats['shp files']++;
        
        //main field header
        $binarydata = fread($shpHandle, 100);
        $mfha = unpack(
        "NFileCode/NUnused4/NUnused8/NUnused12/NUnused16/NUnused20/NFileLength/IVersion/IShapeType/dXmin/dYmin/dXmax/dYmax/dZmin/dZmax/dMmin/dMmax",
        $binarydata);
        $this->printMFHAResolution($mfha);
        
        //if(!isset($this->roi))
        //{
        $this->roi['Xmin']=$mfha['Xmin'];
        $this->roi['Xmax']=$mfha['Xmax'];
        $this->roi['Ymin']=$mfha['Ymin'];
        $this->roi['Ymax']=$mfha['Ymax'];
        // }
        $this->rois[]=$this->roi;
        
        //
        $w=abs($this->roi['Xmax']-$this->roi['Xmin']);
        $h=abs($this->roi['Ymax']-$this->roi['Ymin']);
        
        if(ftell($this->out)==0)
        {
            //line 1
            fputs($this->out,$this->version."\r\n");
            
            //line 2
            fputs($this->out,json_encode($this->roi)."\r\n");
            
            // line 3
            fputs($this->out,"# placeholding for ROIs\r\n");
                
            //line 3 (initial values)
            $this->clip=$this->computeRegionMids($this->roi);
            
            fputs($this->out,"# placeholding for Clip");
            }
        
        //
        $this->setShapePoly();
        
        //
        $count=0;
        $pos=ftell($shpHandle);
        while(!feof($shpHandle) && ($pos+8)<$shpFileSize)
        {
            $this->stats['shp records']++;
            $this->stats['records']++;
            
            //
            $row = $dbfRecords[$count];
            
            $text=$row[$nameField];
            
            //
            $binarydata = fread($shpHandle, 8);
            $pos=ftell($shpHandle);
            $rh = unpack("NRecordNumber/NContentLength",$binarydata);
            
            //
            $binarydata = fread($shpHandle, 4);
            $rc = unpack("IShapeType",$binarydata);
            
            // A M W E
            // i h p r t
            if(isset($row['ROADFLG']) && $row['ROADFLG']=='Y' && strstr($text,'Interstate Hwy'))
            $type2='i';
            else if(isset($row['ROADFLG']) && $row['ROADFLG']=='Y' && strstr($text,'Hwy'))
            $type2='h';
            else if(isset($row['ROADFLG']) && $row['ROADFLG']=='Y' && strstr($text,'Pkwy'))
            $type2='p';
            else if(isset($row['ROADFLG']) && $row['ROADFLG']=='Y')
            $type2='r';
            else if(isset($row['RAILFLG']) && $row['RAILFLG']=='Y')
            $type2='t';
            else
                $type2=$type;
            
            switch($rc['ShapeType'])
            {
                case 1:
                    $this->cacheShapefileTypePoint($shpHandle,$type2,$rh['ContentLength'],$text);
                    break;
                case 3:
                    $this->cacheShapefileTypePolyline($shpHandle,$type2,$rh['ContentLength'],$text);
                    break;
                case 5:
                    $this->cacheShapefileTypePolygon($shpHandle,$type2,$rh['ContentLength'],$text);
                    break;
                default:
                    $this->$this->io->warning('Unknown shape type: '.$rc['ShapeType']);
            }
            
            //
            fseek($shpHandle,$pos + $rh['ContentLength']*2,SEEK_SET);
            $pos=ftell($shpHandle);
            
            $count++;
        }
        
        fclose($shpHandle);
    }
    
    
    //
    protected function cacheShapefileTypePoint($handle,string $type,int $length=0,string $text='')
    {
        $this->stats['point']++;
        
        $binarydata = fread($handle, 16);
        $point = unpack("dX/dY",$binarydata);
        
        $this->cacheOutPoint($point['X'],$point['Y'],$type,$text);
        $this->stats['points']++;
    }
    
    
    //
    protected function cacheShapefileTypePolyline($handle,string $type,int $length=0,string $text='')
    {
        $this->stats['polyline']++;
        
        $binarydata = fread($handle, 40);
        $h = unpack("dXmin/dYmin/dXmax/dYmax/InumParts/InumPoints/",$binarydata);
        
        $pos=ftell($handle);
        
        $numParts=$h['numParts'];
        $numPoints=$h['numPoints'];
        
        //
        $offset=[];
        $part=0;
        while(($part+1)<=$numParts)
        {
            $binarydata = fread($handle, 4);
            if(feof($handle))
            { break; }
        
        $d = unpack("VStart",$binarydata);
        
        $offset[$part]=$d['Start'];
        $part++;
    }
    
    //
    $pointsPerPart=[];
    if($numParts==1) {
        $pointsPerPart[0]=$numPoints;
    } else
    {
        $part=0;
        $points=0;
        foreach($offset as $p=>$o)
        {
            if(($part+1)<$numParts)
            { $count=$offset[$part+1]-$offset[$part]; }
            else
            { $count=$numPoints-$points; }
            $points+=$count;
            $pointsPerPart[$part]=$count;
            
            $part++;
        }
    }
    
    //
    $part=0;
    foreach($offset as $key=>$startPoint)
    {
        $partPoints=$pointsPerPart[$part];
        
        $pointOffset=$pos+($numParts*4)+($startPoint*16);
        fseek($handle,$pointOffset);
        
        $points=0;
        $first['X']=0;
        $first['Y']=0;
        do
        {
            $binarydata = fread($handle, 16);
            if(feof($handle)) { break; }
        
        $point = unpack("dX/dY",$binarydata);
        $this->stats['points']++;
        
        if($points==0)
        {
            $first['X']=$point['X']; $first['Y']=$point['Y'];
            $this->cacheOutPolylineStart($point['X'],$point['Y'],$type,$text);
        }
        else
        {
            $this->cacheOutPolyline($point['X'],$point['Y']);
        }
        
        $points++;
    } while(
    $points<$numPoints &&
    $points<$partPoints
    );
    
    $part++;
    if($part>$numParts) break;
}
}



//
protected function cacheShapefileTypePolygon($handle,string $type,int $length=0,string $text='')
{
    // $this->stats['polygon']++;
    
    $binarydata = fread($handle, 40);
    $h = unpack("dXmin/dYmin/dXmax/dYmax/InumParts/InumPoints/",$binarydata);
    
    $pos=ftell($handle);
    
    $numParts=$h['numParts'];
    $numPoints=$h['numPoints'];
    
    //
    $offset=[];
    $part=0;
    while($part<$numParts)
    {
        $binarydata = fread($handle, 4);
        $d = unpack("VStart",$binarydata);
        $offset[$part]=$d['Start'];
        
        $part++;
    }
    
    //
    $pointsPerPart=[];
    if($numParts==1) {
        $pointsPerPart[0]=$numPoints;
    }
    else
    {
        $part=0;
        $points=0;
        foreach($offset as $p=>$o)
        {
            if(($part+1)<$numParts)
            { $count=$offset[$part+1]-$offset[$part]; }
            else
            { $count=$numPoints-$points; }
            $points+=$count;
            $pointsPerPart[$part]=$count;
            
            $part++;
        }
    }
    
    //
    $part=0;
    foreach($offset as $key=>$startPoint)
    {
        $partPoints=$pointsPerPart[$part];
        
        $pointOffset=$pos+($numParts*4)+($startPoint*16);
        fseek($handle,$pointOffset);
        
        $points=0;
        $first['X']=0;
        $first['Y']=0;
        do
        {
            $binarydata = fread($handle, 16);
            if(feof($handle)) break;
        
        $point = unpack("dX/dY",$binarydata);
        $this->stats['points']++;
        
        if($points==0)
        {
            $first['X']=$point['X']; $first['Y']=$point['Y'];
            $this->cacheOutPolygonStart($point['X'],$point['Y'],$type,$text);
        }
        else
            $this->cacheOutPolygon($point['X'],$point['Y']);
        
        $points++;
    } while(
    $points<$numPoints &&
    $points<$partPoints
    );
    
    $part++;
    if($part>$numParts) break;
}
}


//
protected function cacheOutPoint(float $x,float $y,string $type=' ',string $text='')
{
    $x=$this->clipPrecision($x);
    $y=$this->clipPrecision($y);
    
    fputs($this->out,"\r\n".$this->correctTextAbbreviations($text));
    fputs($this->out,"\r\n*$type".$x.",".$y);
    
    $this->stats['points out']++;
    $this->stats['shapes']++;
    
    $this->updateCacheClipBounds($x,$y);
}

protected function cacheOutPolylineStart(float $x,float $y,string $type=' ',string $text='')
{
    $x=$this->clipPrecision($x);
    $y=$this->clipPrecision($y);
    
    fputs($this->out,"\r\n".$this->correctTextAbbreviations($text));
    fputs($this->out,"\r\nL$type".$x.",".$y);
    
    $this->stats['points out']++;
    $this->stats['shapes']++;
    
    $this->updateCacheClipBounds($x,$y);
    $this->setShapePoly($x,$y,'P');
}

protected function cacheOutPolyline(float $x,float $y)
{
    $x=$this->clipPrecision($x);
    $y=$this->clipPrecision($y);
    
    if($this->getCacheNoDups() && $this->lastShape=='L' && $this->lastLat==$x && $this->lastLon==$y)
    {
        $this->stats['points roi culled']++;
        return;
    }
    
    fputs($this->out,",".$x.",".$y);
    
    $this->stats['points out']++;
    
    $this->updateCacheClipBounds($x,$y);
    $this->setShapePoly($x,$y,'L');
}

protected function cacheOutPolygonStart(float $x, float $y, string $type=' ', string $text='')
{
    $x=$this->clipPrecision($x);
    $y=$this->clipPrecision($y);
    
    fputs($this->out,"\r\n".$this->correctTextAbbreviations($text));
    fputs($this->out,"\r\nP$type".$x.",".$y);
    
    $this->stats['points out']++;
    $this->stats['shapes']++;
    $this->stats['polygon']++;
    
    $this->updateCacheClipBounds($x,$y);
    $this->setShapePoly($x,$y,'P');
}

protected function cacheOutPolygon(float $x,float $y)
{
    $x=$this->clipPrecision($x);
    $y=$this->clipPrecision($y);
    
    if($this->getCacheNoDups() && $this->lastShape=='P' && $this->lastLat==$x && $this->lastLon==$y)
    {
        $this->stats['points roi culled']++;
        return;
    }
    
    fputs($this->out,",".$x.",".$y);
    
    $this->stats['points out']++;
    
    $this->updateCacheClipBounds($x,$y);
    $this->setShapePoly($x,$y,'P');
}


//


/**
*/
public function cacheStatesList() {
    $records=[];
    
    // get list of county folders
    $finder = new Finder();
    $finder->directories()->depth(" == 0")->path("/^[\d]{2,2}_(.*)/")->in($this->getRootDataPath()."/".$this->getYearFolder()."/");
    foreach ($finder as $dir) {
        $state_folder=$dir->getRelativePathname();
        
        $a=explode('_',$state_folder);
        $statefp=$a[0];
        $state=UCWords(strtolower($a[1]));
        
        // fe_2007_47_county.dbf
        $records[]=$statefp."\t".$state_folder."\t".$state;
    }
    
    //
    file_put_contents($this->getDataCachePath()."/list.states.txt",implode("\n",$records));
    
    //
    return $records;
}


/**
*/
public function cacheCountiesList() {
    $records=[];
    
    // get list of county folders
    $finder = new Finder();
    $finder->directories()->depth("== 0")->path("/^[\d]{2,2}_(.*)/")->in($this->getDataPath());
    foreach ($finder as $dir) {
        $state_folder=$dir->getRelativePathname();
        
        $a=explode('_',$state_folder);
        $statefp=$a[0];
        
        // fe_2007_47_county.dbf
        $dbfFilename=$this->getDataPath()."/{$state_folder}/fe_{$this->yearfp}_{$statefp}_county.dbf";
        if(file_exists($dbfFilename))
        {
            $dbfRecords = DBase::fromFile($dbfFilename);
            $this->stats['dbf files']++;
            $this->stats['dbf records']+=count($dbfRecords);
              
            foreach ($dbfRecords as $record) {
                //      $records[]=$record->getArrayCopy();
                $records[]=$record['CNTYIDFP']."\t".$record['NAME']."\t".$record['NAMELSAD'];
            }
        }
    }
    
    //
    file_put_contents($this->getDataCachePath()."/list.counties.txt",implode("\n",$records));
    
    //
    return $records;
}



protected function getFilesForId(string $id): array
{
    $files=[];
    
    $finder = new Finder();
    
    switch(strlen($id))
    {
        case 0:
        $file="/fe_{$this->yearfp}_us_state";
        foreach($this->tigerline_subtypes as $subtype)
        {
            $this->stats[$subtype['prefix'].' files']++;
            
            $subtype['nameField']='NAMELSAD';
            $prefix=$subtype['prefix'];
            $files[] = [
            'type'=>$subtype['type'],
            'prefix'=>$subtype['prefix'],
            'nameField'=>$subtype['nameField'],
            'shpFilename'=>$this->getDataPath()."/".$file.".shp",
            'shxFilename'=>$this->getDataPath()."/".$file.".shx",
            'dbfFilename'=>$this->getDataPath()."/".$file.".dbf",
            'prjFilename'=>$this->getDataPath()."/".$file.".prj",
            ];
        }
        return $files;
        break;
    
    case 2:
        $finder->directories()->depth("== 0")->path("/^".$id."_(.*)/")->in($this->getDataPath());
        foreach ($finder as $dir)
        {
            $state_folder=$dir->getRelativePathname();
    }
    $file= "{$state_folder}/fe_{$this->yearfp}_{$id}_county";
    foreach($this->tigerline_subtypes as $subtype)
    {
        $this->stats[$subtype['prefix'].' files']++;
        
        $subtype['nameField']='NAMELSAD';
        
        $prefix=$subtype['prefix'];
        $files[]= [
        'type'=>$subtype['type'],
        'prefix'=>$subtype['prefix'],
        'nameField'=>$subtype['nameField'],
        'shpFilename'=>$this->getDataPath()."/".$file.".shp",
        'shxFilename'=>$this->getDataPath()."/".$file.".shx",
        'dbfFilename'=>$this->getDataPath()."/".$file.".dbf",
        'prjFilename'=>$this->getDataPath()."/".$file.".prj",
        ];
    }
    return $files;
    //break;
    
    case 5:
        $finder->directories()->depth("== 0")->path("/^".substr($id,0,2)."_(.*)/")->in($this->getDataPath());
        foreach ($finder as $dir)
        {
            $state_folder=$dir->getRelativePathname();
    }
    
    $finder->directories()->depth("== 0")->path("/^".$id."_(.*)/")->in($this->getDataPath()."/".$state_folder);
    foreach ($finder as $dir)
    {
        $county_folder=$dir->getRelativePathname();
    }
    
    $file= "{$state_folder}/{$county_folder}/fe_{$this->yearfp}_{$id}_";
    
    foreach($this->tigerline_subtypes as $subtype)
    {
        $this->stats[$subtype['prefix'].' files']++;
        
        $prefix=$subtype['prefix'];
        $files[]= [
        'type'=>$subtype['type'],
        'prefix'=>$subtype['prefix'],
        'nameField'=>$subtype['nameField'],
        'shpFilename'=>$this->getDataPath()."/".$file.$prefix.".shp",
        'shxFilename'=>$this->getDataPath()."/".$file.$prefix.".shx",
        'dbfFilename'=>$this->getDataPath()."/".$file.$prefix.".dbf",
        'prjFilename'=>$this->getDataPath()."/".$file.$prefix.".prj",
        ];
    }
    return $files;
    //break;
    
    default:
        return $files;
}

return $files;
}

//
/*
protected function cacheGetShapefile(string $filename): array
{
if(file_exists($filename))
{
$mfha=$this->cacheGetShapefileMFHA($filename);
$mfhaRecords $this->cacheGetShapefileContentsMainheader($mfha);
return [$mfha,$mfhaRecords];
}
return [];
}

protected function cacheGetShapefileMFHA(string $filename): array
{
$size=filesize($filename);
// $this->io->note("Shapefile MFHA: $filename ($size bytes)");
$handle = fopen($filename, "r");
if($handle !== FALSE)
{
$binarydata = fread($handle, 100);
//main field header
$mfha = unpack(
"NFileCode/NUnused4/NUnused8/NUnused12/NUnused16/NUnused20/NFileLength/IVersion/IShapeType/dXmin/dYmin/dXmax/dYmax/dZmin/dZmax/dMmin/dMmax",
$binarydata);
fclose($handle);
}
$mfha=$this->removeUnused($mfha);
$this->printArray($mfha,"MFHA");
return $mfha;
}

protected function cacheGetShapefileContentsMainheader(array $mfha): array
{
$records=[];

foreach($mfha as $key=>$value)
{
$records[]=[
'key'=>$key,
'value'=>$value,
'type'=>($key=='ShapeType')?$this->cacheGetShapeType($value):""
];
}
// $this->printArray($records,"MFHA");

return $records;
}


// I d
protected function cacheGetShapeType(string $type): string
{
switch($type)
{
case 0: return 'Null Shape';
break;
case 1: return 'Point';
break;
case 3: return 'PolyLine';
break;
case 5: return 'Polygon';
break;
case 8: return 'MultiPoint';
break;
case 11: return 'PointZ';
break;
case 13: return 'PolyLineZ';
break;
case 15: return 'PolygonZ';
break;
case 18: return 'MultiPointZ';
break;
case 21: return 'PointM';
break;
case 23: return 'PolyLineM';
break;
case 25: return 'PolygonM';
break;
case 28: return 'MultiPointM';
break;
case 31: return 'MultiPatch';
break;
default:
return '';
}
}

*/




//
public function getShx($shxFilename): array
{
    try {
        $mfha=null;
        $shx=[];
        
        $this->stats['shx files']++;
        $size=filesize($shxFilename);
        // $this->logger->debug("SHX::: $shxFilename ($size bytes)");
        
        $handle = @fopen($shxFilename, "rb");
        if($handle !== FALSE) {
            $binarydata = @fread($handle, 100);
            
            //main field header
            $mfha = unpack(
            "NFileCode/NUnused4/NUnused8/NUnused12/NUnused16/NUnused20/NFileLength/IVersion/IShapeType/dXmin/dYmin/dXmax/dYmax/dZmin/dZmax/dMmin/dMmax",
            $binarydata);
            //  print_shapefile_contents_mainheader($mfha);
            
            $count=0;
            $pos=ftell($handle);
            while(!feof($handle) && ($pos+8)<$size)
            {
                $binarydata = fread($handle, 8);
                $pos=ftell($handle);
                $rh = unpack("NOffset/NContentLength",$binarydata);
                $this->stats['shx records']++;
                
                $shx[]=array($rh['Offset'],$rh['ContentLength']);
                
                //   $this->logger->debug("+++ [".ftell($handle)."] Offset: ".$rh['Offset']."w ContentLength: ".$rh['ContentLength']."w");
                
                //
                fseek($handle,$pos + $rh['ContentLength']*2,SEEK_SET);
                $pos=ftell($handle);
                
                $count++;
            }
            
            // $this->logger->debug("SHX>>> $count index count");
            
            fclose($handle);
        } //handle if
        
        return ['header'=>$mfha,'index'=>$shx];
    }
    catch (\Symfony\Component\Debug\Exception\ContextErrorException $e)
    {
        return ['header'=>[],'index'=>[]];
    }
}

}