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

// CRUD POST : Création de la commande
    #[Route('/create', name: 'new', methods: ['POST'])]
    public function new(Request $request): Response
    {
        $pdo = getPDO();
        $data = json_decode($request->getContent(), true);

         /* Vérification utilisateur */
        $sqlUtilisateur = "SELECT * FROM utilisateur
                           WHERE idUtilisateur = :id";

        $stmtUtilisateur = $pdo->prepare($sqlUtilisateur);

        $stmtUtilisateur->execute([
            'id' => $data['idUtilisateur']
        ]);

        $utilisateur = $stmtUtilisateur->fetch();

        if (!$utilisateur) {
            return $this->json([
                'message' => 'Utilisateur inexistant'], 404);
        }

        /* Vérification */
        $sqlMenu = "SELECT * FROM menu
                    WHERE idMenu = :id";

        $stmtMenu = $pdo->prepare($sqlMenu);

        $stmtMenu->execute([
            'id' => $data['idMenu']
        ]);

        $menu = $stmtMenu->fetch();

        if (!$menu) {
            return $this->json(['message' => 'Menu inexistant'], 404);
        }

        /* Crétion de la commande */
        $sql = "INSERT INTO commande(numero_cde,date_cde, date_prestation, heure_livraison,
                prix_menu, nbre_personne, prix_livraison, statut, 
                pret_materiel, restitution_materiel, idMenu, idUtilisateur)
                VALUES(:numero_cde, :date_cde, :date_prestation, :heure_livraison, 
                :prix_menu, :nbre_personne, :prix_livraison, :statut, 
                :pret_materiel, :restitution_materiel, :idMenu, :idUtilisateur)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'numero_cde' => $data['numero_cde'],
            'date_cde' => $data['date_cde'],
            'date_prestation' => $data['date_prestation'],
            'heure_livraison' => $data['heure_livraison'],
            'prix_menu' => $data['prix_menu'],
            'nbre_personne' => $data['nbre_personne'],
            'prix_livraison' => $data['prix_livraison'],
            'statut' => $data['statut'],
            'pret_materiel' => $data['pret_materiel'],
            'restitution_materiel' => $data['restitution_materiel'],
            'idMenu' => $data['idMenu'],
            'idUtilisateur' => $data['idUtilisateur']
        ]);

        // … Crée une nouvelle commande et la sauvegarde en base de données
        return $this->json(['message' => 'Commande créée'], 
        status: Response::HTTP_CREATED);
    }

// CRUD : Lecture
    #[Route('/show/{numero}', name: 'show', methods: ['GET'])]
    public function show($numero): Response
    {
        $pdo = getPDO();
        $sql = "SELECT * FROM commande WHERE numero_Cde = :numero";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['numero' => $numero]);
        $commandes = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$commandes) {
            return $this->json(['message' => 'Commande introuvable'], 404);
        }

        // ... Affiche les détails de la commande
        return $this->json(['message' => 'Commande trouvée','commandes' => $commandes]);
    }

// CRUD : Mise à jour / modification
    #[Route('/edit/{numero}', name: 'edit', methods: ['PUT'])]
    public function edit($numero, Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        $pdo = getPDO();

        /*Vérifie si la commande existe*/

        $checkSql = "SELECT * FROM commande
                    WHERE numero_Cde = :numero";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute(['numero' => $numero]);
        $commande = $checkStmt->fetch(\PDO::FETCH_ASSOC);

        /*Si aucune commande trouvée*/

        if (!$commande) {
            return $this->json(['message' => 'Commande introuvable'], 404);
        }

        /*Mise à jour*/
        $data = json_decode($request->getContent(), true);

        $sql = "UPDATE commande
                SET date_cde = :date_cde, date_prestation = :date_prestation,
                    heure_livraison = :heure_livraison, prix_menu = :prix_menu,
                    nbre_personne = :nbre_personne, prix_livraison = :prix_livraison,
                    statut = :statut, pret_materiel = :pret_materiel, restitution_materiel = :restitution_materiel
                WHERE numero_Cde = :numero";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'date_cde' => $data['date_cde'],
            'date_prestation' => $data['date_prestation'],
            'heure_livraison' => $data['heure_livraison'],
            'prix_menu' => $data['prix_menu'],
            'nbre_personne' => $data['nbre_personne'],
            'prix_livraison' => $data['prix_livraison'],
            'statut' => $data['statut'],
            'pret_materiel' => $data['pret_materiel'],
            'restitution_materiel' => $data['restitution_materiel'],
            'numero' => $numero
        ]);

        // Récupérer la commande mise à jour pour la réponse
        $stmt = $pdo->prepare("SELECT * FROM commande WHERE numero_Cde = :numero");
        $stmt->execute(['numero' => $numero]);
        $updateCommande = $stmt->fetch(\PDO::FETCH_ASSOC);

        // … Edite la commande et le sauvegarde en base de données
        return $this->json(['message' => 'Commande modifiée','commandes' => $updateCommande]);
    }

// CRUD : Suppression
    #[Route('/delete/{numero}', name: 'delete', methods: ['DELETE'])]
    public function delete($numero): Response
    {
        $pdo = getPDO();

        /*Vérification*/

        $checkSql = "SELECT * FROM commande
                     WHERE numero_Cde = :numero";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute(['numero' => $numero]);
        $commande = $checkStmt->fetch(\PDO::FETCH_ASSOC);

        if (!$commande) {
            return $this->json(['message' => 'Commande introuvable'], 404);
        }

        /*Suppression*/

        $sql = "DELETE FROM commande
                WHERE numero_Cde = :numero";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['numero' => $numero]);

        // ... Supprime la commande de la base de données
        return $this->json(['message' => 'Commande supprimée'], Response::HTTP_OK);
    }
}



