<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

//use AtlasGrove\Tigerline as Tigerline;
use AtlasGrove\TigerlineDownloads as TigerlineDownloads;

use AppBundle\Menu\Builder as MenuBuilder;

class AdminController extends Controller
{
    /**
     * @Route("/admin", name="admin_homepage")
     */
    public function indexAction(Request $request)
    {
        $logger = $this->get('logger');

        $tigerline = new TigerlineDownloads($this->container);

        // replace this example code with whatever you need
        return $this->render('default/admin/index.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.root_dir') . '/..') . DIRECTORY_SEPARATOR,
        ]);
    }

    /**
     * @Route("/admin/raw/years", name="raw_years")
     */
    public function rawYearsAction(Request $request)
    {
        $logger = $this->get('logger');

        $tigerline = new TigerlineDownloads($this->container);

        $obj = $tigerline->getCacheYears();
        $records = $obj['records'];

        return $this->render('default/admin/raw.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.root_dir') . '/..') . DIRECTORY_SEPARATOR,
            'records' => $records,
            'map_path' => $tigerline->getMapPath(),
            'map_base' => 'map/'
        ]);
    }

    /**
     * @Route("/admin/raw/states", name="raw_states")
     */
    public function rawStatesAction(Request $request)
    {
        $logger = $this->get('logger');

        $tigerline = new TigerlineDownloads($this->container);

        $obj = $tigerline->getCacheStates();
        $records = $obj['records'];

        return $this->render('default/admin/raw.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.root_dir') . '/..') . DIRECTORY_SEPARATOR,
            'records' => $records,
            'map_path' => $tigerline->getMapPath(),
            'map_base' => 'map/'
        ]);
    }

    /**
     * @Route("/admin/raw/counties", name="raw_counties")
     */
    public function rawCountiesAction(Request $request)
    {
        $logger = $this->get('logger');

        $tigerline = new TigerlineDownloads($this->container);

        $obj = $tigerline->getCacheCounties();
        $records = $obj['records'];

        return $this->render('default/admin/raw.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.root_dir') . '/..') . DIRECTORY_SEPARATOR,
            'records' => $records,
            'map_path' => $tigerline->getMapPath(),
            'map_base' => 'map/'
        ]);
    }


    /**
     * @Route("/admin/raw/files", name="raw_files")
     */
    public function rawFilesAction(Request $request)
    {
        $logger = $this->get('logger');

        $tigerline = new TigerlineDownloads($this->container);

        $obj = $tigerline->getCacheFiles();
        $records = $obj['records'];

        return $this->render('default/admin/raw.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.root_dir') . '/..') . DIRECTORY_SEPARATOR,
            'records' => $records,
            'map_path' => $tigerline->getMapPath(),
            'map_base' => 'map/'
        ]);
    }


    /**
     * @Route("/admin/id", name="admin_id")
     */
    public function renderIdAction(Request $request)
    {
        $logger = $this->get('logger');

        $tigerline = new TigerlineDownloads($this->container);

        $obj = $tigerline->getCacheFiles();
        $records = $obj['records'];

        return $this->render('default/admin/index.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.root_dir') . '/..') . DIRECTORY_SEPARATOR,
            'records' => $records,
            'map_path' => $tigerline->getMapPath(),
            'map_base' => 'map/'
        ]);
    }


    /**
     * @Route("/admin/roi", name="admin_roi")
     */
    public function renderROIAction(Request $request)
    {
        $logger = $this->get('logger');

        $tigerline = new TigerlineDownloads($this->container);

        $obj = $tigerline->getCacheFiles();
        $records = $obj['records'];

        return $this->render('default/admin/index.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.root_dir') . '/..') . DIRECTORY_SEPARATOR,
            'records' => $records,
            'map_path' => $tigerline->getMapPath(),
            'map_base' => 'map/'
        ]);
    }


    /**
     * @Route("/admin/states", name="admin_states")
     */
    public function renderStatesAction(Request $request)
    {
        $logger = $this->get('logger');

        $tigerline = new TigerlineDownloads($this->container);

        $obj = $tigerline->getCacheFiles();
        $records = $obj['records'];

        return $this->render('default/admin/index.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.root_dir') . '/..') . DIRECTORY_SEPARATOR,
            'records' => $records,
            'map_path' => $tigerline->getMapPath(),
            'map_base' => 'map/'
        ]);
    }


    /**
     * @Route("/admin/counties", name="admin_counties")
     */
    public function renderCountiesAction(Request $request)
    {
        $logger = $this->get('logger');

        $tigerline = new TigerlineDownloads($this->container);

        $obj = $tigerline->getCacheFiles();
        $records = $obj['records'];

        return $this->render('default/admin/index.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.root_dir') . '/..') . DIRECTORY_SEPARATOR,
            'records' => $records,
            'map_path' => $tigerline->getMapPath(),
            'map_base' => 'map/'
        ]);
    }


}