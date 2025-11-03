<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/home', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('pages/home.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    } 

    #[Route('/', name: 'app_home2')]
    public function index2(): Response
    {
        return $this->render('pages/home.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    } 

    #[Route('/about', name: 'app_about')]
    public function about(): Response
    {
        return $this->render('pages/about.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }

    #[Route('/team', name: 'app_team')]
    public function team(): Response
    {
        return $this->render('pages/team.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }
}
