<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use AtlasGrove\Tigerline as Tigerline;
use AtlasGrove\Utils as Utils;

use AppBundle\Menu\Builder as MenuBuilder;


class DefaultController extends Controller
{
    /**
    * @Route("/", name="homepage")
    */
    public function indexAction(Request $request)
    {
        $logger = $this->get('logger');
        
        $util=new Utils($this->container);
        
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
    * @Route("/map/states", name="map_states")
    */
    public function mapStatesAction(Request $request)
    {
        $logger = $this->get('logger');
        
        $util=new Utils($this->container);
        
        $obj=$util->getMapList('states');
        $maps=$obj['records'];
        
        return $this->render('default/map.html.twig', [
        'base_dir' => realpath($this->getParameter('kernel.root_dir').'/..').DIRECTORY_SEPARATOR,
        'maps' => $maps,
        'map_path'=> $util->tigerline->getMapPath(),
        'map_base'=> 'map/'
        ]);
    }
    
    
    /**
    * @Route("/map/counties", name="map_counties")
    */
    public function mapCountiesAction(Request $request)
    {
        $logger = $this->get('logger');
        
        $util=new Utils($this->container);
        
        $obj=$util->getMapList('counties');
        $maps=$obj['records'];
        
        return $this->render('default/map.html.twig', [
        'base_dir' => realpath($this->getParameter('kernel.root_dir').'/..').DIRECTORY_SEPARATOR,
        'maps' => $maps,
        'map_path'=> $util->tigerline->getMapPath(),
        'map_base'=> 'map/'
        ]);
    }
    
    
    
    /**
    * @Route("/map/roi", name="map_roi")
    */
    public function mapROIAction(Request $request)
    {
        $logger = $this->get('logger');
        
        $util=new Utils($this->container);
        
        $obj=$util->getMapList('roi');
        $maps=$obj['records'];
        
        return $this->render('default/map.html.twig', [
        'base_dir' => realpath($this->getParameter('kernel.root_dir').'/..').DIRECTORY_SEPARATOR,
        'maps' => $maps,
        'map_path'=> $util->tigerline->getMapPath(),
        'map_base'=> 'map/'
        ]);
    }
    
    
    /**
    * @Route("/map/roads/counties", name="map_roads_counties")
    */
    public function mapRoadsCountiesAction(Request $request)
    {
        $logger = $this->get('logger');
        
        $util=new Utils($this->container);
        
        $obj=$util->getMapList('roads');
        $maps=$obj['records'];
        
        return $this->render('default/maps.html.twig', [
        'base_dir' => realpath($this->getParameter('kernel.root_dir').'/..').DIRECTORY_SEPARATOR,
        'maps' => $maps,
        'map_path'=> $util->tigerline->getMapPath(),
        'map_base'=> 'map/'
        ]);
    }
}