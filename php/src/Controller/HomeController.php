<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

require_once __DIR__ . '/../Database/connexion.php';

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
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
}
