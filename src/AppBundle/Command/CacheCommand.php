<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use AtlasGrove\TigerlineCache as TigerlineCache;

// php bin/console atlasgrove:cache

class CacheCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('atlasgrove:cache');
        $this->setDescription('lists AtlasGrove cached tigerline files');

        $this->addArgument('id', InputArgument::OPTIONAL, "id");

        $this->addOption('force', null, InputOption::VALUE_NONE, 'If set, uses force');

        $this->addOption('states-list', null, InputOption::VALUE_NONE, 'If set, uses ..');
        $this->addOption('counties-list', null, InputOption::VALUE_NONE, 'If set, uses ..');

        $this->addOption('states', null, InputOption::VALUE_NONE, 'If set, uses ..');
        $this->addOption('counties', null, InputOption::VALUE_NONE, 'If set, uses ..');

        $this->addOption('all', null, InputOption::VALUE_NONE, 'If set, uses ..');

        //$this->addOption('path', 'p', InputOption::VALUE_REQUIRED, '', getcwd());
        $this->addOption('path', null, InputOption::VALUE_NONE, 'Optional output path');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $tigerline = new TigerlineCache($this->getContainer(), $io);

        $id = $input->getArgument('id') ?: "";
        $io->note("Id=$id");

        $force = $input->getOption('force') ? true : false;
        $io->note('Force ' . ($force ? "YES" : "NO"));

        if ($input->getOption('states-list') || $input->getOption('all')) {
            $io->title('Cache States List');
            $files = $tigerline->cacheStatesList();

            foreach ($files as &$file) {
                $file = explode("\t", $file);
            }
            $io->table(['id', 'folder', 'name'], $files);
        }

        if ($input->getOption('states') || $input->getOption('all')) {
            $io->title('Cache States Shapes');
            $files = $tigerline->cacheStatesList();

            $tigerline->cacheShapes($files);
        }

        if ($input->getOption('counties-list') || $input->getOption('all')) {
            $io->title('Cache Counties List');
            $files = $tigerline->cacheCountiesList();

            foreach ($files as &$file) {
                $file = explode("\t", $file);
            }
            $io->table(['id', 'county', 'full'], $files);
        }

        if ($input->getOption('counties') || $input->getOption('all')) {
            $io->title('Cache Counties Shapes');
            $files = $tigerline->cacheCountiesList();

            $tigerline->cacheShapes($files, $force);
        }

        if ($id > 0) {
            $io->title("Cache Id {$id}");

           $tigerline->cacheShape($id, $force);
            //  $io->table(['id','county','full'],$records);
        }

    }
}