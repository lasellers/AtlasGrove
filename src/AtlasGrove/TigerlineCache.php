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

class TigerlineCache extends Tigerline
{
    
    protected $out;
    protected $opt;
    
    
    
    public function __construct($container,SymfonyStyle $io)
    {
        parent::__construct($container,$io);
        
        // $this->logger->debug(" dw=$dw dh=$dh");
        
        //
        // $this->clip=null;
        //$this->roi=null;
        
        $this->opt['precision']=0;
        
        $this->resetCacheStats();
    }
    
    
    private $cacheStats;
    public function resetCacheStats()
    {
        //
        $this->cacheStats['shx']=0;
        $this->cacheStats['shxrecords']=0;
        $this->cacheStats['shapes']=0;
        $this->cacheStats['records']=0;
        $this->cacheStats['points']=0;
        $this->cacheStats['points_roi_culled']=0;
        $this->cacheStats['records_minres_culled']=0;
        $this->cacheStats['files']=0;
        $this->cacheStats['files_minres_culled']=0;
        $this->cacheStats['points out']=0;
        $this->cacheStats['arealm_count']=0;
        $this->cacheStats['pointlm_count']=0;
        $this->cacheStats['areawater_count']=0;
        $this->cacheStats['edges_count']=0;
        $this->cacheStats['county_count']=0;
        $this->cacheStats['place_count']=0;
        $this->cacheStats['dbf']=0;
        $this->cacheStats['shx']=0;
        $this->cacheStats['shp']=0;
        $this->cacheStats['dbfrecords']=0;
        $this->cacheStats['shxrecords']=0;
        $this->cacheStats['shprecords']=0;
    }
    
    // *****************************************************************************
    protected function printCacheStatisticsTotal()
    {
        $this->io->section("Total Cache Statistics");
        $this->io->table(
        ['Name','Count'],
        [
        ["Area Landmarks",$this->cacheStats['arealm_count']],
        ["Point Landmarks",$this->cacheStats['pointlm_count']],
        ["Area Water",$this->cacheStats['areawater_count']],
        ["Edges",$this->cacheStats['edges_count']],
        ["County",$this->cacheStats['county_count']],
        ["Places",$this->cacheStats['place_count']]
        ]
        );
    }
    
    
    protected function printCacheStatistics()
    {
        $this->io->section('Cache Statistics');
        
        $header=['Name','Count','Result'];
        $data=[];
        
        $data[]=["Files",$this->cacheStats['files'],""];
        
        try {
            $result=(100*($this->cacheStats['files_minres_culled']/$this->cacheStats['files']));
        } catch(\Symfony\Component\Debug\Exception\ContextErrorException $e)
        {
            $result=0;
        }
        $data[]=["Files minres Culled",$this->cacheStats['files_minres_culled'],"%$result"];
        
        $data[]=["Records",$this->cacheStats['records'],""];
        
        try {
            $result = (100*($this->cacheStats['records_minres_culled']/$this->cacheStats['records']));
        } catch(\Symfony\Component\Debug\Exception\ContextErrorException $e)
        {
            $result=0;
        }
        $data[]=["Records minres Culled",$this->cacheStats['records_minres_culled'],"%$result"];
        
        $data[]=["Shapes",$this->cacheStats['shapes'],""];
        
        try {
            $result = (100*($this->cacheStats['points out']/$this->cacheStats['points']));
        } catch(\Symfony\Component\Debug\Exception\ContextErrorException $e)
        {
            $result=0;
        }
        $data[]=["Points: (in)",$this->cacheStats['points']." (out) ".$this->cacheStats['points out'],"%$result"];
        
        try {
            $result=(100*($this->cacheStats['points_roi_culled']/$this->cacheStats['points']));
        } catch(\Symfony\Component\Debug\Exception\ContextErrorException $e)
        {
            $result=0;
        }
        $data[]=["Points ROI Culled",$this->cacheStats['points_roi_culled'],"%$result"];
        
        $this->io->table($header,$data);
    }
    
    
    // *****************************************************************************
    protected function clipPrecision(float $number): float
    {
        if($this->opt['precision']>0) {
            return sprintf("%4.".$this->opt['precision']."f",$number);
        }
        return $number;
    }
    
