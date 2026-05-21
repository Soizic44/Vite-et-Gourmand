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
/*READ ALL avec GET /api/commande */    
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $pdo = getPDO();

        // Jointure SQL pour récupérer les commandes avec les détails du menu et de l'utilisateur
        $sql = "SELECT commande.numero_cde,
                commande.date_cde,
                commande.date_prestation,
                commande.heure_livraison,
                commande.prix_menu,
                commande.nbre_personne,
                commande.prix_livraison,
                commande.statut,
                commande.pret_materiel,
                commande.restitution_materiel,

                menu.idMenu,
                menu.titre AS menu,

                utilisateur.idUtilisateur,
                utilisateur.prenom,
                utilisateur.email

            FROM commande

            INNER JOIN menu
                ON commande.idMenu = menu.idMenu

            INNER JOIN utilisateur
                ON commande.idUtilisateur = utilisateur.idUtilisateur";

        $stmt = $pdo->query($sql);
        $commandes = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $this->json(['commandes' => $commandes]);
    }

/* CRUD POST - CREATE : Création de la commande avec POST /api/commande/create */
    #[Route('/create', name: 'new', methods: ['POST'])]
    public function new(Request $request): Response
    {
        $pdo = getPDO();

        // JSON de la requête
        $data = json_decode($request->getContent(), true);

        // Vérification JSON et des champs requis
        if (
            !$data ||
            !isset($data['numero_cde']) ||
            !isset($data['date_cde']) ||
            !isset($data['date_prestation']) ||
            !isset($data['heure_livraison']) ||
            !isset($data['prix_menu']) ||
            !isset($data['nbre_personne']) ||
            !isset($data['prix_livraison']) ||
            !isset($data['statut']) ||
            !isset($data['pret_materiel']) ||
            !isset($data['restitution_materiel']) ||
            !isset($data['idMenu']) ||
            !isset($data['idUtilisateur'])
        ) {
            return $this->json(['message' => 'Données invalides ou champs manquants'], 400);
        }

        // Vérification numérique
        if (
            !is_numeric($data['prix_menu']) ||
            !is_numeric($data['nbre_personne']) ||
            !is_numeric($data['prix_livraison']) ||
            !is_numeric($data['idMenu']) ||
            !is_numeric($data['idUtilisateur'])
        ) {
            return $this->json(['message' => 'Valeurs numériques invalides'], 400);
        }

        // Vérification doublon du numéro de commande
        $sqlCheck = "SELECT * FROM commande WHERE numero_cde = :numero_cde";
        $stmtCheck = $pdo->prepare($sqlCheck);
        $stmtCheck->execute(['numero_cde' => $data['numero_cde']]);
        $existingCommande = $stmtCheck->fetch();

        if ($existingCommande) {
            return $this->json(['message' => 'Numéro de commande déjà utilisé'], 409);
        }

         // Vérification utilisateur
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

        // Vérification menu 
        $sqlMenu = "SELECT * FROM menu
                    WHERE idMenu = :id";

        $stmtMenu = $pdo->prepare($sqlMenu);

        $stmtMenu->execute([
            'id' => $data['idMenu']
        ]);

        $menu = $stmtMenu->fetch(\PDO::FETCH_ASSOC);

        if (!$menu) {
            return $this->json(['message' => 'Menu inexistant'], 404);
        }

        // Vérification stock
        if ($menu['quantite_restante'] < $data['nbre_personne'] || $menu['quantite_restante'] <= 0) {
            return $this->json(['message' => 'Menu indisponible'], 400);
        }

        // Crétion de la commande
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

        // Optionnel : Mettre à jour la quantité restante du menu
        $sqlStock = "UPDATE menu
                          SET quantite_restante = quantite_restante - :nbre_personne
                          WHERE idMenu = :idMenu";
        $stmtStock = $pdo->prepare($sqlStock);
        $stmtStock->execute([
            'nbre_personne' => $data['nbre_personne'],
            'idMenu' => $data['idMenu']
        ]);
        return $this->json(['message' => 'Commande créée et stock mis à jour'],
        status: Response::HTTP_CREATED);
    }

/* READ ONE - CRUD : Lecture avec GET /api/commande/show/{numero} */
    #[Route('/show/{numero}', name: 'show', methods: ['GET'])]
    public function show($numero): Response
    {
        $pdo = getPDO();

        // Jointure SQL
        $sql = "SELECT commande.*, 
                menu.titre AS menu, 
                utilisateur.prenom, utilisateur.email

            FROM commande

            INNER JOIN menu
                ON commande.idMenu = menu.idMenu

            INNER JOIN utilisateur
                ON commande.idUtilisateur = utilisateur.idUtilisateur

            WHERE commande.numero_cde = :numero";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['numero' => $numero]);
        $commandes = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$commandes) {
            return $this->json(['message' => 'Commande introuvable'], 404);
        }

        // ... Affiche les détails de la commande
        return $this->json(['message' => 'Commande trouvée','commandes' => $commandes]);
    }

/* CRUD UPDATE Avec PUT /api/commande/edit/{numero} : Mise à jour / modification */
    #[Route('/edit/{numero}', name: 'edit', methods: ['PUT'])]
    public function edit($numero, Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        $pdo = getPDO();

        // si la commande existe
        $checkSql = "SELECT * FROM commande
                    WHERE numero_Cde = :numero";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute(['numero' => $numero]);
        $commande = $checkStmt->fetch(\PDO::FETCH_ASSOC);

        // Si aucune commande trouvée
        if (!$commande) {
            return $this->json(['message' => 'Commande introuvable'], 404);
        }

        // JSON 
        $data = json_decode($request->getContent(),true);

        // Validation JSON et des champs requis
        if (
            !$data ||
            !isset($data['date_cde']) ||
            !isset($data['date_prestation']) ||
            !isset($data['heure_livraison']) ||
            !isset($data['prix_menu']) ||
            !isset($data['nbre_personne']) ||
            !isset($data['prix_livraison']) ||
            !isset($data['statut']) ||
            !isset($data['pret_materiel']) ||
            !isset($data['restitution_materiel'])
        ) {
            return $this->json(['message' => 'Données JSON invalides'], 400);
        }

        // Mise à jour de la commande
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

// CRUD DELETE /api/commande/delete/{numero} : Suppression
    #[Route('/delete/{numero}', name: 'delete', methods: ['DELETE'])]
    public function delete($numero): Response
    {
        $pdo = getPDO();

        //Vérification de l'existence de la commande

        $checkSql = "SELECT * FROM commande
                     WHERE numero_Cde = :numero";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute(['numero' => $numero]);
        $commande = $checkStmt->fetch(\PDO::FETCH_ASSOC);

        if (!$commande) {
            return $this->json(['message' => 'Commande introuvable'], 404);
        }

        //Suppression

        $sql = "DELETE FROM commande
                WHERE numero_Cde = :numero";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['numero' => $numero]);

        // ... Supprime la commande de la base de données
        return $this->json(['message' => 'Commande supprimée'], Response::HTTP_OK);
    }
}



