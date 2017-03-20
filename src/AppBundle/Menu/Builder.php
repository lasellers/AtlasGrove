<?php
// src/AppBundle/Menu/Builder.php
namespace AppBundle\Menu;

use Knp\Menu\FactoryInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class Builder implements ContainerAwareInterface
{
    use ContainerAwareTrait;
    
    public function createMainMenu(FactoryInterface $factory, array $options)
    {
        echo "main menu<br>\n";
        $menu = $factory->createItem('root');
        
        $menu->addChild('Home', array('route' => 'homepage'));
        /*
        // access services from the container!
        $em = $this->container->get('doctrine')->getManager();
        // findMostRecent and Blog are just imaginary examples
        $blog = $em->getRepository('AppBundle:Blog')->findMostRecent();
        
        $menu->addChild('Latest Blog Post', array(
        'route' => 'blog_show',
        'routeParameters' => array('id' => $blog->getId())
        ));
        */
        
        // create another menu item
        $menu->addChild('About Me', array('route' => 'about'));
        
        // you can also add sub level's to your menu's as follows
        $menu['About Me']->addChild('Edit profile', array('route' => 'edit_profile'));
        
        $menu->addChild('Maps', array('route' => 'maps'));
        $menu['Maps']->addChild('States', array('route' => 'maps_states'));
        $menu['Maps']->addChild('Counties', array('route' => 'maps_counties'));
        $menu['Maps']->addChild('County Roads', array('route' => 'maps_county_roads'));
        $menu['Maps']->addChild('ROI', array('route' => 'maps_roi'));
        
        // create another menu item
        $menu->addChild('Raw Data', array('route' => 'raw_data'));
        $menu['Raw Data']->addChild('Years', array('route' => 'raw_data_years'));
        $menu['Raw Data']->addChild('States', array('route' => 'raw_data_states'));
        $menu['Raw Data']->addChild('Counties', array('route' => 'raw_data_counties'));
        $menu['Raw Data']->addChild('Files', array('route' => 'raw_data_files'));
        
      //  $renderer = new ListRenderer(new Matcher());
    //   echo $renderer->render($menu);
        
        return $menu;
    }
    
    
    public function createAdminMenu(FactoryInterface $factory, array $options)
    {
        $menu = $factory->createItem('root');
        
        $menu->addChild('Home', array('route' => 'homepage'));
        /*
        // access services from the container!
        $em = $this->container->get('doctrine')->getManager();
        // findMostRecent and Blog are just imaginary examples
        $blog = $em->getRepository('AppBundle:Blog')->findMostRecent();
        
        $menu->addChild('Latest Blog Post', array(
        'route' => 'blog_show',
        'routeParameters' => array('id' => $blog->getId())
        ));
        */

        // create another menu item
        $menu->addChild('About Me', array('route' => 'about'));
        
        // you can also add sub level's to your menu's as follows
        $menu['About Me']->addChild('Edit profile', array('route' => 'edit_profile'));
        
        $menu->addChild('Generate Map', array('route' => 'generate'));
        $menu['Generate Map']->addChild('All Counties', array('route' => 'generate_map_counties'));
        $menu['Generate Map']->addChild('All States', array('route' => 'generate_map_states'));
        $menu['Generate Map']->addChild('All ROI', array('route' => 'generate_map_rois'));
        $menu['Generate Map']->addChild('All County Roads', array('route' => 'generate_map_county_roads'));
        $menu['Generate Map']->addChild('Specific Id', array('route' => 'generate_map_id'));
        $menu['Generate Map']->addChild('Specific ROI', array('route' => 'generate_map_roi'));
        
        
        //$renderer = new ListRenderer(new Matcher());
      //  echo $renderer->render($menu);
        
        return $menu;
    }
}

public function mainMenu()
{
    return "
    <ul>
    <li class="current first">
        <a href="#route_to/homepage">Home</a>
    </li>
    <li class="current_ancestor">
        <a href="#route_to/page_show/?id=42">About Me</a>
        <ul class="menu_level_1">
            <li class="current first last">
                <a href="#route_to/edit_profile">Edit profile</a>
            </li>
        </ul>
    </li>
</ul>";"
}
/*
use Knp\Menu\Matcher\Matcher;
use Knp\Menu\MenuFactory;
use Knp\Menu\Renderer\ListRenderer;

$factory = new MenuFactory();
$menu = $factory->createItem('My menu');
$menu->addChild('Home', array('uri' => '/'));
$menu->addChild('Comments', array('uri' => '#comments'));
$menu->addChild('Symfony2', array('uri' => 'http://symfony-reloaded.org/'));

$renderer = new ListRenderer(new Matcher());
echo $renderer->render($menu);
*/