<?php

namespace AtlasGrove;

//declare(strict_types=1);

//use Symfony\Component\Console\Input\InputArgument;
//use Symfony\Component\Console\Input\InputInterface;
//use Symfony\Component\Console\Input\InputOption;
//use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use Symfony\Component\Finder\Finder;
//use Symfony\Component\Cache\Adapter\FilesystemAdapter;

use org\majkel\dbase\Table AS DBase;

use AtlasGrove\Tigerline as Tigerline;

// php bin/console atlasgrove:downloads

class TigerlineDownloads extends Tigerline
{
    public function __construct($container, SymfonyStyle $io = null)
    {
        parent::__construct($container, $io);
    }

    public function returnAndSave(string $outputFilename, array $records, bool $save)
    {
        $outputFile = $this->getMapPath() . "/" . $outputFilename;
        if ($save) {
            file_put_contents($outputFile, implode("\n", $records));
        }

        return [
            'records' => $records,
            'file' => $outputFile
        ];
    }

    /**
     */
    public function getMapList(string $type = "all", $save = false): array
    {
        $subfolder = "";
        switch ($type) {
            case 'states':
                $key = "maps.states";
                $filter = "/^[\d]{2}\.(png|jpg)$/";
                break;

            case 'counties':
                $key = "maps.counties";
                $filter = "/^[\d]{5}\.(png|jpg)$/";
                break;

            case 'roi':
                $key = "maps.roi";
                $filter = "/^.+,.+,.+,.+\.(png|jpg)$/";
                break;

            case 'steps':
                $subfolder = "/steps";
                $key = "maps.steps";
                $filter = "/^.+\.png$/";
                break;

            case 'road':
                $key = "maps.road";
                $filter = "/^[\d]{5}\..*\.road\.(png|jpg)$/";
                break;

            default:
                $key = "maps";
                $filter = "/.+.(png|jpg)/";
        }
        $cacheKey = "cache.{$key}";

        $cachedItems = $this->container->get('cache.app')->getItem($cacheKey);

        if ($cachedItems->isHit()) {
            $files = $cachedItems->get();
        } else {
            //
            $files = [];

            $finder = new Finder();
            //
            $finder->files()->depth("== 0")->path($filter)->in($this->getMapPath() . $subfolder);
            foreach ($finder as $file) {
                $files[] = $file->getRelativePathname();
            }

            if (count($files) > 0) {
                $cachedItems->expiresAfter($this->getCacheTTL());
                $cachedItems->set($files);
                $this->container->get('cache.app')->save($cachedItems);
            }
        }

        sort($files);

        $outputFilename = "/{$key}.txt";
        return $this->returnAndSave( $outputFilename, $files, $save);
    }

    /**
     * e.g. cache("47095") restrict to specific county
     */
    public function getCacheYears(string $filter = "", bool $save = false, string $outputFilename = "downloads-years.txt"): array
    {
        $cachedItems = $this->container->get('cache.app')->getItem('cache.years');

        if ($cachedItems->isHit()) {
            $files = $cachedItems->get();
        } else {
            $files = [];

            $finder = new Finder();
            $finder->directories()->depth("== 0")->path("/TIGER[\d]{4}/")->in($this->getRootDataPath());
            foreach ($finder as $file) {
                $files[] = $file->getRelativePathname();
            }

            if (count($files) > 0) {
                sort($files);

                $cachedItems->expiresAfter($this->getCacheTTL());
                $cachedItems->set($files);
                $this->container->get('cache.app')->save($cachedItems);
            }
        }

        return $this->returnAndSave( $outputFilename, $files, $save);
    }

    /**
     * e.g. cache("47095") restrict to specific county
     */
    public function getCacheStates(string $filter = "", bool $save = false, string $outputFilename = "downloads-states.txt"): array
    {
        $cachedItems = $this->container->get('cache.app')->getItem('cache.states');

        if ($cachedItems->isHit()) {
            $files = $cachedItems->get();
        } else {
            //
            $files = [];

            $finder = new Finder();

            $finder->directories()->depth("== 0")->path("/^[\d]{2}_(.*)/")->in($this->getDataPath());
            foreach ($finder as $file) {
                $files[] = $file->getRelativePathname();
            }

            if (count($files) > 0) {

                sort($files);

                $cachedItems->expiresAfter($this->getCacheTTL());
                $cachedItems->set($files);
                $this->container->get('cache.app')->save($cachedItems);
            }
        }

        return $this->returnAndSave( $outputFilename, $files, $save);
    }


