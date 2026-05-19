<?php
// src/Controller/HomeController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

require_once __DIR__ . '/../Database/connexion.php';

#[Route('/commande', name: 'commande_')]
class HomeController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $pdo = getPDO();

        $sql = "SELECT * FROM commande";

        $stmt = $pdo->query($sql);

        $commandes = $stmt->fetchAll();

        return $this->render('home/index.html.twig', [
            'commandes' => $commandes
        ]);
    }

    #[Route('/create', name: 'new', methods: ['POST'])]
    public function new(): Response
    {
        // … Crée une nouvelle commande et la sauvegarde en base de données
        return new Response('Nouvelle commande');
    }

    #[Route('/show/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): Response
    {
        // ... Affiche les détails de la commande
        return $this->json([
            'message' => 'Commande trouvée',
            'id' => $id
        ]);
    }

    #[Route('/edit/{id}', name: 'edit', methods: ['PUT'])]
    public function edit(int $id): Response
    {
        // … Edite la commande et le sauvegarde en base de données
        return new Response('Commande modifiée');
    }

    #[Route('/delete/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): Response
    {
        // ... Supprime la commande de la base de données
        return new Response('Commande supprimée');
    }
}



