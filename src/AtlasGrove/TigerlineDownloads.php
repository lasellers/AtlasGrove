<?php
namespace AtlasGrove;
//declare(strict_types=1);

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

use org\majkel\dbase\Table AS DBase;

use AtlasGrove\Tigerline as Tigerline;

// php bin/console atlasgrove:downloads

class TigerlineDownloads extends Tigerline
{
    
    public function __construct($container,SymfonyStyle $io=null)
    {
        parent::__construct($container,$io);
    }
    
    
    /**
    * e.g. cache("47095") restrict to specific county
    */
    public function getCacheYears(string $filter="",bool $save=false,string $output_filename="downloads-years.txt"): array
    {
        $cachedItems = $this->container->get('cache.app')->getItem('cache.years');
        
        if ($cachedItems->isHit()) {
            $files=$cachedItems->get();
        } else {
            $files=[];
            
            $finder = new Finder();
            $finder->directories()->depth("== 0")->path("/TIGER[\d]{4}/")->in($this->getRootDataPath());
            foreach ($finder as $file) {
                $files[]=$file->getRelativePathname();
            }
            
            if(count($files)>0) {
                sort($files);
                
                $cachedItems->expiresAfter($this->getCacheTTL());
                $cachedItems->set($files);
                $this->container->get('cache.app')->save($cachedItems);
            }
        }
        
        if($save)
        {
            file_put_contents($this->getDataCachePath()."/".$output_filename,implode("\n",$files));
        }
        
        return  [
        'records' => $files,
        'file' => $this->getDataCachePath()."/".$output_filename
        ];
    }
    
    /**
    * e.g. cache("47095") restrict to specific county
    */
    public function getCacheStates(string $filter="",bool $save=false,string $output_filename="downloads-states.txt"): array
    {
        $cachedItems = $this->container->get('cache.app')->getItem('cache.states');
        
        if ($cachedItems->isHit()) {
            $files=$cachedItems->get();
        } else {
            //
            $files=[];
            
            $finder = new Finder();
            
            $finder->directories()->depth("== 0")->path("/^[\d]{2}_(.*)/")->in($this->getDataPath());
            foreach ($finder as $file) {
                $files[]=$file->getRelativePathname();
            }
            
            if(count($files)>0) {
                
                sort($files);
                
                $cachedItems->expiresAfter($this->getCacheTTL());
                $cachedItems->set($files);
                $this->container->get('cache.app')->save($cachedItems);
            }
        }
        
        if($save)
        {
            file_put_contents( $this->getDataCachePath()."/".$output_filename, implode("\n",$files) );
        }
        
        return  [
        'records' => $files,
        'file' => $this->getDataCachePath()."/".$output_filename
        ];
    }
    
    
    