    protected function setShapePoly(float $lat=0,float $lon=0,string $shape='')
    {
        $this->lastlat=$lat;
        $this->lastlon=$lon;
        $this->lastshape=$shape;
    }
    
    
    // *****************************************************************************
    private function correctTextAbbreviations(string $text): string
    {
        $abbr=array(
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
        );
        
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
    // *****************************************************************************
    
    
    private $minRes=0.00001;
    protected function minimumResolution(array $roi): bool
    {
        $w=abs($roi['Xmax']-$roi['Xmin']);
        $h=abs($roi['Ymax']-$roi['Ymin']);
return false;
//todo
// $w/$this->width
        if($w<$this->minRes && $h<$this->minRes) {
            return true;
        }
        return false;
    }
    
    
    
    public function cacheShapes(array $files,bool $force=false)
    {
        foreach($files as $file)
        {
            list($id)=explode("\t",$file);
            $this->cacheShape($id,$force);
        }
        
        $this->printCacheStatisticsTotal();
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
                // list($mfha,$mfhaRecords)=$this->cacheGetShapefile($file['dbf']);
                
                //mfha mfharecords
                $this->cacheShapefileContents(
                $file['dbf'],
                $cacheFilename,
                $file['shp'],
                $file['type'],
                $file['nameField']
                );
            }
            
            fclose($this->out);
            
            $this->io->note("Wrote $cacheFilename.");
        }
        
        
        // rewrite lines 3 and 4
        $lines=explode("\n",file_get_contents($cacheFilename));
        $lines[2]=json_encode($this->rois);
        $lines[3]=json_encode($this->clip);
        file_put_contents($cacheFilename,implode("\n",$lines));
        $this->io->note("Rewrote $cacheFilename.");
        
        //
        $this->printCacheStatistics();
        return false;
    }
    
    
    //
    protected function cacheShapefileContents(
    string $dbfFilename,
    string $cacheFilename,
    string $shpFilename,
    string $type, //A M W E
    string $namefield="FULLNAME"
    )
    {
        $dbfRecords = DBase::fromFile($dbfFilename);
        
        foreach ($dbfRecords as $record)
        {
            $fields=array_keys($record->getArrayCopy());
            break;
    }
    
    $record_numbers = count($dbfRecords);
    
    //
    $size=filesize($shpFilename);
    //$this->io->note("SHP $shpFilename ($size bytes)");
    
    
    $shpHandle = fopen($shpFilename, "rb");
    if($shpHandle !== FALSE)
    {
        $this->cacheStats['files']++;
        $binarydata = fread($shpHandle, 100);
        
        //main field header
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
        if($this->minimumResolution($this->roi)) {
            $this->cacheStats['files_minres_culled']++;
        }
        else
        {
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
                fputs($this->out,"[placeholding for ROIs]\r\n");
                    
                //line 3 (initial values)
                $this->clip=$this->roi;
                $this->clip=$this->computeRegionMids($this->clip);
                //  fputs($this->out,$this->clip['Xmin'].','.$this->clip['Ymin'].','.$this->clip['Xmax'].','.$this->clip['Ymax'].','.$this->clip['Xmid'].','.$this->clip['Ymid']);
                fputs($this->out,"[placeholding for Clip]");
                }
            
            //
            $this->setShapePoly();
            
            //
            $count=0;
            $pos=ftell($shpHandle);
            while(!feof($shpHandle) && ($pos+8)<$size)
            {
                //
                $row = $dbfRecords[$count];
                
                $text=$row[$namefield];
                
                //
                $binarydata = fread($shpHandle, 8);
                $pos=ftell($shpHandle);
                $rh = unpack("NRecordNumber/NContentLength",$binarydata);
                $this->cacheStats['records']++;
                
                //
                $binarydata = fread($shpHandle, 4);
                $rc = unpack("IShapeType",$binarydata);
                
                //      if(is_road($text))
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
            
        }
        
        fclose($shpHandle);
    }
    //  $this->clip=$this->computeRegionMids($this->clip);
}


