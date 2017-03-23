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

//use FtpClient\FtpClient;

use ZipArchive;

class TigerlineDownload extends Tigerline
{
    private $host="148.129.75.35"; //ftp2.census.gov";
    private $user="anonymous";
    private $password="guest";
    
    private $dir='/geo/tiger';
    
    private $conn_id;
    
    private $years;
    private $year;
    private $state;
    
    
    public function __construct($container,SymfonyStyle $io)
    {
        parent::__construct($container,$io);
    }
    
    // Atlasgrove\tigerlineDownload
    public function download(string $year="2007", string $state="47") {
        
        $this->year=preg_replace('/^0-9/',"",$year);
        $this->state=str_pad(preg_replace('/^0-9/',"",$state), 2, "0", STR_PAD_LEFT);
        
        $this->io->note("Year: $this->year");
        $this->io->note("State: $this->state");
        
        $this->io->note("Connecting to $this->host, $this->user, $this->password...");
        
        $this->conn_id = ftp_connect($this->host);
        if($this->conn_id === FALSE)
        {
            $this->io->error("No connection $this->host.");
            return;
        }
        
        $login_result = ftp_login($this->conn_id, $this->user, $this->password);
        
        $this->io->note("Passive mode...");
        
        ftp_pasv($this->conn_id, true);
        
        $this->io->note("Connected to $this->host, $this->user, $this->password");
        
        $dir=$this->dir;
        $localFolder=$this->getRootDataPath();
        $this->checkLocalFolder($localFolder);
        
        $this->changeFolder($dir);
        
        
        
        // years
        $this->io->note("Getting years...");
        $nlist = ftp_nlist($this->conn_id, ".");
        
        $this->years = array_filter($nlist, function ($haystack) {
            return !preg_match("/(.+)\.pdf$/",$haystack);
        });
        
        $this->io->table(
        ['Year'],
        $this->arrayToNameValue($this->years)
        );
        
        
        // year
        $matches = array_filter($this->years, function ($haystack) {
            return preg_match("/(.+)".$this->year."/",$haystack);
        });
        $yearFolder=each($matches)['value'];
        $this->io->note("yearfolder=".$yearFolder."");
        
        $dir=$this->dir."/".$yearFolder;
        $this->changeFolder($dir);
        
        $localFolder=$this->getRootDataPath()."/".$yearFolder;
        $this->checkLocalFolder($localFolder);
        
        //
        list($this->states,$usdownloads)=$this->getList("States","/^(\d){2}_[A-Z\-_]+$/","/(.+)_us_state\.zip$/");
        /*
        $this->io->note("Getting states...");
        $states_nlist = ftp_nlist($this->conn_id, ".");
        
        $this->states = array_filter($states_nlist, function ($haystack) {
        return preg_match("/^(\d){2}_[A-Z\-_]+$/",$haystack);
        //            return !preg_match("/(.+)\.zip$/",$haystack);
        });
        $usdownloads = array_filter($states_nlist, function ($haystack) {
        return preg_match("/(.+)_us_state\.zip$/",$haystack);
        });
        
        $this->io->table(
        ['States'],
        $this->arrayToNameValue($this->states)
        );
        */
        $localFolder=$this->getRootDataPath()."/".$yearFolder;
        $this->checkLocalFolder($localFolder);
        
        $this->downloadAndUnzipStubData($usdownloads,$localFolder,$name="US");
        
        foreach($usdownloads as $remoteFile)
        {
            // [ [1] => 02_ALASKA ]
            $statefps = array_filter($this->states, function ($haystack) {
                return substr($haystack,0,3)== $this->state."_";
            });
            $stateFolder=each($statefps)['value'];
            $this->io->note("stateFolder=$stateFolder");
            if(strlen($stateFolder)>0)
            {
                $dir=$this->dir."/".$yearFolder."/".$stateFolder;
                $this->changeFolder($dir);
                
                //
                list($this->counties,$countydownloads)=$this->getList("Counties","/(.+)\.zip$/","/(.+)_county\.zip$/",true);
                /*
                $this->io->note("Getting counties...");
                $counties_nlist = ftp_nlist($this->conn_id, ".");
                
                $this->counties = array_filter($counties_nlist, function ($haystack) {
                return !preg_match("/(.+)\.zip$/",$haystack);
                });
                $countydownloads = array_filter($counties_nlist, function ($haystack) {
                return preg_match("/(.+)_county\.zip$/",$haystack);
                });
                
                
                $this->io->table(
                ['Counties'],
                $this->arrayToNameValue($this->counties)
                );
                */
                
                $localFolder=$this->getRootDataPath()."/".$yearFolder."/".$stateFolder;
                $this->downloadAndUnzipStubData($countydownloads,$localFolder,$name="County");
                
                foreach($this->counties as $countyFolder)
                {
                    $localFolder=$this->getRootDataPath()."/".$yearFolder."/".$stateFolder."/".$countyFolder;
                    
                    $dir=$this->dir."/".$yearFolder."/".$stateFolder."/".$countyFolder;
                    $this->changeFolder($dir);
                    
                    $this->io->note("Getting county data...");
                    $countydata_nlist = ftp_nlist($this->conn_id, ".");
                    
                    $countydatadownloads = array_filter($countydata_nlist, function ($haystack) {
                        return preg_match("/(.+)_(arealm|areawater|edges|pointlm|featnames)\.zip$/",$haystack);
                    });
                    
                    $this->downloadAndUnzipStubData($countydatadownloads,$localFolder,$name="County Data");
                }
            }
        }
        
        //
        ftp_close($this->conn_id);
    }
    
    
    private function getList($name="Counties",$filter1,$filter2,$neg1=false) {
        //
        $this->io->note("Getting $name...");
        $nlist = ftp_nlist($this->conn_id, ".");
        
        $nlist2 = array_filter($nlist, function ($haystack) use ($filter1,$neg1) {
            return $neg1?!preg_match($filter1,$haystack):preg_match($filter1,$haystack);
        });
        
        $downloads = array_filter($nlist, function ($haystack) use ($filter2) {
            return preg_match($filter2,$haystack);
            //            return preg_match($filter2,$haystack);
        });
        
        $this->io->table(
        [$name],
        $this->arrayToNameValue($nlist2)
        );
        
        return [$nlist2,$downloads];
    }
    