    /**
    * e.g. cache("47095") restrict to specific county
    */
    public function getCacheCounties(string $filter="",bool $save=false,string $output_filename="downloads-counties.txt"): array
    {
        $cachedItems = $this->container->get('cache.app')->getItem('cache.counties');
        
        if ($cachedItems->isHit()) {
            $dirs=$cachedItems->get();
        } else {
            $dirs=[];
            
            $finder = new Finder();
            
            //
            $dirs=[];
            if($filter=="") {
                $finder->directories()->depth("== 1")->path("/^[\d]{2}_(.*)/")->in($this->getDataPath());
            }
            else {
                $finder->directories()->depth("== 1")->path("/^[\d]{2}_(.*)/")->path($filter)->in($this->getDataPath());
            }
            foreach ($finder as $file) {
                $dirs[]=$file->getRelativePathname();
            }
            
            if(count($dirs)>0) {
                sort($dirs);
                
                $cachedItems->expiresAfter($this->getCacheTTL());
                $cachedItems->set($dirs);
                $this->container->get('cache.app')->save($cachedItems);
            }
        }
        
        
        if($save)
        {
            file_put_contents(
            $this->getDataCachePath()."/".$output_filename,
            implode("\n",$dirs)
            );
        }
        
        return  [
        'records' => $dirs,
        'file' => $this->getDataCachePath()."/".$output_filename
        ];
        
    }
    
    
    /**
    * e.g. cache("47095") restrict to specific county
    */
    public function getCacheFiles(string $filter="",bool $save=false,string $output_filename="downloads-files.txt"): array
    {
        $cachedItems = $this->container->get('cache.app')->getItem('cache.files');
        
        if ($cachedItems->isHit()) {
            $files=$cachedItems->get();
        } else {
            $files=[];
            
            $finder = new Finder();
            
            if($filter=="") {
                $finder->files()->in($this->getDataPath());
            }
            else {
                $finder->files()->path($filter)->in($this->getDataPath());
            }
            foreach ($finder as $file) {
                $files[]=$file->getRelativePathname();
            }
            
            if(count($files)>0) {
                sort($files);
                
                $cachedItems->expiresAfter($this->getCacheTTL());
                $cachedItems->set($files);
                $this->container->get('cache.app')->save($cachedItems);
            }
        }
        
        if($save)
        {
            file_put_contents($this->getDataCachePath()."/".$output_filename, implode("\n",$files));
        }
        
        return  [
        'records' => $files,
        'file' => $this->getDataCachePath()."/".$output_filename
        ];
    }
    
    
    /**
    */
    public function getDBFList($filter="",$save=false,$output_filename="dbf.txt"): array {
        
        $finder = new Finder();
        
        //  $records=[];
        
        file_put_contents(
        $this->getDataCachePath()."/".$output_filename,
        ""
        );
        
        // get list of county folders
        //////  if($filter=="") {
        $finder->directories()->depth("== 0")->path("/^[\d]{2}_(.*)/")->in($this->getDataPath());
        //}
        // else {
        ////     $finder->directories()->depth("== 0")->path("/^[\d]{2}_(.*)/")->in($this->dataDir);
        //}
        foreach ($finder as $dir) {
            $state_folder=$dir->getRelativePathname();
            
            $a=explode('_',$state_folder);
            $statefp=$a[0];
            
            // fe_2007_47_county.dbf
            $fileprefix="fe_{$this->yearfp}_{$statefp}_";
            $statefolder=$this->getDataPath()."/{$state_folder}";
            
            $dbf_filename=$this->getDataPath()."/{$state_folder}/fe_{$this->yearfp}_{$statefp}_county.dbf";
            
            $dbf = DBase::fromFile($dbf_filename);
            
            foreach ($dbf as $record) {
                // $records[]=$record->getArrayCopy();
                // $records[]=[$record['CNTYIDFP'],$record['NAME'],$record['NAMELSAD']];
                
                foreach($this->tigerline_subtypes as $subtype)
                {
                    $subtype=$subtype['prefix'];
                    // 47003_Bedford
                    // fe_2007_47003_arealm.dbf
                    $cntyidfp=$record['CNTYIDFP'];
                    $name=$record['NAME'];
                    
                    $dbf2_filename=$this->getDataPath()."/{$state_folder}/{$cntyidfp}_{$name}/fe_{$this->yearfp}_{$cntyidfp}_{$subtype}.dbf";
                    
                    if(file_exists($dbf2_filename))
                    {
                        try {
                            $dbf2 = DBase::fromFile($dbf2_filename);
                            
                            $records=[];
                            foreach ($dbf2 as $record2) {
                                $records[]=($record2->getArrayCopy());
                                
                            }
                            file_put_contents(
                            $this->getDataCachePath()."/".$output_filename,
                            json_encode($records),
                            FILE_APPEND
                            );
                        }
                        catch (RuntimeException $e)
                        {
                            $this->io->error($e->getMessage());
                            //
                        }
                    }
                    
                }
            }
            
        }
        
        //
        if($save)
        {
        }
        
        return  [
        'records' => $records,
        'file' => "{$this->getDataCachePath()}/{$output_filename}"
        ];
    }
    
    
    /**
    */
    public function getSHXList($filter="",$save=false,$output_filename="shx.txt") {
        
        $finder = new Finder();
        
        $records=[];
        
        file_put_contents(
        $this->getDataCachePath()."/".$output_filename,
        ""
        );
        
        // get list of county folders
        //////  if($filter=="") {
        $finder->directories()->depth("== 0")->path("/^[\d]{2,2}_(.*)/")->in($this->getDataPath());
        //}
        // else {
        ////     $finder->directories()->depth("== 0")->path("/^[\d]{2}_(.*)/")->in($this->dataDir);
        //}
        foreach ($finder as $dir) {
            $state_folder=$dir->getRelativePathname();
            echo "state_folder=$state_folder\n";
            
            $a=explode('_',$state_folder);
            $statefp=$a[0];
            echo "statefp=$statefp\n";
            
            // fe_2007_47_county.dbf
            $fileprefix="fe_{$this->yearfp}_{$statefp}_";
            $statefolder=$this->getDataPath()."/{$state_folder}";
            
            $dbf_filename=$this->getDataPath()."/{$state_folder}/fe_{$this->yearfp}_{$statefp}_county.dbf";
            
            $dbf = DBase::fromFile($dbf_filename);
            
            foreach ($dbf as $record) {
                // $records[]=$record->getArrayCopy();
                // $records[]=[$record['CNTYIDFP'],$record['NAME'],$record['NAMELSAD']];
                
                foreach($this->tigerline_subtypes as $subtype)
                {
                    $subtype=$subtype['prefix'];
                    
                    // 47003_Bedford
                    // fe_2007_47003_arealm.dbf
                    $cntyidfp=$record['CNTYIDFP'];
                    $name=$record['NAME'];
                    
                    $shx_filename=$this->getDataPath()."/{$state_folder}/{$cntyidfp}_{$name}/fe_{$this->yearfp}_{$cntyidfp}_{$subtype}.shx";
                    
                    $shx=$this->getShx($shx_filename);
                    //  list($header,$index)=$tigerline->get_shx($shx_filename);
                    
                    file_put_contents(
                    $this->getDataCachePath()."/".$output_filename,
                    json_encode($shx),
                    FILE_APPEND
                    );
                    
                }
            }
            
        }
        
        //
        if($save)
        {
        }
        
        return  [
        'records' => $shx,
        'file' => "{$this->getDataCachePath()}/{$output_filename}"
        ];
    }
    
    
    /**
    */
    public function getSHPList($filter="",$save=false,$output_filename="shp.txt") {
        
        $finder = new Finder();
        
        //  $records=[];
        
        file_put_contents(
        $this->getDataCachePath()."/".$output_filename,
        ""
        );
        file_put_contents(
        $this->getDataCachePath()."/".$output_filename.".html",
        ""
        );
        
        // get list of county folders
        //////  if($filter=="") {
        $finder->directories()->depth("== 0")->path("/^[\d]{2}_(.*)/")->in($this->getDataPath());
        //}
        // else {
        ////     $finder->directories()->depth("== 0")->path("/^[\d]{2}_(.*)/")->in($this->dataDir);
        //}
        foreach ($finder as $dir) {
            $state_folder=$dir->getRelativePathname();
            
            $a=explode('_',$state_folder);
            $statefp=$a[0];
            
            // fe_2007_47_county.dbf
            $fileprefix="fe_{$this->yearfp}_{$statefp}_";
            $statefolder=$this->getDataPath()."/{$state_folder}";
            
            $dbf_filename=$this->getDataPath()."/{$state_folder}/fe_{$this->yearfp}_{$statefp}_county.dbf";
            
            $dbf = DBase::fromFile($dbf_filename);
            
            foreach ($dbf as $record) {
                // $records[]=$record->getArrayCopy();
                // $records[]=[$record['CNTYIDFP'],$record['NAME'],$record['NAMELSAD']];
                
                $records=[];
                foreach($this->tigerline_subtypes as $subtype)
                {
                    $subtype=$subtype['prefix'];
                    
                    // 47003_Bedford
                    // fe_2007_47003_arealm.dbf
                    $cntyidfp=$record['CNTYIDFP'];
                    $name=$record['NAME'];
                    
                    $shp_filename=$this->getDataPath()."/{$state_folder}/{$cntyidfp}_{$name}/fe_{$this->yearfp}_{$cntyidfp}_{$subtype}.shp";
                    
                    $shp=$this->get_shapefile($shp_filename);
                    //  list($header,$index)=$tigerline->get_shx($shx_filename);
                    // $records[]=($record2->getArrayCopy());
                    $records[]=$shp;
                    
                    file_put_contents(
                    $this->getDataCachePath()."/".$output_filename.".html",
                    $this->print_shps($shp),
                    FILE_APPEND
                    );
                    
                }
                
                file_put_contents(
                $this->getDataCachePath()."/".$output_filename,
                json_encode($records),
                FILE_APPEND
                );
            }
            
        }
        
        //
        if($save)
        {
        }
        
        return  [
        'records' => $records,
        'file' => "{$this->getDataCachePath()}/{$output_filename}"
        ];
    }
    
    
    // *****************************************************************************
    public function print_shps($shps)
    {
        $s="<table>";
        foreach($shps as $shp)
        {
            $s.="<tr>
            <th>
            {$shp['key']}
            </th>
            <td>
            {$shp['value']} {$shp['type']}
            </td>
            </tr>";
        }
        $s.="</table>";
        return $s;
    }
    
    
    /**
    todo
    */
    public function getRoadsList($filter="",$save=false,$output_filename="roads.txt") {
        
        $finder = new Finder();
        
        //  $records=[];
        
        // get list of county folders
        //////  if($filter=="") {
        $finder->directories()->depth("== 0")->path("/^[\d]{2}_(.*)/")->in($this->getDataPath());
        //}
        // else {
        ////     $finder->directories()->depth("== 0")->path("/^[\d]{2}_(.*)/")->in($this->dataDir);
        //}
        foreach ($finder as $dir) {
            $state_folder=$dir->getRelativePathname();
            
            $a=explode('_',$state_folder);
            $statefp=$a[0];
            
            // fe_2007_47_county.dbf
            $fileprefix="fe_{$this->yearfp}_{$statefp}_";
            $statefolder=$this->getDataPath()."/{$state_folder}";
            
            $dbf_filename=$this->getDataCachePath()."/{$state_folder}/{$this->yearfp}_{$statefp}_county.txt";
            
            $dbf = DBase::fromFile($dbf_filename);
            
            foreach ($dbf as $record) {
                // $records[]=$record->getArrayCopy();
                // $records[]=[$record['CNTYIDFP'],$record['NAME'],$record['NAMELSAD']];
                
                $records=[];
                foreach($this->tigerline_subtypes as $subtype)
                {
                    $subtype=$subtype['prefix'];
                    
                    // 47003_Bedford
                    // fe_2007_47003_arealm.dbf
                    $cntyidfp=$record['CNTYIDFP'];
                    $name=$record['NAME'];
                    
                    $shp_filename=$this->getDataPath()."/{$state_folder}/{$cntyidfp}_{$name}/fe_{$this->yearfp}_{$cntyidfp}_{$subtype}.shp";
                    
                    $shp=$this->get_shapefile($shp_filename);
                    //  list($header,$index)=$tigerline->get_shx($shx_filename);
                    // $records[]=($record2->getArrayCopy());
                    $records[]=$shp;
                    
                    file_put_contents(
                    $this->getDataCachePath()."/$output_filename.html",
                    $this->print_shps($shp),
                    FILE_APPEND
                    );
                    
                }
                
                file_put_contents( $this->getDataCachePath()."/".$output_filename,json_encode($records),FILE_APPEND);
            }
            
        }
        
        //
        if($save)
        {
        }
        
        return  [
        'records' => $records,
        'file' => "{$this->getDataCachePath()}/{$output_filename}"
        ];
    }
    
    
    
