<?php
// src/Controller/AvisController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

require_once __DIR__ . '/../Database/connexion.php';

// création des routes CRUD pour la gestion des régimes
#[Route('/api/regime', name: 'api_regime_')]
class RegimeController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $pdo = getPDO();
        $sql = "SELECT * FROM regime";
        $stmt = $pdo->query($sql);
        $regime = $stmt->fetchAll();

        return $this->json(['regime' => $regime]);
    }

// CRUD : Création 
    #[Route('/create', name: 'new', methods: ['POST'])]
    public function new(Request $request): Response

    {
        $data = json_decode($request->getContent(),true);

        if (
            !$data ||
            !isset($data['idRegime']) ||
            !isset($data['libelle'])
        ) {
           return $this->json(['message' => 'Données invalides'], 400);
        } 

        $pdo = getPDO();
        $sql = "INSERT INTO regime(idRegime, libelle)
                VALUES(:idRegime, :libelle)";
        $stmt = $pdo->prepare($sql);
        // Insertion des données saisies dans la base de données
        $stmt->execute(['idRegime' => $data['idRegime'], 'libelle' => $data['libelle']]);

        // … Crée une nouvelle regime et la sauvegarde en base de données
        return $this->json(['message' => 'Regime créé', 'id' => $pdo->lastInsertId()],
        status: Response::HTTP_CREATED);  
    }

// CRUD : Lecture
    #[Route('/show/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): Response
    {
        $pdo = getPDO();
        $sql = "SELECT * FROM regime WHERE idRegime = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $regime = $stmt->fetch();

        if (!$regime) {
            return $this->json(['message' => 'Regime introuvable'], 404);
        }

        // ... Affiche les détails de la regime
        return $this->json(['message' => 'Regime trouvé','regime' => $regime]);
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

        /*Vérifie si le régime existe*/

        $checkSql = "SELECT * FROM regime WHERE idRegime = :id";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute(['id' => $id]);
        $regime = $checkStmt->fetch();

        /*Si aucun régime trouvée*/

        if (!$regime) {
            return $this->json(['message' => 'Regime introuvable'], 404);
        }

        /*Mise à jour*/

        $sql = "UPDATE regime
                SET libelle = :libelle
                WHERE idRegime = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['libelle' => $data['libelle'], 'id' => $id]);

        //Récupération du régime mis à jour pour la réponse
        $updatedStmt = $pdo->prepare("SELECT * FROM regime WHERE idRegime = :id");
        $updatedStmt->execute(['id' => $id]);
        $updatedRegime = $updatedStmt->fetch();
        
        // … Edite le régime et le sauvegarde en base de données
        return $this->json(['message' => 'Regime modifié','regime' => $updatedRegime]);
    }

// CRUD : Suppression
    #[Route('/delete/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): Response
    {
        $pdo = getPDO();

        /*Vérification*/

        $checkSql = "SELECT * FROM regime
                     WHERE idRegime = :id";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute(['id' => $id]);
        $regime = $checkStmt->fetch();

        if (!$regime) {
            return $this->json(['message' => 'Regime introuvable'], 404);
        }

        /*Suppression*/

        $sql = "DELETE FROM regime
                WHERE idRegime = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $id]);

        // ... Supprime le régime de la base de données
        return $this->json(['message' => 'Regime supprimé'],Response::HTTP_OK);
    }
}