// *****************************************************************************
protected function cacheShapefileTypePoint($handle,string $type,int $length=0,string $text='')
{ //16
    $binarydata = fread($handle, 16);
    $point = unpack("dX/dY",$binarydata);
    
    $this->cacheStats['points']++;
    //if(in_roi($point))
    //  {
    //  if(VERBOSE>=3)
    //   $this->io->note("(".ftell($handle).") point: x=".$point['X'].",\ty=".$point['Y']);
    $this->cacheOutPoint($point['X'],$point['Y'],$type,$text);
    // }
}


// *****************************************************************************
protected function cacheShapefileTypePolyline($handle,string $type,int $length=0,string $text='')
{
    $binarydata = fread($handle, 40);
    $h = unpack("dXmin/dYmin/dXmax/dYmax/INumParts/INumPoints/",$binarydata);
    // if(VERBOSE>=2)
    //{
    // $this->io->note('polyline: Xmin='.$h['Xmin'].',Ymin='.$h['Ymin'].' Xmax='.$h['Xmax'].',Ymax='.$h['Ymax'].'NumParts='.$h['NumParts'].' NumPoints='.$h['NumPoints']);
    //  if($h['NumPoints']<10) $this->io->note('###L');
    // }
    
    //printMFHAResolution($h);
    if($this->minimumResolution($h))
    {
        $this->cacheStats['records_minres_culled']++;
        return;
    }
    
    $pos=ftell($handle);
    //$this->io->note("pos=$pos");
    $numparts=$h['NumParts'];
    $numpoints=$h['NumPoints'];
    //$this->io->note("numparts $numparts numpoints=$numpoints");
    
    //
    $offset=[];
    $part=0;
    while(($part+1)<=$numparts)
    {
        $binarydata = fread($handle, 4);
        if(feof($handle)) break;
    
    $d = unpack("VStart",$binarydata);
    
    //    if(VERBOSE>=2)
    //{
    //    $hs = unpack("H4hex",$binarydata);
    //$this->io->note(" > part=$part < $numparts vstart ".$d['Start']." : parts[$part] offset: ".$offset[$part]." 0x".$hs['hex']);
    // }
    
    $offset[$part]=$d['Start'];
    $part++;
}

//
$pointsperpart=[];
if($numparts==1) {
    $pointsperpart[0]=$numpoints;
} else
{
    $part=0;
    $points=0;
    foreach($offset as $p=>$o)
    {
        if(($part+1)<$numparts)
        { $count=$offset[$part+1]-$offset[$part]; }
        else
        { $count=$numpoints-$points; }
        $points+=$count;
        $pointsperpart[$part]=$count;
        //$this->io->note(" < offset[$part] count: $count offset:".$offset[$part]." points:".$pointsperpart[$part]);
        $part++;
    }
}

//
$part=0;
foreach($offset as $key=>$startpoint)
{
    $partpoints=$pointsperpart[$part];
    
    $pointoffset=$pos+($numparts*4)+($startpoint*16);
    fseek($handle,$pointoffset);
    //if(VERBOSE>=2)
    //$this->io->note(" >> parts[$part] : startpoint: $startpoint key: $key offset: $pointoffset ($partpoints part points)");
    
    $points=0;
    $first['X']=0;
    $first['Y']=0;
    do
    {
        $binarydata = fread($handle, 16);
        if(feof($handle)) { break; }
    
    $point = unpack("dX/dY",$binarydata);
    $this->cacheStats['points']++;
    
    if($points==0)
    {
        $first['X']=$point['X']; $first['Y']=$point['Y'];
        $this->cacheOutPolylineStart($point['X'],$point['Y'],$type,$text);
    }
    else
    {
        $this->cacheOutPolyline($point['X'],$point['Y']);
    }
    
    //if(VERBOSE>=2)
    ////////// $this->io->note("(".ftell($handle).") [".$points."] polyline: point: X=".$point['X'].",\tY=".$point['Y']);
    
    $points++;
} while(
$points<$numpoints &&
$points<$partpoints
);

$part++;
if($part>$numparts) break;
}
}



