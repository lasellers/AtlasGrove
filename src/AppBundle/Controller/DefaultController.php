<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use AtlasGrove\Tigerline as Tigerline;
use AtlasGrove\TigerlineDownloads as TigerlineDownloads;

use AppBundle\Menu\Builder as MenuBuilder;


class DefaultController extends Controller
{
    /**
    * @Route("/", name="homepage")
    */
    public function indexAction(Request $request)
    {
        $logger = $this->get('logger');
        
        $tigerline=new TigerlineDownloads($this->container);
        
        // $menu =$this->get('knp_menu.menu_provider')->get('main');
        //  $menu=MenuBuilder.createMainMenu();
        
        return $this->render('default/index.html.twig', [
        'base_dir' => realpath($this->getParameter('kernel.root_dir').'/..').DIRECTORY_SEPARATOR,
        //   'menu'=>$menu
        ]);
    }
    
    /**
    * @Route("/about", name="about")
    */
    public function aboutAction()
    {
        return new Response(
        '<html><body>About route</body></html>'
        );
    }
    
        
    /**
    * @Route("/contact", name="contact")
    */
    public function contactAction()
    {
        return new Response(
        '<html><body>Contact route</body></html>'
        );
    }

    /**
    * @Route("/map/states", name="map_states")
    */
    public function mapStatesAction(Request $request)
    {
        $logger = $this->get('logger');
        
        $tigerline=new TigerlineDownloads($this->container);
        
        $obj=$tigerline->getMapList('states');
        $maps=$obj['records'];
        
        return $this->render('default/map.html.twig', [
        'base_dir' => realpath($this->getParameter('kernel.root_dir').'/..').DIRECTORY_SEPARATOR,
        'maps' => $maps,
        'map_path'=> $tigerline->getMapPath(),
        'map_base'=> '/map/'
        ]);
    }
    
    
    /**
    * @Route("/map/counties", name="map_counties")
    */
    public function mapCountiesAction(Request $request)
    {
        $logger = $this->get('logger');
        
        $tigerline=new TigerlineDownloads($this->container);
        
        $obj=$tigerline->getMapList('counties');
        $maps=$obj['records'];
        
        return $this->render('default/map.html.twig', [
        'base_dir' => realpath($this->getParameter('kernel.root_dir').'/..').DIRECTORY_SEPARATOR,
        'maps' => $maps,
        'map_path'=> $tigerline->getMapPath(),
        'map_base'=> '/map/'
        ]);
    }
    
    
    /**
    * @Route("/map/roi", name="map_roi")
    */
    public function mapROIAction(Request $request)
    {
        $logger = $this->get('logger');
        
        $tigerline=new TigerlineDownloads($this->container);
        
        $obj=$tigerline->getMapList('roi');
        $maps=$obj['records'];
        
        return $this->render('default/map.html.twig', [
        'base_dir' => realpath($this->getParameter('kernel.root_dir').'/..').DIRECTORY_SEPARATOR,
        'maps' => $maps,
        'map_path'=> $tigerline->getMapPath(),
        'map_base'=> '/map/'
        ]);
    }

    
    /**
    * @Route("/map/steps", name="map_steps")
    */
    public function mapROIStepsAction(Request $request)
    {
        $logger = $this->get('logger');
        
        $tigerline=new TigerlineDownloads($this->container);
        
        $obj=$tigerline->getMapList('steps');
        $maps=$obj['records'];
        
        return $this->render('default/map.html.twig', [
        'base_dir' => realpath($this->getParameter('kernel.root_dir').'/..').DIRECTORY_SEPARATOR,
        'maps' => $maps,
        'map_path'=> $tigerline->getMapPath(),
        'map_base'=> '/map/steps/'
        ]);
    }
    

    /**
    * @Route("/map/roads/counties", name="map_roads_counties")
    */
    public function mapRoadsCountiesAction(Request $request)
    {
        $logger = $this->get('logger');
        
        $tigerline=new TigerlineDownloads($this->container);
        
        $obj=$tigerline->getMapList('roads');
        $maps=$obj['records'];
        
        return $this->render('default/maps.html.twig', [
        'base_dir' => realpath($this->getParameter('kernel.root_dir').'/..').DIRECTORY_SEPARATOR,
        'maps' => $maps,
        'map_path'=> $tigerline->tigerline->getMapPath(),
        'map_base'=> 'map/'
        ]);
    }
}