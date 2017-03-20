<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use AtlasGrove\Tigerline as Tigerline;
use AtlasGrove\Utils as Utils;

class AdminController extends Controller
{
    /**
    * @Route("/admin", name="admin_homepage")
    */
    public function indexAction(Request $request)
    {
        $logger = $this->get('logger');

        /*
        return new Response(
        '<html><body>home route</body></html>'
        );*/
        // replace this example code with whatever you need
        return $this->render('default/admin/index.html.twig', [
        'base_dir' => realpath($this->getParameter('kernel.root_dir').'/..').DIRECTORY_SEPARATOR,
        ]);
    }
    
    /**
    * @Route("/admin/about", name="admin_about")
    */
    public function aboutAction()
    {
        return new Response(
        '<html><body>user About route</body></html>'
        );
    }

            /**
    * @Route("/admin/raw/years", name="raw_years")
    */
    public function rawYearsAction(Request $request)
    {
        $logger = $this->get('logger');
        
        $util=new Utils($this->container);
        
        $obj=$util->getCacheYears();
        $records=$obj['records'];
        
        return $this->render('default/admin/raw.html.twig', [
        'base_dir' => realpath($this->getParameter('kernel.root_dir').'/..').DIRECTORY_SEPARATOR,
        'records' => $records,
        'map_path'=> $util->tigerline->getMapPath(),
        'map_base'=> 'map/'
        ]);
    }
    
    /**
    * @Route("/admin/raw/states", name="raw_states")
    */
    public function rawStatesAction(Request $request)
    {
        $logger = $this->get('logger');
        
        $util=new Utils($this->container);
        
        $obj=$util->getCacheStates();
        $records=$obj['records'];
        
        return $this->render('default/admin/raw.html.twig', [
        'base_dir' => realpath($this->getParameter('kernel.root_dir').'/..').DIRECTORY_SEPARATOR,
        'records' => $records,
        'map_path'=> $util->tigerline->getMapPath(),
        'map_base'=> 'map/'
        ]);
    }
    
    /**
    * @Route("/admin/raw/counties", name="raw_counties")
    */
    public function rawCountiesAction(Request $request)
    {
        $logger = $this->get('logger');
        
        $util=new Utils($this->container);
        
        $obj=$util->getCacheCounties();
        $records=$obj['records'];
        
        return $this->render('default/admin/raw.html.twig', [
        'base_dir' => realpath($this->getParameter('kernel.root_dir').'/..').DIRECTORY_SEPARATOR,
        'records' => $records,
        'map_path'=> $util->tigerline->getMapPath(),
        'map_base'=> 'map/'
        ]);
    }
    
    /**
    * @Route("/admin/raw/roads/counties", name="raw_county_roads")
    */
    public function rawCountyRoadsAction(Request $request)
    {
        $logger = $this->get('logger');
        
        $util=new Utils($this->container);
        
        $obj=$util->getCacheCounties();
        $records=$obj['records'];
        
        return $this->render('default/admin/raw.html.twig', [
        'base_dir' => realpath($this->getParameter('kernel.root_dir').'/..').DIRECTORY_SEPARATOR,
        'records' => $records,
        'map_path'=> $util->tigerline->getMapPath(),
        'map_base'=> 'map/'
        ]);
    }
    
    
    /**
    * @Route("/admin/raw/files", name="raw_files")
    */
    public function rawFilesAction(Request $request)
    {
        $logger = $this->get('logger');
        
        $util=new Utils($this->container);
        
        $obj=$util->getCacheFiles();
        $records=$obj['records'];
        
        return $this->render('default/admin/raw.html.twig', [
        'base_dir' => realpath($this->getParameter('kernel.root_dir').'/..').DIRECTORY_SEPARATOR,
        'records' => $records,
        'map_path'=> $util->tigerline->getMapPath(),
        'map_base'=> 'map/'
        ]);
    }
    
    
}