    /**
    */
    public function getMapList(string $type="all",$save=false): array
    {
        $subfolder="";
        switch($type)
        {
            case 'states':
                $key="maps.states";
            $filter="/^[\d]{2}\.png$/";
            break;
        
        case 'counties':
            $key="maps.counties";
        $filter="/^[\d]{5}\.png$/";
        break;
    
    case 'roi':
        $key="maps.roi";
        $filter="/^.+,.+,.+,.+\.png$/";
        break;
    
    case 'steps':
        $subfolder="/steps";
        $key="maps.steps";
        $filter="/^.+\.png$/";
        break;
    
    case 'road':
        $key="maps.road";
    $filter="/^[\d]{5}\..*\.road\.png$/";
    break;

default:
    $key="maps";
    $filter="/.+.png/";
}
$cache_key="cache.{$key}";
$output_filename="{$key}.txt";
$output_filepath=$this->getDataCachePath()."/".$output_filename;

$cachedItems = $this->container->get('cache.app')->getItem($cache_key);

if ($cachedItems->isHit()) {
    $files=$cachedItems->get();
} else {
    //
    $files=[];
    
    $finder = new Finder();
    //
    $finder->files()->depth("== 0")->path($filter)->in($this->getMapPath().$subfolder);
    foreach ($finder as $file) {
        $files[]=$file->getRelativePathname();
    }
    
    if(count($files)>0) {
        $cachedItems->expiresAfter($this->getCacheTTL());
        $cachedItems->set($files);
        $this->container->get('cache.app')->save($cachedItems);
    }
}

sort($files);

if($save)
{
    file_put_contents($output_filepath,implode("\n",$files));
}

//$files=array_map(function ($file) {
//},$files);

return  [
'records' => $files,
'file' => $output_filepath
];
}

}