<?php
// src/Controller/AvisController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

require_once __DIR__ . '/../Database/connexion.php';

// création des routes CRUD pour la gestion des roles
#[Route('/api/role', name: 'api_role_')]
class RoleController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $pdo = getPDO();
        $sql = "SELECT * FROM role";
        $stmt = $pdo->query($sql);
        $role = $stmt->fetchAll();

        return $this->json(['role' => $role]);
    }

// CRUD : Création 
    #[Route('/create', name: 'new', methods: ['POST'])]
    public function new(Request $request): Response

    {
        $data = json_decode($request->getContent(),true);

        if (
            !$data ||
            !isset($data['idRole']) ||
            !isset($data['libelle'])
        ) {
           return $this->json(['message' => 'Données invalides'], 400);
        } 

        $pdo = getPDO();
        $sql = "INSERT INTO role(idRole, libelle)
                VALUES(:idRole, :libelle)";
        $stmt = $pdo->prepare($sql);
        // Insertion des données saisies dans la base de données
        $stmt->execute(['idRole' => $data['idRole'], 'libelle' => $data['libelle']]);

        // … Crée une nouvelle role et la sauvegarde en base de données
        return $this->json(['message' => 'Role créé', 'id' => $pdo->lastInsertId()],
        status: Response::HTTP_CREATED);  
    }

// CRUD : Lecture
    #[Route('/show/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): Response
    {
        $pdo = getPDO();
        $sql = "SELECT * FROM role WHERE idRole = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $role = $stmt->fetch();

        if (!$role) {
            return $this->json(['message' => 'Role introuvable'], 404);
        }

        // ... Affiche les détails du role
        return $this->json(['message' => 'Role trouvé','role' => $role]);
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

        /*Vérifie si le role existe*/

        $checkSql = "SELECT * FROM role WHERE idRole = :id";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute(['id' => $id]);
        $role = $checkStmt->fetch();

        /*Si aucun role trouvée*/

        if (!$role) {
            return $this->json(['message' => 'Role introuvable'], 404);
        }

        /*Mise à jour*/

        $sql = "UPDATE role
                SET libelle = :libelle
                WHERE idRole = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['libelle' => $data['libelle'], 'id' => $id]);

        //Récupération du role mis à jour pour la réponse
        $updatedStmt = $pdo->prepare("SELECT * FROM role WHERE idRole = :id");
        $updatedStmt->execute(['id' => $id]);
        $updatedRole = $updatedStmt->fetch();
        
        // … Edite le role et le sauvegarde en base de données
        return $this->json(['message' => 'Role modifié','role' => $updatedRole]);
    }

// CRUD : Suppression
    #[Route('/delete/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): Response
    {
        $pdo = getPDO();

        /*Vérification*/

        $checkSql = "SELECT * FROM role
                     WHERE idRole = :id";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute(['id' => $id]);
        $role = $checkStmt->fetch();

        if (!$role) {
            return $this->json(['message' => 'Role introuvable'], 404);
        }

        /*Suppression*/

        $sql = "DELETE FROM role
                WHERE idRole = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $id]);

        // ... Supprime le role de la base de données
        return $this->json(['message' => 'Role supprimé'],Response::HTTP_OK);
    }
}