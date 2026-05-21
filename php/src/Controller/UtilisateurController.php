<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

require_once __DIR__ . '/../Database/connexion.php';

// création des routes CRUD pour la gestion des utilisateurs
#[IsGranted('ROLE_ADMIN')]
#[Route('/api/utilisateur', name: 'api_utilisateur_')]
class UtilisateurController extends AbstractController
{
    /* READ ALL avec GET /api/utilisateur */
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $pdo = getPDO();

        // Jointures SQL
        $sql = "SELECT utilisateur.idUtilisateur,
                utilisateur.email,
                utilisateur.prenom,
                utilisateur.telephone,
                utilisateur.ville,
                utilisateur.pays,
                utilisateur.adresse_postale,

                role.idRole,
                role.libelle AS role,

                avis.idAvis,
                avis.note,
                avis.description,
                avis.statut

            FROM utilisateur

            INNER JOIN role
                ON utilisateur.idRole = role.idRole

            INNER JOIN avis
                ON utilisateur.idAvis = avis.idAvis";

        $stmt = $pdo->query($sql);
        $utilisateurs = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $this->json($utilisateurs);
    }

    /* READ ONE avec GET /api/utilisateur/{id}*/
    #[Route('/show/{id}', name: 'show', methods: ['GET'])]
    public function show($id): Response
    {
        // Validation de l'ID
        if (!is_numeric($id) || $id <= 0) {
            return $this->json(['message' => 'ID invalide'], 400);
        }

        $pdo = getPDO();

        // Jointures SQL
        $sql = "SELECT utilisateur.idUtilisateur,
                utilisateur.email,
                utilisateur.prenom,
                utilisateur.telephone,
                utilisateur.ville,
                utilisateur.pays,
                utilisateur.adresse_postale,

                role.idRole,
                role.libelle AS role,

                avis.idAvis,
                avis.note,
                avis.description,
                avis.statut

            FROM utilisateur

            INNER JOIN role
                ON utilisateur.idRole = role.idRole

            INNER JOIN avis
                ON utilisateur.idAvis = avis.idAvis

            WHERE utilisateur.idUtilisateur = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'id' => $id
        ]);

        $utilisateur = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$utilisateur) {
            return $this->json(['message' => 'Utilisateur introuvable'], 404);
        }

        return $this->json(['utilisateur' => $utilisateur]);
    }

    /* CREATE avec POST /api/utilisateur */
     // Prtotection CRUD avec IsGranted
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/create', name: 'create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        $pdo = getPDO();

        // JSON 
        $data = json_decode($request->getContent(), true);

        // Validation JSON
        if (!isset($data['email'], $data['password'], $data['prenom'], 
        $data['telephone'], $data['ville'], $data['pays'], $data['adresse_postale'], 
        $data['idAvis'], $data['idRole'])) {

            return $this->json(['message' => 'Données manquantes'], 400);
        }

        // Validation email
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return $this->json(['message' => 'Email invalide'], 400);
        }

        // Validation téléphone
        if (!preg_match('/^[0-9+\s.-]{8,20}$/', $data['telephone'])) {
            return $this->json(['message' => 'Numéro de téléphone invalide'], 400);
        }

        // Validation password
        if (strlen($data['password']) < 8) {
            return $this->json(
                ['message' => 'Le mot de passe doit contenir au moins 8 caractères'], 400);
        }

        // Vérification de l'unicité de l'email
        $checkSql = "SELECT * FROM utilisateur
                    WHERE email = :email";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute(['email' => $data['email']]);
        $existingUser = $checkStmt->fetch(\PDO::FETCH_ASSOC);

        if ($existingUser) {
            return $this->json(
                ['message' => 'Un utilisateur avec cet email existe déjà'], 400);
        }

        // Vérification role
        $checkRoleSql = "SELECT * FROM role
                        WHERE idRole = :idRole";
        $checkRoleStmt = $pdo->prepare($checkRoleSql);
        $checkRoleStmt->execute(['idRole' => $data['idRole']]);
        $existingRole = $checkRoleStmt->fetch(\PDO::FETCH_ASSOC);

        if (!$existingRole) {
            return $this->json(
                ['message' => 'Le rôle spécifié n\'existe pas'], 400);
        }

        // Vérification avis
        $checkAvisSql = "SELECT * FROM avis
                        WHERE idAvis = :idAvis";
        $checkAvisStmt = $pdo->prepare($checkAvisSql);
        $checkAvisStmt->execute(['idAvis' => $data['idAvis']]);
        $existingAvis = $checkAvisStmt->fetch(\PDO::FETCH_ASSOC);

        if (!$existingAvis) {
            return $this->json(
                ['message' => 'L\'avis spécifié n\'existe pas'], 400);
        }

        // Hash du mot de passe
        $passwordHash = password_hash(
            $data['password'],
            PASSWORD_DEFAULT
        );

        // Insertion en base de données
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

        return $this->json(['message' => 'Utilisateur créé'], 201);
    }

    /* UPDATE avec PUT /api/utilisateur/{id} */
    // Prtotection CRUD avec IsGranted
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/update/{id}', name: 'update', methods: ['PUT'])]
    public function update($id, Request $request): Response
    {
        
        // Validation ID
        if (!is_numeric($id) || $id <= 0) {
            return $this->json(['message' => 'ID invalide'], 400);
        }

        $pdo = getPDO();

        // Vérifier si l'utilisateur existe
        $checkSql = "SELECT * FROM utilisateur
                    WHERE idUtilisateur = :id";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute(['id' => $id]);
        $utilisateur = $checkStmt->fetch(\PDO::FETCH_ASSOC);

        if (!$utilisateur) {
            return $this->json(['message' => 'Utilisateur introuvable'], 404);
        }

        // JSON
        $data = json_decode($request->getContent(), true);

        // Validation JSON
        if (!isset($data['email'], $data['prenom'], 
        $data['telephone'], $data['ville'], $data['pays'], $data['adresse_postale'])) {

            return $this->json(['message' => 'Données manquantes'], 400);
        }

        // Validation email
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return $this->json(['message' => 'Email invalide'], 400);
        }

        // Validation téléphone
        if (!preg_match('/^[0-9+\s.-]{8,20}$/', $data['telephone'])) {
            return $this->json(['message' => 'Numéro de téléphone invalide'], 400);
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
        // Prtotection CRUD avec IsGranted
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/delete/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete($id): Response
    {

        // Validation ID
        if (!is_numeric($id) || $id <= 0) {
            return $this->json(['message' => 'ID invalide'], 400);
        }

        $pdo = getPDO();

        // Vérifier si l'utilisateur existe
        $checkSql = "SELECT * FROM utilisateur
                    WHERE idUtilisateur = :id";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute(['id' => $id]);
        $utilisateur = $checkStmt->fetch(\PDO::FETCH_ASSOC);

        if (!$utilisateur) {
            return $this->json(['message' => 'Utilisateur introuvable'], 404);
        }

        // Suppression de l'utilisateur
        $sql = "DELETE FROM utilisateur
                WHERE idUtilisateur = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $id]);

        return $this->json(['message' => 'Utilisateur supprimé'], Response::HTTP_OK);
    }
}
