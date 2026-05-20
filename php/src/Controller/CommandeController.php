<?php
// src/Controller/CommandeController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

require_once __DIR__ . '/../Database/connexion.php';

// création des routes CRUD pour la gestion des commandes
#[Route('/api/commande', name: 'api_commande_')]
class CommandeController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $pdo = getPDO();
        $sql = "SELECT * FROM commande";
        $stmt = $pdo->query($sql);
        $commandes = $stmt->fetchAll();

        return $this->json(['commandes' => $commandes]);
    }

// CRUD : Création 
    #[Route('/create', name: 'new', methods: ['POST'])]
    public function new(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        if (
            !$data ||
            !isset($data['date_cde']) ||
            !isset($data['date_prestation']) ||
            !isset($data['heure_livraison']) ||
            !isset($data['prix_menu']) ||
            !isset($data['nbre_personnes']) ||
            !isset($data['prix_livraison']) ||
            !isset($data['statut']) ||
            !isset($data['pret_materiel']) ||
            !isset($data['restitution_materiel']) ||
            !isset($data['idMenu']) ||
            !isset($data['idUtilisateur'])
        ) {
           return $this->json(['message' => 'Données invalides'], 400);
        }

        $pdo = getPDO();
        $sql = "INSERT INTO commande(date_prestation, heure_livraison,
                prix_menu, nbre_personnes, prix_livraison, statut, 
                pret_materiel, restitution_materiel, idMenu, idUtilisateur)
                VALUES(:date_prestation, :heure_livraison, 
                :prix_menu, :nbre_personnes, :prix_livraison, :statut, 
                :pret_materiel, :restitution_materiel, :idMenu, :idUtilisateur)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'date_cde' => $data['date_cde'],
            'date_prestation' => $data['date_prestation'],
            'heure_livraison' => $data['heure_livraison'],
            'prix_menu' => $data['prix_menu'],
            'nbre_personnes' => $data['nbre_personnes'],
            'prix_livraison' => $data['prix_livraison'],
            'statut' => $data['statut'],
            'pret_materiel' => $data['pret_materiel'],
            'restitution_materiel' => $data['restitution_materiel'],
            'idMenu' => $data['idMenu'],
            'idUtilisateur' => $data['idUtilisateur']
        ]);

        // … Crée une nouvelle commande et la sauvegarde en base de données
        
        return $this->json(['message' => 'Commande créée', 'id' => $pdo->lastInsertId()],
        statut: Response::HTTP_CREATED);  
    }

// CRUD : Lecture
    #[Route('/show/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): Response
    {
        $pdo = getPDO();
        $sql = "SELECT * FROM commande WHERE numero_Cde = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $commandes = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if (!$commandes) {
            return $this->json(['message' => 'Commande introuvable'], 404);
        }

        // ... Affiche les détails de la commande
        return $this->json(['message' => 'Commande trouvée','commandes' => $commandes]);
    }

// CRUD : Mise à jour / modification
    #[Route('/edit/{id}', name: 'edit', methods: ['PUT'])]
    public function edit(int $id): Response
    {
        $data = json_decode(file_get_contents('php://input'), true);

        $pdo = getPDO();

        /*Vérifie si la commande existe*/

        $checkSql = "SELECT * FROM commande
                    WHERE numero_Cde = :id";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute(['id' => $id]);
        $commande = $checkStmt->fetch();

        /*Si aucune commande trouvée*/

        if (!$commande) {
            return $this->json(['message' => 'Commande introuvable'], 404);
        }

        /*Mise à jour*/

        $sql = "UPDATE commande
                SET date_cde = :date_cde, date_prestation = :date_prestation,
                    heure_livraison = :heure_livraison, prix_menu = :prix_menu,
                    nbre_personnes = :nbre_personnes, prix_livraison = :prix_livraison,
                    statut = :statut, pret_materiel = :pret_materiel, restitution_materiel = :restitution_materiel,
                    idMenu = :idMenu, idUtilisateur = :idUtilisateur
                WHERE numero_Cde = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'date_cde' => $data['date_cde'],
            'date_prestation' => $data['date_prestation'],
            'heure_livraison' => $data['heure_livraison'],
            'prix_menu' => $data['prix_menu'],
            'nbre_personnes' => $data['nbre_personnes'],
            'prix_livraison' => $data['prix_livraison'],
            'statut' => $data['statut'],
            'pret_materiel' => $data['pret_materiel'],
            'restitution_materiel' => $data['restitution_materiel'],
            'idMenu' => $data['idMenu'],
            'idUtilisateur' => $data['idUtilisateur'],
            'id' => $id
        ]);

        // Récupérer la commande mise à jour pour la réponse
        $stmt = $pdo->prepare("SELECT * FROM commande WHERE numero_Cde = :id");
        $stmt->execute(['id' => $id]);
        $updateCommande = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // … Edite la commande et le sauvegarde en base de données
        return $this->json(['message' => 'Commande modifiée','commandes' => $updateCommande]);
    }

// CRUD : Suppression
    #[Route('/delete/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): Response
    {
        $pdo = getPDO();

        /*Vérification*/

        $checkSql = "SELECT * FROM commande
                     WHERE numero_Cde = :id";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute(['id' => $id]);
        $commande = $checkStmt->fetch(\PDO::FETCH_ASSOC);

        if (!$commande) {
            return $this->json(['message' => 'Commande introuvable'], 404);
        }

        /*Suppression*/

        $sql = "DELETE FROM commande
                WHERE numero_Cde = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $id]);

        // ... Supprime la commande de la base de données
        return $this->json(['message' => 'Commande supprimée'], Response::HTTP_OK);
    }
}



