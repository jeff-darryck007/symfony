<?php

namespace App\Controller;

use App\Entity\Painting;
use App\Form\PaintingType;
use Symfony\Component\HttpFoundation\JsonResponse; // formater les reponses en json
use App\Repository\PaintingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AdminController extends AbstractController
{
    //  Liste des peintures
    #[Route('/admin', name: 'app_admin', methods: ['GET'])]
    public function index(PaintingRepository $paintingRepository): Response
    {
        $paintings = $paintingRepository->findAll();

        return new JsonResponse([
            'message' => 'ok.',
            'data' => $paintings
        ], 200);
    }
    
     //modification

    #[Route('/admin/edit/{id}', name: 'admin_painting_edit', methods: ['GET', 'POST'])]
    public function edit(Painting $painting, Request $request, EntityManagerInterface $em): Response
    {
    // Si la requête est POST, on met à jour la peinture
    if ($request->isMethod('POST')) {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'JSON invalide'], 400);
        }

        if (isset($data['title'])) $painting->setTitle($data['title']);
        if (isset($data['description'])) $painting->setDescription($data['description']);
        if (isset($data['height'])) $painting->setHeight((float)$data['height']);
        if (isset($data['width'])) $painting->setWidth((float)$data['width']);
        if (isset($data['image'])) $painting->setImage($data['image']);

        $em->flush();

        return $this->json([
            'message' => 'Peinture mise à jour avec succès',
            'painting' => [
                'id' => $painting->getId(),
                'title' => $painting->getTitle(),
                'description' => $painting->getDescription(),
                'created' => $painting->getCreated()?->format('Y-m-d'),
                'height' => $painting->getHeight(),
                'width' => $painting->getWidth(),
                'image' => $painting->getImage(),
                'category' => $painting->getIdCategory()?->getName(),
                'technique' => $painting->getIdTechnique()?->getName(),
                'visible' => $painting->isVisible(),
            ]
        ], 200);
    }

    // Si c'est GET, on renvoie les informations actuelles de la peinture
    return $this->json([
        'message' => 'Peinture récupérée avec succès',
        'painting' => [
            'id' => $painting->getId(),
            'title' => $painting->getTitle(),
            'description' => $painting->getDescription(),
            'created' => $painting->getCreated()?->format('Y-m-d'),
            'height' => $painting->getHeight(),
            'width' => $painting->getWidth(),
            'image' => $painting->getImage(),
            'category' => $painting->getIdCategory()?->getName(),
            'technique' => $painting->getIdTechnique()?->getName(),
            'visible' => $painting->isVisible(),
        ]
    ], 200);
}

    // Supprimer une peinture
        #[Route('/admin/delete/{id}', name: 'admin_painting_delete', methods: ['POST'])]
        public function delete(Painting $painting, EntityManagerInterface $em): JsonResponse
        {
            $em->remove($painting);
            $em->flush();

            return new JsonResponse([
                'message' => 'Peinture supprimée avec succès',
                'deleted_painting_id' => $painting->getId()
            ], 200);
        }

        // Afficher / Masquer une peinture
        #[Route('/admin/toggle/{id}', name: 'admin_painting_toggle', methods: ['POST'])]
        public function toggle(Painting $painting, EntityManagerInterface $em): JsonResponse
        {
            $painting->setVisible(!$painting->isVisible());
            $em->flush();

            return new JsonResponse([
                'message' => $painting->isVisible() ? 'Peinture affichée.' : 'Peinture masquée.',
                'painting' => [
                    'id' => $painting->getId(),
                    'visible' => $painting->isVisible()
                ]
            ], 200);
        }
}