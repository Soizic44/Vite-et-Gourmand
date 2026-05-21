<?php
// src/Controller/AvisController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

require_once __DIR__ . '/../Database/connexion.php';

// création des routes CRUD pour la gestion des horaires
#[Route('/api/horaire', name: 'api_horaire_')]
class HoraireController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $pdo = getPDO();
        $sql = "SELECT * FROM horaire";
        $stmt = $pdo->query($sql);
        $horaire = $stmt->fetchAll();

        return $this->json(['horaire' => $horaire]);
    }

// CRUD : Création 
    #[Route('/create', name: 'new', methods: ['POST'])]
    public function new(Request $request): Response

    {
        $data = json_decode($request->getContent(),true);

        if (
            !$data ||
            !isset($data['idHoraire']) ||
            !isset($data['jour']) ||
            !isset($data['heure_ouverture']) ||
            !isset($data['heure_fermeture'])
        ) {
           return $this->json(['message' => 'Données invalides'], 400);
        } 

        $pdo = getPDO();
        $sql = "INSERT INTO horaire(idHoraire, jour, heure_ouverture, heure_fermeture)
                VALUES(:idHoraire, :jour, :heure_ouverture, :heure_fermeture)";
        $stmt = $pdo->prepare($sql);
        // Insertion des données saisies dans la base de données
        $stmt->execute(['idHoraire' => $data['idHoraire'], 'jour' => $data['jour'], 'heure_ouverture' => $data['heure_ouverture'], 'heure_fermeture' => $data['heure_fermeture']]);

        // … Crée une nouvelle horaire et la sauvegarde en base de données
        return $this->json(['message' => 'Horaire créé', 'id' => $pdo->lastInsertId()],
        status: Response::HTTP_CREATED);  
    }

// CRUD : Lecture
    #[Route('/show/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): Response
    {
        $pdo = getPDO();
        $sql = "SELECT * FROM horaire WHERE idHoraire = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $horaire = $stmt->fetch();

        if (!$horaire) {
            return $this->json(['message' => 'Horaire introuvable'], 404);
        }

        // ... Affiche les détails de la horaire
        return $this->json(['message' => 'Horaire trouvé','horaire' => $horaire]);
    }

// CRUD : Mise à jour / modification
    #[Route('/edit/{id}', name: 'edit', methods: ['PUT'])]
    public function edit(int $id, Request $request): Response
    {
        $data = json_decode($request->getContent(),true);

        if (
            !$data ||
            !isset($data['jour']) ||
            !isset($data['heure_ouverture']) ||
            !isset($data['heure_fermeture'])
        ) {
            return $this->json(['message' => 'Données invalides'], 400);
        }

        $pdo = getPDO();

        /*Vérifie si les horaires existe*/

        $checkSql = "SELECT * FROM horaire WHERE idHoraire = :id";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute(['id' => $id]);
        $horaire = $checkStmt->fetch();

        /*Si aucun horaire trouvée*/

        if (!$horaire) {
            return $this->json(['message' => 'Horaire introuvable'], 404);
        }

        /*Mise à jour*/

        $sql = "UPDATE horaire
                SET jour = :jour, heure_ouverture = :heure_ouverture, heure_fermeture = :heure_fermeture
                WHERE idHoraire = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['jour' => $data['jour'], 'heure_ouverture' => $data['heure_ouverture'], 'heure_fermeture' => $data['heure_fermeture'], 'id' => $id]);

        //Récupération du horaire mis à jour pour la réponse
        $updatedStmt = $pdo->prepare("SELECT * FROM horaire WHERE idHoraire = :id");
        $updatedStmt->execute(['id' => $id]);
        $updatedHoraire = $updatedStmt->fetch();
        
        // … Edite le horaire et le sauvegarde en base de données
        return $this->json(['message' => 'Horaire modifié','horaire' => $updatedHoraire]);
    }

// CRUD : Suppression
    #[Route('/delete/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): Response
    {
        $pdo = getPDO();

        /*Vérification*/

        $checkSql = "SELECT * FROM horaire
                     WHERE idHoraire = :id";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute(['id' => $id]);
        $horaire = $checkStmt->fetch();

        if (!$horaire) {
            return $this->json(['message' => 'Horaire introuvable'], 404);
        }

        /*Suppression*/

        $sql = "DELETE FROM horaire
                WHERE idHoraire = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $id]);

        // ... Supprime le horaire de la base de données
        return $this->json(['message' => 'Horaire supprimé'],Response::HTTP_OK);
    }
}