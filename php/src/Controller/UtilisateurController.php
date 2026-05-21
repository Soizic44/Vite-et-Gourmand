<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

require_once __DIR__ . '/../Database/connexion.php';

// création des routes CRUD pour la gestion des utilisateurs
#[Route('/api/utilisateur', name: 'api_utilisateur_')]
class UtilisateurController extends AbstractController
{
    /* READ ALL avecGET /api/utilisateur */
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $pdo = getPDO();

        $sql = "SELECT * FROM utilisateur";
        $stmt = $pdo->query($sql);
        $utilisateurs = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $this->json($utilisateurs);
    }

    /* READ ONE avec GET /api/utilisateur/{id}*/
    #[Route('/show/{id}', name: 'show', methods: ['GET'])]
    public function show($id): Response
    {
        $pdo = getPDO();

        $sql = "SELECT * FROM utilisateur
                WHERE idUtilisateur = :id";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            'id' => $id
        ]);

        $utilisateur = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$utilisateur) {
            return $this->json([
                'message' => 'Utilisateur introuvable'
            ], 404);
        }

        return $this->json($utilisateur);
    }

    /* CREATE avec POST /api/utilisateur */
    #[Route('/create', name: 'create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        $pdo = getPDO();

        $data = json_decode($request->getContent(), true);

        $passwordHash = password_hash(
            $data['password'],
            PASSWORD_DEFAULT
        );

        $sql = "INSERT INTO utilisateur
        (email, password, prenom, telephone, ville, pays, adresse_postale, idAvis, idRole)
        VALUES
        (:email, :password, :prenom, :telephone, :ville, :pays, :adresse, :avis, :role)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'email' => $data['email'],
            'password' => $passwordHash,
            'prenom' => $data['prenom'],
            'telephone' => $data['telephone'],
            'ville' => $data['ville'],
            'pays' => $data['pays'],
            'adresse' => $data['adresse_postale'],
            'avis' => $data['idAvis'],
            'role' => $data['idRole']
        ]);

        return $this->json([
            'message' => 'Utilisateur créé'
        ], 201);
    }

    /* UPDATE avec PUT /api/utilisateur/{id} */
    #[Route('/update/{id}', name: 'update', methods: ['PUT'])]
    public function update($id, Request $request): Response
    {
        $pdo = getPDO();

        // Vérifier si l'utilisateur existe
        $checkSql = "SELECT * FROM utilisateur
                    WHERE idUtilisateur = :id";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute(['id' => $id]);
        $utilisateur = $checkStmt->fetch(\PDO::FETCH_ASSOC);

        if (!$utilisateur) {
            return $this->json([
                'message' => 'Utilisateur introuvable'
            ], 404);
        }

        // Mise à jour de l'utilisateur
        $data = json_decode($request->getContent(), true);

        $sql = "UPDATE utilisateur
                SET
                    email = :email,
                    prenom = :prenom,
                    telephone = :telephone,
                    ville = :ville,
                    pays = :pays,
                    adresse_postale = :adresse
                WHERE idUtilisateur = :id";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            'email' => $data['email'],
            'prenom' => $data['prenom'],
            'telephone' => $data['telephone'],
            'ville' => $data['ville'],
            'pays' => $data['pays'],
            'adresse' => $data['adresse_postale'],
            'id' => $id
        ]);

        // Récupérer l'utilisateur mis à jour pour la réponse
        $stmt = $pdo->prepare("SELECT * FROM utilisateur WHERE idUtilisateur = :id");
        $stmt->execute(['id' => $id]);
        $updatedUtilisateur = $stmt->fetch(\PDO::FETCH_ASSOC);

        // ... Edite l'utilisateur et le sauvegarde en base de données
        return $this->json([
            'message' => 'Utilisateur modifié',
            'utilisateur' => $updatedUtilisateur]);
    }

    /* Supprimer avec DELETE /api/utilisateur/{id} */
    #[Route('/delete/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete($id): Response
    {
        $pdo = getPDO();
        // Vérifier si l'utilisateur existe
        $checkSql = "SELECT * FROM utilisateur
                    WHERE idUtilisateur = :id";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute(['id' => $id]);
        $utilisateur = $checkStmt->fetch(\PDO::FETCH_ASSOC);

        if (!$utilisateur) {
            return $this->json([
                'message' => 'Utilisateur introuvable'
            ], 404);
        }

        $sql = "DELETE FROM utilisateur
                WHERE idUtilisateur = :id";

        $stmt = $pdo->prepare($sql);

        $stmt->execute(['id' => $id]);

        return $this->json(['message' => 'Utilisateur supprimé'], Response::HTTP_OK);
    }
}
