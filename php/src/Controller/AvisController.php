<?php
// src/Controller/AvisController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

require_once __DIR__ . '/../Database/connexion.php';

// création des routes CRUD pour la gestion des avis
#[Route('/api/avis', name: 'api_avis_')]
class AvisController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $pdo = getPDO();
        $sql = "SELECT * FROM avis";
        $stmt = $pdo->query($sql);
        $avis = $stmt->fetchAll();

        return $this->json(['avis' => $avis]);
    }

// CRUD : Création 
    #[Route('/create', name: 'new', methods: ['POST'])]
    public function new(Request $request): Response

    {
        $data = json_decode($request->getContent(),true);

        if (
            !$data ||
            !isset($data['note']) ||
            !isset($data['description']) ||
            !isset($data['statut'])
        ) {
           return $this->json(['message' => 'Données invalides'], 400);
        } 

        $pdo = getPDO();
        $sql = "INSERT INTO avis(note, description, statut)
                VALUES(:note, :description, :statut)";
        $stmt = $pdo->prepare($sql);
        // Insertion des données saisies dans la base de données
        $stmt->execute(['note' => $data['note'], 'description' => $data['description'], 'statut' => $data['statut']]);

        // … Crée une nouvelle avis et la sauvegarde en base de données
        return $this->json(['message' => 'Avis créé', 'id' => $pdo->lastInsertId()],
        status: Response::HTTP_CREATED);  
    }

// CRUD : Lecture
    #[Route('/show/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): Response
    {
        $pdo = getPDO();
        $sql = "SELECT * FROM avis WHERE idAvis = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $avis = $stmt->fetch();

        if (!$avis) {
            return $this->json(['message' => 'Avis introuvable'], 404);
        }

        // ... Affiche les détails de l'avis
        return $this->json(['message' => 'Avis trouvé','avis' => $avis]);
    }

// CRUD : Mise à jour / modification
    #[Route('/edit/{id}', name: 'edit', methods: ['PUT'])]
    public function edit(int $id, Request $request): Response
    {
        $data = json_decode($request->getContent(),true);

        if (
            !$data ||
            !isset($data['note']) ||
            !isset($data['description']) ||
            !isset($data['statut'])
        ) {
            return $this->json(['message' => 'Données invalides'], 400);
        }

        $pdo = getPDO();

        /*Vérifie si l'avis existe*/

        $checkSql = "SELECT * FROM avis
                    WHERE idAvis = :id";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute(['id' => $id]);
        $avis = $checkStmt->fetch();

        /*Si aucun avis trouvée*/

        if (!$avis) {
            return $this->json(['message' => 'Avis introuvable'], 404);
        }

        /*Mise à jour*/

        $sql = "UPDATE avis
                SET note = :note, description = :description, statut = :statut
                WHERE idAvis = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['note' => $data['note'], 'description' => $data['description'], 'statut' => $data['statut'], 'id' => $id]);

        //Récupération de l'avis mis à jour pour la réponse
        $updatedStmt = $pdo->prepare("SELECT * FROM avis WHERE idAvis = :id");
        $updatedStmt->execute(['id' => $id]);
        $updatedAvis = $updatedStmt->fetch();
        
        // … Edite l'avis et le sauvegarde en base de données
        return $this->json(['message' => 'Avis modifié','avis' => $updatedAvis]);
    }

// CRUD : Suppression
    #[Route('/delete/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): Response
    {
        $pdo = getPDO();

        /*Vérification*/

        $checkSql = "SELECT * FROM avis
                     WHERE idAvis = :id";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute(['id' => $id]);
        $avis = $checkStmt->fetch();

        if (!$avis) {
            return $this->json(['message' => 'Avis introuvable'], 404);
        }

        /*Suppression*/

        $sql = "DELETE FROM avis
                WHERE idAvis = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $id]);

        // ... Supprime l'avis de la base de données
        return $this->json(['message' => 'Avis supprimé'],Response::HTTP_OK);
    }
}