<?php
// src/Controller/AvisController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

require_once __DIR__ . '/../Database/connexion.php';

// création des routes CRUD pour la gestion des thèmes
#[Route('/api/theme', name: 'api_theme_')]
class ThemeController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $pdo = getPDO();
        $sql = "SELECT * FROM theme";
        $stmt = $pdo->query($sql);
        $theme = $stmt->fetchAll();

        return $this->json(['theme' => $theme]);
    }

// CRUD : Création 
    #[Route('/create', name: 'new', methods: ['POST'])]
    public function new(Request $request): Response

    {
        $data = json_decode($request->getContent(),true);

        if (
            !$data ||
            !isset($data['idTheme']) ||
            !isset($data['libelle'])
        ) {
           return $this->json(['message' => 'Données invalides'], 400);
        } 

        $pdo = getPDO();
        $sql = "INSERT INTO theme(idTheme, libelle)
                VALUES(:idTheme, :libelle)";
        $stmt = $pdo->prepare($sql);
        // Insertion des données saisies dans la base de données
        $stmt->execute(['idTheme' => $data['idTheme'], 'libelle' => $data['libelle']]);

        // … Crée une nouvelle theme et la sauvegarde en base de données
        return $this->json(['message' => 'Theme créé', 'id' => $pdo->lastInsertId()],
        status: Response::HTTP_CREATED);  
    }

// CRUD : Lecture
    #[Route('/show/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): Response
    {
        $pdo = getPDO();
        $sql = "SELECT * FROM theme WHERE idTheme = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $theme = $stmt->fetch();

        if (!$theme) {
            return $this->json(['message' => 'Theme introuvable'], 404);
        }

        // ... Affiche les détails du theme
        return $this->json(['message' => 'Theme trouvé','theme' => $theme]);
    }

// CRUD : Mise à jour / modification
    #[Route('/edit/{id}', name: 'edit', methods: ['PUT'])]
    public function edit(int $id, Request $request): Response
    {
        $data = json_decode($request->getContent(),true);

        if (
            !$data ||
            !isset($data['libelle'])
        ) {
            return $this->json(['message' => 'Données invalides'], 400);
        }

        $pdo = getPDO();

        /*Vérifie si le thème existe*/

        $checkSql = "SELECT * FROM theme WHERE idTheme = :id";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute(['id' => $id]);
        $theme = $checkStmt->fetch();

        /*Si aucun thème trouvée*/

        if (!$theme) {
            return $this->json(['message' => 'Theme introuvable'], 404);
        }

        /*Mise à jour*/

        $sql = "UPDATE theme
                SET libelle = :libelle
                WHERE idTheme = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['libelle' => $data['libelle'], 'id' => $id]);

        //Récupération du thème mis à jour pour la réponse
        $updatedStmt = $pdo->prepare("SELECT * FROM theme WHERE idTheme = :id");
        $updatedStmt->execute(['id' => $id]);
        $updatedTheme = $updatedStmt->fetch();
        
        // … Edite le thème et le sauvegarde en base de données
        return $this->json(['message' => 'Theme modifié','theme' => $updatedTheme]);
    }

// CRUD : Suppression
    #[Route('/delete/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): Response
    {
        $pdo = getPDO();

        /*Vérification*/

        $checkSql = "SELECT * FROM theme
                     WHERE idTheme = :id";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute(['id' => $id]);
        $theme = $checkStmt->fetch();

        if (!$theme) {
            return $this->json(['message' => 'Theme introuvable'], 404);
        }

        /*Suppression*/

        $sql = "DELETE FROM theme
                WHERE idTheme = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $id]);

        // ... Supprime le thème de la base de données
        return $this->json(['message' => 'Theme supprimé'],Response::HTTP_OK);
    }
}