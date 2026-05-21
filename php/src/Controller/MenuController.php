<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

require_once __DIR__ . '/../Database/connexion.php';

// création des routes CRUD pour la gestion des menus
#[Route('/api/menu', name: 'api_menu_')]
class MenuController extends AbstractController
{
/*READ ALL avec GET /api/menu*/
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $pdo = getPDO();

        // Jointures SQL pour récupérer les menus avec leurs thèmes et régimes 
        $sql = "SELECT
                menu.idMenu,
                menu.titre,
                menu.nbre_personne_min,
                menu.prix_personne,
                menu.description,
                menu.quantite_restante,

                theme.idTheme,
                theme.libelle AS theme,

                regime.idRegime,
                regime.libelle AS regime

            FROM menu

            INNER JOIN theme
                ON menu.idTheme = theme.idTheme

            INNER JOIN regime
                ON menu.idRegime = regime.idRegime";
            

        $stmt = $pdo->query($sql);
        $menus = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $this->json(['menus' => $menus]);
    }

/*READ ONE avec GET /api/menu/{id}*/
    #[Route('/show/{id}', name: 'show', methods: ['GET'])]
    public function show($id): Response
    {
        // validation de l'id
        if (!is_numeric($id) || $id <= 0) {
            return $this->json(['message' => 'ID invalide'], 400);
        }

        $pdo = getPDO();

        // Jointures SQL pour récupérer le menu avec son thème et régime
        $sql = "SELECT
                    menu.idMenu,
                    menu.titre,
                    menu.nbre_personne_min,
                    menu.prix_personne,
                    menu.description,
                    menu.quantite_restante,

                    theme.idTheme,
                    theme.libelle AS theme,

                    regime.idRegime,
                    regime.libelle AS regime

                FROM menu

                INNER JOIN theme
                    ON menu.idTheme = theme.idTheme

                INNER JOIN regime
                    ON menu.idRegime = regime.idRegime

                WHERE menu.idMenu = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $menu = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$menu) {
            return $this->json(['message' => 'Menu introuvable'], 404);
        }
        // ... Affiche les détails du menu
        return $this->json(['message' => 'Menu trouvé','menu' => $menu]);
    }

/*CREATE avec POST /api/menu*/
    #[Route('/create', name: 'create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        $pdo = getPDO();

        // Récupération JSON
        $data = json_decode($request->getContent(), true);

        // Validation des données
        if (
            !$data ||
            !isset($data['titre']) ||
            !isset($data['nbre_personne_min']) ||
            !isset($data['prix_personne']) ||
            !isset($data['regime']) ||
            !isset($data['description']) ||
            !isset($data['quantite_restante']) ||
            !isset($data['idTheme']) ||
            !isset($data['idRegime'])
        ){
            return $this->json(['message' => 'Données invalides'], 400);
        }
        // Validation numérique
        if (!is_numeric($data['nbre_personne_min']) || $data['nbre_personne_min'] <= 0) {
            return $this->json(['message' => 'Nombre de personnes minimum invalide'], 400);
        }
        if (!is_numeric($data['prix_personne']) || $data['prix_personne'] <= 0) {
            return $this->json(['message' => 'Prix par personne invalide'], 400);
        }
        if (!is_numeric($data['quantite_restante']) || $data['quantite_restante'] < 0) {
            return $this->json(['message' => 'Quantité restante invalide'], 400);
        }
        if (!is_numeric($data['idTheme']) || $data['idTheme'] <= 0) {
            return $this->json(['message' => 'ID du thème invalide'], 400);
        }
        if (!is_numeric($data['idRegime']) || $data['idRegime'] <= 0) {
            return $this->json(['message' => 'ID du régime invalide'], 400);
        }

        // Vérification thème
        $themeCheckSql = "SELECT * FROM theme WHERE idTheme = :idTheme";
        $themeCheckStmt = $pdo->prepare($themeCheckSql);
        $themeCheckStmt->execute(['idTheme' => $data['idTheme']]);
        $theme = $themeCheckStmt->fetch(\PDO::FETCH_ASSOC);

        if (!$theme) {
            return $this->json(['message' => 'Thème introuvable'], 404);
        }

        // Vérification régime
        $regimeCheckSql = "SELECT * FROM regime WHERE idRegime = :idRegime";
        $regimeCheckStmt = $pdo->prepare($regimeCheckSql);
        $regimeCheckStmt->execute(['idRegime' => $data['idRegime']]);
        $regime = $regimeCheckStmt->fetch(\PDO::FETCH_ASSOC);

        if (!$regime) {
            return $this->json(['message' => 'Régime introuvable'], 404);
        }

        // Vérification doublon
        $duplicateCheckSql = "SELECT * FROM menu WHERE titre = :titre AND idTheme = :idTheme AND idRegime = :idRegime";
        $duplicateCheckStmt = $pdo->prepare($duplicateCheckSql);
        $duplicateCheckStmt->execute([
            'titre' => $data['titre'],
            'idTheme' => $data['idTheme'],
            'idRegime' => $data['idRegime']
        ]);
        $duplicate = $duplicateCheckStmt->fetch(\PDO::FETCH_ASSOC);

        if ($duplicate) {
            return $this->json(['message' => 'Menu déjà existant'], 409);
        }

        // Insert
        $sql = "INSERT INTO menu
        (titre, nbre_personne_min, prix_personne, regime, description, quantite_restante, idTheme, idRegime)
        VALUES
        (:titre, :nbre, :prix, :regime, :description, :quantite, :theme, :regimeId)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'titre' => $data['titre'],
            'nbre' => $data['nbre_personne_min'],
            'prix' => $data['prix_personne'],
            'regime' => $data['regime'],
            'description' => $data['description'],
            'quantite' => $data['quantite_restante'],
            'theme' => $data['idTheme'],
            'regimeId' => $data['idRegime']
        ]);

        return $this->json(['message' => 'Menu créé', 'id' => $pdo->lastInsertId()],
        status: Response::HTTP_CREATED);
    }