    private function changeFolder($dir)
    {
        $this->io->note("* Changing directory ... $dir");
        ftp_chdir($this->conn_id,$dir);
        $this->io->note("current directory: ".ftp_pwd($this->conn_id));
    }
    
    private function downloadAndUnzipStubData($stubs,$localFolder,$name="")
    {
        $this->io->table(
        ["{$name} downloads"],
        $this->arrayToNameValue($stubs)
        );
        
        //
        $this->checkLocalFolder($localFolder);
        
        // download ...
        foreach($stubs as $remoteFile)
        {
            $localFile=$localFolder."/".$remoteFile;
            $this->io->note("* localFile=$localFile remoteFile=$remoteFile");
            $this->checkLocalFolder($localFolder);
            
            $this->downloadZip($localFile,$remoteFile);
            $this->extractZip($localFile);
            
            unlink($localFile);
        }
    }
    
    private function checkLocalFolder($localFolder)
    {
        //        echo "checkLocalFolder=$localFolder\n";
        if(!file_exists($localFolder)) {
            mkdir($localFolder);
        }
        
    }
    
    private function downloadZip($localFile,$remoteFile)
    {
        $handle = fopen($localFile, 'w');
        if($handle == FALSE) {
            return false;
        }
        
        if (ftp_fget($this->conn_id, $handle, $remoteFile, FTP_BINARY, 0)) {
            $this->io->note("successfully written to $localFile");
        } else {
            $this->io->note("There was a problem while downloading $remoteFile to $localFile");
        }
        
        fclose($handle);
        
        return true;
    }
    
    /*
    private function zipFlatten ( $zipfile, $dest='.' )
    {
    $zip = new ZipArchive;
    if ( $zip->open( $zipfile ) )
    {
    for ( $i=0; $i < $zip->numFiles; $i++ )
    {
    $entry = $zip->getNameIndex($i);
    if ( substr( $entry, -1 ) == '/' ) continue; // skip directories
    
    $fp = $zip->getStream( $entry );
    $ofp = fopen( $dest.'/'.basename($entry), 'w' );
    
    if ( ! $fp )
    throw new Exception('Unable to extract the file.');
    
    while ( ! feof( $fp ) )
    fwrite( $ofp, fread($fp, 8192) );
    
    fclose($fp);
    fclose($ofp);
    }
    
    $zip->close();
    }
    else
    return false;
    
    return $zip;
    }
    */
    
    
    private function extractZip($localFile)
    {
        $localFolder=dirname($localFile);
        
        // $this->zipFlatten($localFile);
        
        $zip = new ZipArchive;
        $res = $zip->open($localFile);
        if ($res === TRUE) {
            $zip->extractTo($localFolder);
            $zip->close();
            echo "unzipped $localFile\n";
        } else {
            echo "failed to unzip $localFile\n";
        }
        
    }
    
}