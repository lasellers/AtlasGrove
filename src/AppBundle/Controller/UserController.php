<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use AtlasGrove\Tigerline as Tigerline;
use AtlasGrove\Utils as Utils;

class UserController extends Controller
{
    /**
    * @Route("/user", name="user_homepage")
    */
    public function indexAction(Request $request)
    {
        $logger = $this->get('logger');
        $logger->info(__FUNCTION__);
        $logger->info(__CLASS__);
        $logger->err(__CLASS__);
        /*
        return new Response(
        '<html><body>home route</body></html>'
        );*/
        // replace this example code with whatever you need
        return $this->render('default/user/index.html.twig', [
        'base_dir' => realpath($this->getParameter('kernel.root_dir').'/..').DIRECTORY_SEPARATOR,
        ]);
    }
    
    /**
    * @Route("/user/about", name="user_about")
    */
    public function aboutAction()
    {
        return new Response(
        '<html><body>user About route</body></html>'
        );
    }
    
}