    /**
     * e.g. cache("47095") restrict to specific county
     */
    public function getCacheCounties(string $filter = "", bool $save = false, string $outputFilename = "downloads-counties.txt"): array
    {
        $cachedItems = $this->container->get('cache.app')->getItem('cache.counties');

        if ($cachedItems->isHit()) {
            $dirs = $cachedItems->get();
        } else {
            $finder = new Finder();

            //
            $dirs = [];
            if ($filter == "") {
                $finder->directories()->depth("== 1")->path("/^[\d]{2}_(.*)/")->in($this->getDataPath());
            } else {
                $finder->directories()->depth("== 1")->path("/^[\d]{2}_(.*)/")->path($filter)->in($this->getDataPath());
            }
            foreach ($finder as $file) {
                $dirs[] = $file->getRelativePathname();
            }

            if (count($dirs) > 0) {
                sort($dirs);

                $cachedItems->expiresAfter($this->getCacheTTL());
                $cachedItems->set($dirs);
                $this->container->get('cache.app')->save($cachedItems);
            }
        }

        return $this->returnAndSave( $outputFilename, $dirs, $save);
    }


    /**
     * e.g. cache("47095") restrict to specific county
     */
    public function getCacheFiles(string $filter = "", bool $save = false, string $outputFilename = "downloads-files.txt"): array
    {
        $cachedItems = $this->container->get('cache.app')->getItem('cache.files');

        if ($cachedItems->isHit()) {
            $files = $cachedItems->get();
        } else {
            $files = [];

            $finder = new Finder();

            if ($filter == "") {
                $finder->files()->in($this->getDataPath());
            } else {
                $finder->files()->path($filter)->in($this->getDataPath());
            }
            foreach ($finder as $file) {
                $files[] = $file->getRelativePathname();
            }

            if (count($files) > 0) {
                sort($files);

                $cachedItems->expiresAfter($this->getCacheTTL());
                $cachedItems->set($files);
                $this->container->get('cache.app')->save($cachedItems);
            }
        }

        return $this->returnAndSave( $outputFilename, $files, $save);
    }


    /**
     */
    public function getDBFList($filter = "", $save = false, $outputFilename = "dbf.txt"): array
    {
        $outputFile= $this->getDataCachePath() . "/" . $outputFilename;
        $finder = new Finder();

        file_put_contents(
            $outputFile,
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
            $state_folder = $dir->getRelativePathname();

            $a = explode('_', $state_folder);
            $statefp = $a[0];

            // fe_2007_47_county.dbf
            $fileprefix = "fe_{$this->yearfp}_{$statefp}_";
            $statefolder = $this->getDataPath() . "/{$state_folder}";

            $dbf_filename = $this->getDataPath() . "/{$state_folder}/fe_{$this->yearfp}_{$statefp}_county.dbf";

            $dbf = DBase::fromFile($dbf_filename);

            foreach ($dbf as $record) {
                // $records[]=$record->getArrayCopy();
                // $records[]=[$record['CNTYIDFP'],$record['NAME'],$record['NAMELSAD']];

                foreach ($this->tigerline_subtypes as $subtype) {
                    $subtype = $subtype['prefix'];
                    // 47003_Bedford
                    // fe_2007_47003_arealm.dbf
                    $cntyidfp = $record['CNTYIDFP'];
                    $name = $record['NAME'];

                    $dbf2_filename = $this->getDataPath() . "/{$state_folder}/{$cntyidfp}_{$name}/fe_{$this->yearfp}_{$cntyidfp}_{$subtype}.dbf";

                    if (file_exists($dbf2_filename)) {
                        try {
                            $dbf2 = DBase::fromFile($dbf2_filename);

                            $records = [];
                            foreach ($dbf2 as $record2) {
                                $records[] = ($record2->getArrayCopy());

                            }
                            file_put_contents(
                                $outputFile,
                                json_encode($records),
                                FILE_APPEND
                            );
                        } catch (RuntimeException $e) {
                            $this->io->error($e->getMessage());
                            //
                        }
                    }

                }
            }

        }

        return $this->returnAndSave( $outputFilename, $records, $save);
    }


