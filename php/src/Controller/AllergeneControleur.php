<?php
// src/Controller/AvisController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

require_once __DIR__ . '/../Database/connexion.php';

// création des routes CRUD pour la gestion des allergènes
#[Route('/api/allergene', name: 'api_allergene_')]
class AllergeneController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $pdo = getPDO();
        $sql = "SELECT * FROM allergene";
        $stmt = $pdo->query($sql);
        $allergene = $stmt->fetchAll();

        return $this->json(['allergene' => $allergene]);
    }

// CRUD : Création 
    #[Route('/create', name: 'new', methods: ['POST'])]
    public function new(Request $request): Response

    {
        $data = json_decode($request->getContent(),true);

        if (
            !$data ||
            !isset($data['idAllergene']) ||
            !isset($data['libelle'])
        ) {
           return $this->json(['message' => 'Données invalides'], 400);
        } 

        $pdo = getPDO();
        $sql = "INSERT INTO allergene(idAllergene, libelle)
                VALUES(:idAllergene, :libelle)";
        $stmt = $pdo->prepare($sql);
        // Insertion des données saisies dans la base de données
        $stmt->execute(['idAllergene' => $data['idAllergene'], 'libelle' => $data['libelle']]);

        // … Crée une nouvelle allergene et la sauvegarde en base de données
        return $this->json(['message' => 'Allergene créé', 'id' => $pdo->lastInsertId()],
        status: Response::HTTP_CREATED);  
    }

// CRUD : Lecture
    #[Route('/show/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): Response
    {
        $pdo = getPDO();
        $sql = "SELECT * FROM allergene WHERE idAllergene = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $allergene = $stmt->fetch();

        if (!$allergene) {
            return $this->json(['message' => 'Allergene introuvable'], 404);
        }

        // ... Affiche les détails de la allergene
        return $this->json(['message' => 'Allergene trouvé','allergene' => $allergene]);
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

        /*Vérifie si le allergene existe*/

        $checkSql = "SELECT * FROM allergene WHERE idAllergene = :id";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute(['id' => $id]);
        $allergene = $checkStmt->fetch();

        /*Si aucun allergene trouvée*/

        if (!$allergene) {
            return $this->json(['message' => 'Allergene introuvable'], 404);
        }

        /*Mise à jour*/

        $sql = "UPDATE allergene
                SET libelle = :libelle
                WHERE idAllergene = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['libelle' => $data['libelle'], 'id' => $id]);

        //Récupération du allergene mis à jour pour la réponse
        $updatedStmt = $pdo->prepare("SELECT * FROM allergene WHERE idAllergene = :id");
        $updatedStmt->execute(['id' => $id]);
        $updatedAllergene = $updatedStmt->fetch();
        
        // … Edite le allergene et le sauvegarde en base de données
        return $this->json(['message' => 'Allergene modifié','allergene' => $updatedAllergene]);
    }

// CRUD : Suppression
    #[Route('/delete/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): Response
    {
        $pdo = getPDO();

        /*Vérification*/

        $checkSql = "SELECT * FROM allergene
                     WHERE idAllergene = :id";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute(['id' => $id]);
        $allergene = $checkStmt->fetch();

        if (!$allergene) {
            return $this->json(['message' => 'Allergene introuvable'], 404);
        }

        /*Suppression*/

        $sql = "DELETE FROM allergene
                WHERE idAllergene = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $id]);

        // ... Supprime le allergene de la base de données
        return $this->json(['message' => 'Allergene supprimé'],Response::HTTP_OK);
    }
}