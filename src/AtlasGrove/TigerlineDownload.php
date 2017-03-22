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

use FtpClient\FtpClient;

class TigerlineDownload extends Tigerline
{
    /*
    use Touki\FTP\FTPFactory;
    
    $factory = new FTPFactory;
    $ftp = $factory->build($connection);
    */
    /*
    $connection = new Connection('host', 'username', 'password', $port = 21, $timeout = 90, $passive = false);
    $connection = new AnonymousConnection('host', $port = 21, $timeout = 90, $passive = false);
    $connection = new SSLConnection('host', 'username', 'password', $port = 21, $timeout = 90, $passive = false);
    
    $connection->open();
    */
    
    //$ftp = new FTP();
    
    /*
    $ftp->fileExists(new File('/foo'));
    $ftp->fileExists(new File('/non/existant/file'))
    $ftp->directoryExists(new Directory('/folder'))
    $ftp->directoryExists(new Directory('/bar'))
    
    
    $list = $ftp->findFilesystems(new Directory("/"));
    var_dump($list);
    
    $file  = $ftp->findFileByName('file1.txt');
    var_dump($file);
    
    */
    
    private $host="ftp2.census.gov";
    private $user="anonymous";
    private $password="guest";
    
    private $dir='/geo/tiger/TIGER2007FE/';
    
    // Atlasgrove\tigerlineDownload
    public function download(string $year="2007", string $state="47") {
        
        $this->io->note("Connecting to $this->host, $this->user, $this->password...");
        
        $ftp = new \FtpClient\FtpClient();
        $ftp->connect($this->host);
        $ftp->login($this->user, $this->password);
        
        $this->io->note("Connected to $this->host, $this->user, $this->password");
        
        $this->io->note("Getting directory...");
        $ftp->chdir("/");
        
        $total = $ftp->count();
        var_dump($total);
        
        $list = $ftp->ScanDir();
        var_dump($list);
        
        $this->io->note("Changing to $this->dir");
        $ftp->chdir($this->dir);
        //   $wrapper->cdup();
        //   $wrapper->get($this->getDataPath().'/foofile.txt', '/folder/foofile.txt');
        
        $this->io->note("Getting directory...");
        
        $total = $ftp->count();
        var_dump($total);
        
        $list = $ftp->ScanDir();
        var_dump($list);
        
        
        /*
        get year folder
        
        $states =
        
        get fe_2007_47_county.zip
        unzip here
        
        $counties= get all /geo/tiger/TIGER2007FE/36_NEW_YORK/36001_Albany/
        
        foreach($counties as $county)
        {
        Index of /geo/tiger/TIGER2007FE/36_NEW_YORK/36001_Albany/
        foreach($tigerline_subtypes as $subtype)
        {
        $zip=$ftp->get("fe_2007_36001_arealm.zip","arealm.zip");
        unzip($zip...) //here
        
        delete($zip);
        }
        }
        
        */
        $ftp->close();
        
    }
    
}
// https://www.census.gov/cgi-bin/geo/shapefiles/state-files?state=47
// https://www.census.gov/cgi-bin/geo/shapefiles/county-files?county=47001