    /**
     */
    public function getSHXList($filter = "", $save = false, $outputFilename = "shx.txt")
    {
        $outputFile= $this->getDataCachePath() . "/" . $outputFilename;

        $finder = new Finder();

        $records = [];

        file_put_contents(
            $outputFile,
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
            $state_folder = $dir->getRelativePathname();
            echo "state_folder=$state_folder\n";

            $a = explode('_', $state_folder);
            $statefp = $a[0];

            // fe_2007_47_county.dbf
            $fileprefix = "fe_{$this->yearfp}_{$statefp}_";
            $statefolder = $this->getDataPath() . "/{$state_folder}";

            $dbf_filename = $this->getDataPath() . "/{$state_folder}/fe_{$this->yearfp}_{$statefp}_county.dbf";

            $dbf = DBase::fromFile($dbf_filename);

            foreach ($dbf as $record) {
                // $records[]=$record->getArrayCopy();
                // $records[]=[$record['CNTYIDFP'],$record['NAME'],$record['NAMELSAD']];

                foreach ($this->tigerline_subtypes as $subtype) {
                    $subtype = $subtype['prefix'];

                    // 47003_Bedford
                    // fe_2007_47003_arealm.dbf
                    $cntyidfp = $record['CNTYIDFP'];
                    $name = $record['NAME'];

                    $shx_filename = $this->getDataPath() . "/{$state_folder}/{$cntyidfp}_{$name}/fe_{$this->yearfp}_{$cntyidfp}_{$subtype}.shx";

                    $shx = $this->getShx($shx_filename);
                    //  list($header,$index)=$tigerline->get_shx($shx_filename);

                    file_put_contents(
                        $this->getDataCachePath() . "/" . $outputFilename,
                        json_encode($shx),
                        FILE_APPEND
                    );

                }
            }

        }

        //
        return $this->returnAndSave( $outputFilename, $records, $save);
    }


    /**
     */
    public function getSHPList($filter = "", $save = false, $outputFilename = "shp.txt")
    {
        $outputFile = $this->getDataCachePath() . "/" . $outputFilename;

        $finder = new Finder();

        file_put_contents(
            $outputFile,
            ""
        );
        file_put_contents(
            $outputFile . ".html",
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
            $state_folder = $dir->getRelativePathname();

            $a = explode('_', $state_folder);
            $statefp = $a[0];

            // fe_2007_47_county.dbf
            $fileprefix = "fe_{$this->yearfp}_{$statefp}_";
            $statefolder = $this->getDataPath() . "/{$state_folder}";

            $dbf_filename = $this->getDataPath() . "/{$state_folder}/fe_{$this->yearfp}_{$statefp}_county.dbf";

            $dbf = DBase::fromFile($dbf_filename);

            foreach ($dbf as $record) {

                $records = [];
                foreach ($this->tigerline_subtypes as $subtype) {
                    $subtype = $subtype['prefix'];

                    // 47003_Bedford
                    // fe_2007_47003_arealm.dbf
                    $cntyidfp = $record['CNTYIDFP'];
                    $name = $record['NAME'];

                    $shp_filename = $this->getDataPath() . "/{$state_folder}/{$cntyidfp}_{$name}/fe_{$this->yearfp}_{$cntyidfp}_{$subtype}.shp";

                    $shp = $this->get_shapefile($shp_filename);
                    $records[] = $shp;

                    file_put_contents(
                        $outputFile . ".html",
                        $this->printShps($shp),
                        FILE_APPEND
                    );

                }

                file_put_contents(
                    $outputFile,
                    json_encode($records),
                    FILE_APPEND
                );
            }

        }

        //
        return $this->returnAndSave( $outputFilename, $records, $save);
    }


    public function printShps($shps)
    {
        $s = "<table>";
        foreach ($shps as $shp) {
            $s .= "<tr>
            <th>
            {$shp['key']}
            </th>
            <td>
            {$shp['value']} {$shp['type']}
            </td>
            </tr>";
        }
        $s .= "</table>";
        return $s;
    }

}