/*UPDATE avec PUT /api/menu/{id}*/
    #[Route('/update/{id}', name: 'update', methods: ['PUT'])]
    public function update($id, Request $request): Response
    {
        if (!is_numeric($id)) {
            return $this->json(['message' => 'ID invalide'], 400);
        }

        $pdo = getPDO();

        // Vérifier si le menu existe
        $checkSql = "SELECT * FROM menu WHERE idMenu = :id";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute(['id' => $id]);
        $menu = $checkStmt->fetch(\PDO::FETCH_ASSOC);

        if (!$menu) {
            return $this->json(['message' => 'Menu introuvable'], 404);
        }

        // Récupérer les données de la requête
        $data = json_decode($request->getContent(), true);

        // Validation JSON et des champs requis
            if (
                !$data ||
                !isset($data['titre']) ||
                !isset($data['nbre_personne_min']) ||
                !isset($data['prix_personne']) ||
                !isset($data['regime']) ||
                !isset($data['description']) ||
                !isset($data['quantite_restante']) ||
                !isset($data['idTheme']) ||
                !isset($data['idRegime'])
            ){
                return $this->json(['message' => 'Données invalides'], 400);
            }

        // Mettre à jour le menu
         $sql = "UPDATE menu
                SET titre = :titre,
                    nbre_personne_min = :nbre,
                    prix_personne = :prix,
                    regime = :regime,
                    description = :description,
                    quantite_restante = :quantite,
                    idTheme = :theme,
                    idRegime = :regimeId
                WHERE idMenu = :id";
        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            'titre' => $data['titre'],
            'nbre' => $data['nbre_personne_min'],
            'prix' => $data['prix_personne'],
            'regime' => $data['regime'],
            'description' => $data['description'],
            'quantite' => $data['quantite_restante'],
            'theme' => $data['idTheme'],
            'regimeId' => $data['idRegime'],
            'id' => $id
        ]);

        // Récupérer le menu mis à jour pour la réponse
        $stmt = $pdo->prepare("SELECT * FROM menu WHERE idMenu = :id");
        $stmt->execute(['id' => $id]);
        $updatedMenu = $stmt->fetch(\PDO::FETCH_ASSOC);

        // ... Edite le menu et le sauvegarde en base de données
        return $this->json([
            'message' => 'Menu modifié',
            'menu' => $updatedMenu
        ]);
    }

/* DELETE avec/api/menu/{id} */
    #[Route('/delete/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete($id): Response
    {
        if (!is_numeric($id)) {
            return $this->json(['message' => 'ID invalide'], 400);
        }

        $pdo = getPDO();

        // Vérifier si le menu existe
        $checkSql = "SELECT * FROM menu WHERE idMenu = :id";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute(['id' => $id]);
        $menu = $checkStmt->fetch(\PDO::FETCH_ASSOC);

        if (!$menu) {
            return $this->json(['message' => 'Menu introuvable'], 404);
        }

        // Supprimer le menu
        $sql = "DELETE FROM menu WHERE idMenu = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $id]);

        // ... Supprime le menu de la base de données
        return $this->json(['message' => 'Menu supprimé'], Response::HTTP_OK);
    }
}