// *****************************************************************************
protected function cacheShapefileTypePolygon($handle,string $type,int $length=0,string $text='')
{
    $binarydata = fread($handle, 40);
    $h = unpack("dXmin/dYmin/dXmax/dYmax/INumParts/INumPoints/",$binarydata);
    
    //if(VERBOSE>=2)
    //{
    //$this->io->note('('.ftell($handle).') polygon: Xmin='.$h['Xmin'].',Ymin='.$h['Ymin'].' Xmax='.$h['Xmax'].',Ymax='.$h['Ymax'].'NumParts='.$h['NumParts'].' NumPoints='.$h['NumPoints'].'');
    //if($h['NumPoints']<10) $this->io->note('###P');
    //}
    
    //printMFHAResolution($h);
    if($this->minimumResolution($h))
    {
        $this->cacheStats['records_minres_culled']++;
        return;
    }
    
    $pos=ftell($handle);
    //$this->io->note("pos=$pos");
    $numparts=$h['NumParts'];
    $numpoints=$h['NumPoints'];
    //$this->io->note("numparts $numparts numpoints=$numpoints");
    
    //
    $offset=[];
    $part=0;
    while($part<$numparts)
    {
        $binarydata = fread($handle, 4);
        $d = unpack("VStart",$binarydata);
        $offset[$part]=$d['Start'];
        //if(VERBOSE>=2)
        //{
        // $hs = unpack("H4hex",$binarydata);
        // $this->io->note(" > parts[$part] offset: ".$offset[$part]." 0x".$hs['hex']."");
        //}
        $part++;
    }
    
    //$this->io->note("offset="); print_r($offset);
    //
    $pointsperpart=[];
    if($numparts==1) {
        $pointsperpart[0]=$numpoints;
    }
    else
    {
        $part=0;
        $points=0;
        foreach($offset as $p=>$o)
        {
            if(($part+1)<$numparts)
            { $count=$offset[$part+1]-$offset[$part]; }
            else
            { $count=$numpoints-$points; }
            $points+=$count;
            $pointsperpart[$part]=$count;
            //$this->io->note(" < offset[$part] count: $count offset:".$offset[$part]." points:".$pointsperpart[$part]);
            $part++;
        }
    }
    
    //
    $part=0;
    foreach($offset as $key=>$startpoint)
    {
        $partpoints=$pointsperpart[$part];
        
        $pointoffset=$pos+($numparts*4)+($startpoint*16);
        fseek($handle,$pointoffset);
        //if(VERBOSE>=2)
        //$this->io->note(" >> parts[$part] : startpoint: $startpoint key: $key offset: $pointoffset ($partpoints part points)");
        
        $points=0;
        $first['X']=0;
        $first['Y']=0;
        do
        {
            $binarydata = fread($handle, 16);
            if(feof($handle)) break;
        
        $point = unpack("dX/dY",$binarydata);
        $this->cacheStats['points']++;
        
        if($points==0)
        {
            $first['X']=$point['X']; $first['Y']=$point['Y'];
            $this->cacheOutPolygonStart($point['X'],$point['Y'],$type,$text);
        }
        else
            $this->cacheOutPolygon($point['X'],$point['Y']);
        
        //if(VERBOSE>=3)
        ////////////     $this->io->note("(".ftell($handle).") [$points] polygon: point: X=".$point['X'].",\tY=".$point['Y']);
        
        $points++;
    } while(
    $points<$numpoints &&
    $points<$partpoints
    );
    
    /*
    if(VERBOSE>=2)
    { $this->io->note("part=$part<numparts=$numparts and point=$point_index<numpoints=$numpoints and count=$count!=next_part_index=$next_part_index  and count=$count<=1 part+count=".($offset[$part]+$count)); $this->io->note(); }
    */
    $part++;
    if($part>$numparts) break;
}
}


