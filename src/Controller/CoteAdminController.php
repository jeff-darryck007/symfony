<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\TechniqueRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CoteAdminController extends AbstractController
{
    #[Route('/homeAdmin', name: 'app_cote_admin')]
    public function index(CategoryRepository $categoryRepo, TechniqueRepository $techniqueRepo): Response
    {
        // Récupérer toutes les catégories et techniques
        $categories = $categoryRepo->findAll();
        $techniques = $techniqueRepo->findAll();

        return $this->render('pages/homeAdmin.html.twig', [
            'controller_name' => 'CoteAdminController',
            'categories' => $categories,
            'techniques' => $techniques
        ]);
    }
}
