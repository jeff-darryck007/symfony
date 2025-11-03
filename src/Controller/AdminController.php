<?php

namespace App\Controller;

use App\Entity\Painting;
use App\Entity\Comment;
use App\Form\CommentType;
use App\Form\PaintingFormType;
use Symfony\Component\HttpFoundation\JsonResponse; // formater les reponses en json
use App\Repository\PaintingRepository;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AdminController extends AbstractController
{
    #[Route('/homeAdmin', name: 'admin_painting_index', methods: ['GET'])]
    public function homeAdmin(PaintingRepository $paintingRepository): Response
    {
        $paintings = $paintingRepository->findAll();

        return $this->render('pages/homeAdmin.html.twig', [
            'paintings' => $paintings,
        ]);
    }

    #[Route('/edit/{id}', name: 'admin_painting_edit', methods: ['GET', 'POST'])]
    public function edit(Painting $painting, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(PaintingFormType::class, $painting);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // 1️⃣ Gestion de l'image uploadée
            $file = $form->get('image')->getData();

            if ($file) {
                // Nom unique pour le fichier
                $newFilename = uniqid() . '.' . $file->guessExtension();

                // Chemin du dossier de destination (configuré dans services.yaml)
                $uploadDir = $this->getParameter('paintings_directory');

                // Supprime l’ancienne image s’il y en a une
                if ($painting->getImage() && file_exists($uploadDir . '/' . $painting->getImage())) {
                    unlink($uploadDir . '/' . $painting->getImage());
                }

                // Déplace le nouveau fichier dans le dossier /public/uploads/paintings
                $file->move($uploadDir, $newFilename);

                // Met à jour le nom du fichier dans l'entité
                $painting->setImage($newFilename);
            }

            // 2️⃣ Sauvegarde des modifications
            $em->flush();

            // 3️⃣ Message flash
            $this->addFlash('success', 'La peinture a été mise à jour avec succès.');

            // 4️⃣ Redirection
            return $this->redirectToRoute('admin_painting_index');
        }

        return $this->render('pages/edit.html.twig', [
            'form' => $form->createView(),
            'painting' => $painting,
        ]);
    }

    #[Route('/delete/{id}', name: 'admin_painting_delete', methods: ['POST'])]
    public function delete(Request $request, Painting $painting, EntityManagerInterface $em): Response
    {
        // Vérifie le token CSRF pour éviter les suppressions malveillantes
        if (!$this->isCsrfTokenValid('delete' . $painting->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide ou expiré.');
        }

        // Supprimer la peinture de la base
        $em->remove($painting);
        $em->flush();

        // Message flash de confirmation
        $this->addFlash('success', sprintf('La peinture "%s" a été supprimée avec succès.', $painting->getTitle()));

        // Redirection vers la liste
        return $this->redirectToRoute('admin_painting_index');
    }


    #[Route('/toggle/{id}', name: 'admin_painting_toggle', methods: ['POST'])]
    public function toggleVisible(Painting $painting, EntityManagerInterface $em): Response
    {
        // Inverse la valeur actuelle (true -> false ou 1 -> 0)
        $painting->setVisible(!$painting->getVisible());

        $em->flush();

        // Message flash selon le nouvel état
        if ($painting->getVisible()) {
            $this->addFlash('success', sprintf('La peinture "%s" est maintenant visible.', $painting->getTitle()));
        } else {
            $this->addFlash('warning', sprintf('La peinture "%s" est maintenant masquée.', $painting->getTitle()));
        }

        return $this->redirectToRoute('admin_painting_index');
    }



    //  Liste des peintures
    #[Route('/gallery', name: 'app_admin', methods: ['GET'])]
    public function index(PaintingRepository $paintingRepository): Response
    {
        $paintings = $paintingRepository->findBy(['visible' => true]);

        return $this->render('pages/gallery.html.twig', [
            'paintings' => $paintings
        ]);
    }

    #[Route('/painting/{id}', name: 'app_painting_show', methods: ['GET', 'POST'])]
    public function show(int $id, PaintingRepository $paintingRepository, CommentRepository $commentRepository, Request $request, EntityManagerInterface $em): Response
    {
        $painting = $paintingRepository->find($id);

        if (!$painting) {
            throw $this->createNotFoundException("Cette œuvre n'existe pas.");
        }

        $comments_all = $commentRepository->findByPaintingId($painting->getId());

        // Nouveau commentaire
        $comment = new Comment();
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setCreatedAt(new \DateTimeImmutable());
            $comment->setIdPainting($painting); 

            $em->persist($comment);
            $em->flush();

            $this->addFlash('success', 'Votre commentaire a été ajouté avec succès !');
            return $this->redirectToRoute('app_painting_show', ['id' => $painting->getId()]);
        }

        return $this->render('pages/detailGallery.html.twig', [
            'painting' => $painting,
            'comments' => $comments_all,
            'form' => $form->createView(),
        ]);
    }
    
     //modification

    /*#[Route('/admin/edit/{id}', name: 'admin_painting_edit', methods: ['GET', 'POST'])]
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
*/
    // Supprimer une peinture
        /*#[Route('/admin/delete/{id}', name: 'admin_painting_delete', methods: ['POST'])]
        public function delete(Painting $painting, EntityManagerInterface $em): JsonResponse
        {
            $em->remove($painting);
            $em->flush();

            return new JsonResponse([
                'message' => 'Peinture supprimée avec succès',
                'deleted_painting_id' => $painting->getId()
            ], 200);
        }*/

        // Afficher / Masquer une peinture
        /*#[Route('/admin/toggle/{id}', name: 'admin_painting_toggle', methods: ['POST'])]
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
        }*/
}