// *****************************************************************************
protected function cacheOutPoint(float $x,float $y,string $type=' ',string $text='')
{
    $x=$this->clipPrecision($x);
    $y=$this->clipPrecision($y);
    
    fputs($this->out,"\r\n".$this->correctTextAbbreviations($text));
    fputs($this->out,"\r\n*$type".$x.",".$y);
    
    $this->cacheStats['points out']++;
    $this->cacheStats['shapes']++;
    
    $this->updateCacheClipBounds($x,$y);
}

protected function cacheOutPolylineStart(float $x,float $y,string $type=' ',string $text='')
{
    $x=$this->clipPrecision($x);
    $y=$this->clipPrecision($y);
    
    fputs($this->out,"\r\n".$this->correctTextAbbreviations($text));
    fputs($this->out,"\r\nL$type".$x.",".$y);
    
    $this->cacheStats['points out']++;
    $this->cacheStats['shapes']++;
    
    $this->updateCacheClipBounds($x,$y);
    $this->setShapePoly($x,$y,'P');
}

protected function cacheOutPolyline(float $x,float $y)
{
    $x=$this->clipPrecision($x);
    $y=$this->clipPrecision($y);
    
    if(isset($this->opt['nodups']) && $this->lastshape=='L' && $this->lastlat==$x && $this->lastlon==$y)
    {
        $this->cacheStats['points_roi_culled']++;
        return;
    }
    
    fputs($this->out,",".$x.",".$y);
    
    $this->cacheStats['points out']++;
    
    $this->updateCacheClipBounds($x,$y);
    $this->setShapePoly($x,$y,'L');
}

protected function cacheOutPolygonStart(float $x, float $y,string $type=' ',string $text='')
{
    $x=$this->clipPrecision($x);
    $y=$this->clipPrecision($y);
    
    fputs($this->out,"\r\n".$this->correctTextAbbreviations($text));
    fputs($this->out,"\r\nP$type".$x.",".$y);
    
    $this->cacheStats['points out']++;
    $this->cacheStats['shapes']++;
    
    $this->updateCacheClipBounds($x,$y);
    
    $this->setShapePoly($x,$y,'P');
}

protected function cacheOutPolygon(float $x,float $y)
{
    $x=$this->clipPrecision($x);
    $y=$this->clipPrecision($y);
    
    if(isset($this->opt['nodups']) && $this->lastshape=='P' && $this->lastlat==$x && $this->lastlon==$y)
    {
        $this->cacheStats['points_roi_culled']++;
        return;
    }
    
    fputs($this->out,",".$x.",".$y);
    $this->cacheStats['points out']++;
    
    $this->updateCacheClipBounds($x,$y);
    $this->setShapePoly($x,$y,'P');
}


// *****************************************************************************


/**
*/
public function cacheStatesList(string $filter="") {
    
    $this->output_filename="states_list.txt";
    
    $finder = new Finder();
    
    $records=[];
    
    // echo "rootDataDir=$this->rootDataDir\n";
    // get list of county folders
    //////  if($filter=="") {
    $finder->directories()->depth(" == 0")->path("/^[\d]{2,2}_(.*)/")->in($this->rootDataDir."/tiger{$this->yearfp}/");
    // echo $this->rootDataDir."/tiger{$this->yearfp}/"."\n";
    //}
    // else {
    ////     $finder->directories()->depth("== 0")->path("/^[\d]{2}_(.*)/")->in($this->cacheDir);
    //}
    foreach ($finder as $dir) {
        $state_folder=$dir->getRelativePathname();
        
        //  echo "state_folder=$state_folder\n";
        
        $a=explode('_',$state_folder);
        $statefp=$a[0];
        $state=UCWords(strtolower($a[1]));
        
        // fe_2007_47_county.dbf
        $records[]=$statefp."\t".$state_folder."\t".$state;
    }
    
    file_put_contents(
    $this->outputDir."/".$this->output_filename,
    implode("\n",$records)
    );
    
    //
    return $records;
}




