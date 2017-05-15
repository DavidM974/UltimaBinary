<?php

namespace UB\PlatformBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('UBPlatformBundle:Default:index.html.twig');
    }
}
