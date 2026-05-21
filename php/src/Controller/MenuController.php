<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

require_once __DIR__ . '/../Database/connexion.php';

// création des routes CRUD pour la gestion des menus
#[Route('/api/menu', name: 'api_menu_')]
class MenuController extends AbstractController
{
    /*READ ALL avec GET /api/menu*/
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $pdo = getPDO();
        $sql = "SELECT * FROM menu";
        $stmt = $pdo->query($sql);
        $menus = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $this->json(['menus' => $menus]);
    }

    /*READ ONE avec GET /api/menu/{id}*/
    #[Route('/show/{id}', name: 'show', methods: ['GET'])]
    public function show($id): Response
    {
        $pdo = getPDO();
        $sql = "SELECT * FROM menu WHERE idMenu = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $menu = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$menu) {
            return $this->json(['message' => 'Menu introuvable'], 404);
        }
        // ... Affiche les détails du menu
        return $this->json(['message' => 'Menu trouvé','menu' => $menu]);
    }

    /*CREATE avec POST /api/menu*/
    #[Route('/create', name: 'create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        $pdo = getPDO();

        $data = json_decode($request->getContent(), true);
        $sql = "INSERT INTO menu
        (titre, nbre_personne_min, prix_personne, regime, description, quantite_restante, idTheme, idRegime)
        VALUES
        (:titre, :nbre, :prix, :regime, :description, :quantite, :theme, :regimeId)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'titre' => $data['titre'],
            'nbre' => $data['nbre_personne_min'],
            'prix' => $data['prix_personne'],
            'regime' => $data['regime'],
            'description' => $data['description'],
            'quantite' => $data['quantite_restante'],
            'theme' => $data['idTheme'],
            'regimeId' => $data['idRegime']
        ]);

        return $this->json(['message' => 'Menu créé', 'id' => $pdo->lastInsertId()],
        status: Response::HTTP_CREATED);
    }

    /*UPDATE avec PUT /api/menu/{id}*/
    #[Route('/update/{id}', name: 'update', methods: ['PUT'])]
    public function update($id, Request $request): Response
    {
        $pdo = getPDO();

        // Vérifier si le menu existe
        $checkSql = "SELECT * FROM menu WHERE idMenu = :id";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute(['id' => $id]);
        $menu = $checkStmt->fetch(\PDO::FETCH_ASSOC);

        if (!$menu) {
            return $this->json(['message' => 'Menu introuvable'], 404);
        }

        // Récupérer les données de la requête
        $data = json_decode($request->getContent(), true);
            if (
                !$data ||
                !isset($data['titre']) ||
                !isset($data['nbre_personne_min']) ||
                !isset($data['prix_personne']) ||
                !isset($data['regime']) ||
                !isset($data['description']) ||
                !isset($data['quantite_restante']) ||
                !isset($data['idTheme']) ||
                !isset($data['idRegime'])
            ){
                return $this->json(['message' => 'Données invalides'], 400);
            }

        // Mettre à jour le menu
         $sql = "UPDATE menu
                SET titre = :titre,
                    nbre_personne_min = :nbre,
                    prix_personne = :prix,
                    regime = :regime,
                    description = :description,
                    quantite_restante = :quantite,
                    idTheme = :theme,
                    idRegime = :regimeId
                WHERE idMenu = :id";
        $sql = "UPDATE menu
                SET titre = :titre
                WHERE idMenu = :id";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            'titre' => $data['titre'],
            'id' => $id
        ]);

        // Récupérer le menu mis à jour pour la réponse
        $stmt = $pdo->prepare("SELECT * FROM menu WHERE idMenu = :id");
        $stmt->execute(['id' => $id]);
        $updatedMenu = $stmt->fetch(\PDO::FETCH_ASSOC);

        // ... Edite le menu et le sauvegarde en base de données
        return $this->json([
            'message' => 'Menu modifié',
            'menu' => $updatedMenu
        ]);
    }

    /* DELETE avec/api/menu/{id} */
    #[Route('/delete/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete($id): Response
    {
        $pdo = getPDO();

        // Vérifier si le menu existe
        $checkSql = "SELECT * FROM menu WHERE idMenu = :id";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute(['id' => $id]);
        $menu = $checkStmt->fetch(\PDO::FETCH_ASSOC);

        if (!$menu) {
            return $this->json(['message' => 'Menu introuvable'], 404);
        }

        $sql = "DELETE FROM menu WHERE idMenu = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $id]);

        // ... Supprime le menu de la base de données
        return $this->json(['message' => 'Menu supprimé'], Response::HTTP_OK);
    }
}