/**
*/
public function cacheCountiesList(string $filter="") {
    
    $this->output_filename="counties_list.txt";
    
    $finder = new Finder();
    
    $records=[];
    
    // get list of county folders
    //////  if($filter=="") {
    $finder->directories()->depth("== 0")->path("/^[\d]{2,2}_(.*)/")->in($this->dataDir);
    //}
    // else {
    ////     $finder->directories()->depth("== 0")->path("/^[\d]{2}_(.*)/")->in($this->cacheDir);
    //}
    foreach ($finder as $dir) {
        $state_folder=$dir->getRelativePathname();
        
        $a=explode('_',$state_folder);
        $statefp=$a[0];
        
        // fe_2007_47_county.dbf
        $dbf_filename=$this->dataDir."/{$state_folder}/fe_{$this->yearfp}_{$statefp}_county.dbf";
        
        // echo "dbf_filename=$dbf_filename\n";
        
        $dbf = DBase::fromFile($dbf_filename);
        
        foreach ($dbf as $record) {
            //      $records[]=$record->getArrayCopy();
            $records[]=$record['CNTYIDFP']."\t".$record['NAME']."\t".$record['NAMELSAD'];
        }
    }
    
    file_put_contents(
    $this->outputDir."/".$this->output_filename,
    implode("\n",$records)
    );
    
    //
    return $records;
}




private $county_subtypes=[
['prefix'=>'arealm','type'=>'A','nameField'=>'FULLNAME'],
['prefix'=>'pointlm','type'=>'M','nameField'=>'FULLNAME'],
['prefix'=>'areawater','type'=>'W','nameField'=>'FULLNAME'],
['prefix'=>'edges','type'=>'E','nameField'=>'FULLNAME']
];

protected function getFilesForId(string $id): array
{
    $files=[];
    
    $finder = new Finder();
    
    switch(strlen($id))
    {
        case 0:
        $file="/fe_{$this->yearfp}_us_state";
        foreach($this->county_subtypes as $subtype)
        {
            $subtype['nameField']='NAMELSAD';
            $prefix=$subtype['prefix'];
            $files[]= [
            'type'=>$subtype['type'],
            'prefix'=>$subtype['prefix'],
            'nameField'=>$subtype['nameField'],
            'shp'=>$this->getDataPath()."/".$file.".shp",
            'shx'=>$this->getDataPath()."/".$file.".shx",
            'dbf'=>$this->getDataPath()."/".$file.".dbf",
            'prj'=>$this->getDataPath()."/".$file.".prj",
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
    foreach($this->county_subtypes as $subtype)
    {
        $subtype['nameField']='NAMELSAD';
        
        $prefix=$subtype['prefix'];
        $files[]= [
        'type'=>$subtype['type'],
        'prefix'=>$subtype['prefix'],
        'nameField'=>$subtype['nameField'],
        'shp'=>$this->getDataPath()."/".$file.".shp",
        'shx'=>$this->getDataPath()."/".$file.".shx",
        'dbf'=>$this->getDataPath()."/".$file.".dbf",
        'prj'=>$this->getDataPath()."/".$file.".prj",
        ];
    }
    return $files;
    break;

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

foreach($this->county_subtypes as $subtype)
{
    $prefix=$subtype['prefix'];
    $files[]= [
    'type'=>$subtype['type'],
    'prefix'=>$subtype['prefix'],
    'nameField'=>$subtype['nameField'],
    'shp'=>$this->getDataPath()."/".$file.$prefix.".shp",
    'shx'=>$this->getDataPath()."/".$file.$prefix.".shx",
    'dbf'=>$this->getDataPath()."/".$file.$prefix.".dbf",
    'prj'=>$this->getDataPath()."/".$file.$prefix.".prj",
    ];
}
return $files;
break;

default:
    return $files;
}

return $files;
}

// *****************************************************************************
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



}