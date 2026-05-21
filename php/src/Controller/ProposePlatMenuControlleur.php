<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

require_once __DIR__ . '/../Database/connexion.php';

class ProposePlatMenuController extends AbstractController
{
    /* READ ALL avec GET /api/propose-plat-menu */
    #[Route('/api/propose-plat-menu', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $pdo = getPDO();

        $sql = "SELECT ppm.idProposePlatMenu, menu.titre, plat.titre_plat
                FROM proposePlatMenu ppm
                INNER JOIN menu
                    ON ppm.idMenu = menu.idMenu
                INNER JOIN plat
                    ON ppm.idPlat = plat.idPlat";

        $stmt = $pdo->query($sql);
        $resultats = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return $this->json($resultats);
    }

    /* READ ONE avec GET /api/propose-plat-menu/{id} */

    
    #[Route('/api/propose-plat-menu/{id}', methods: ['GET'])]
    public function show($id): JsonResponse
    {
        if (!is_numeric($id)) {
            return $this->json(['message' => 'ID invalide'], 400);
        }

        $pdo = getPDO();

        $sql = "SELECT ppm.idProposePlatMenu, menu.titre, plat.titre_plat
                FROM proposePlatMenu ppm
                INNER JOIN menu
                    ON ppm.idMenu = menu.idMenu
                INNER JOIN plat
                    ON ppm.idPlat = plat.idPlat
                WHERE ppm.idProposePlatMenu = :id";
            

        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $resultat = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$resultat) {
            return $this->json(['message' => 'Relation introuvable'], 404);
        }

        return $this->json($resultat);
    }

    /* CREATE avecPOST /api/propose-plat-menu */
    #[Route('/api/propose-plat-menu', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $pdo = getPDO();
        $data = json_decode($request->getContent(), true);

        /* Validation JSON */
        if (
            !isset($data['idMenu']) ||
            !isset($data['idPlat'])
        ) {
            return $this->json(['message' => 'Champs obligatoires manquants'], 400);
        }

        /* Vérification numérique */
        if (
            !is_numeric($data['idMenu']) ||
            !is_numeric($data['idPlat'])
        ) {
            return $this->json(['message' => 'IDs invalides'], 400);
        }

        /* Vérification menu */
        $sqlMenu = "SELECT * FROM menu
                    WHERE idMenu = :id";

        $stmtMenu = $pdo->prepare($sqlMenu);
        $stmtMenu->execute([
            'id' => $data['idMenu']]);

        if (!$stmtMenu->fetch()) {
            return $this->json(['message' => 'Menu inexistant'], 404);
        }

        /* Vérification plat */
        $sqlPlat = "SELECT * FROM plat
                    WHERE idPlat = :id";

        $stmtPlat = $pdo->prepare($sqlPlat);
        $stmtPlat->execute([
            'id' => $data['idPlat']]);

        if (!$stmtPlat->fetch()) {
            return $this->json(['message' => 'Plat inexistant'], 404);
        }

        /* Vérification doublon */
        $sqlDoublon = "SELECT * FROM proposePlatMenu
                    WHERE idMenu = :menu
                    AND idPlat = :plat";

        $stmtDoublon = $pdo->prepare($sqlDoublon);
        $stmtDoublon->execute([
            'menu' => $data['idMenu'],
            'plat' => $data['idPlat']
        ]);

        if ($stmtDoublon->fetch()) {
            return $this->json(['message' => 'Relation déjà existante'], 409);
        }

        /* INSERT */

        $sql = "INSERT INTO proposePlatMenu (idMenu, idPlat) 
                VALUES(:menu, :plat)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'menu' => $data['idMenu'],
            'plat' => $data['idPlat']
        ]);

        return $this->json(['message' => 'Relation créée'], 201);
    }

    /* DELETE /api/propose-plat-menu/{id} */
    #[Route('/api/propose-plat-menu/{id}', methods: ['DELETE'])]
    public function delete($id): JsonResponse
    {
        if (!is_numeric($id)) {
            return $this->json(['message' => 'ID invalide'], 400);
        }

        $pdo = getPDO();

        /* Vérification existence */
        $sqlCheck = "SELECT * FROM proposePlatMenu
                    WHERE idProposePlatMenu = :id";

        $stmtCheck = $pdo->prepare($sqlCheck);
        $stmtCheck->execute(['id' => $id]);

        if (!$stmtCheck->fetch()) {
            return $this->json(['message' => 'Relation introuvable'], 404);
        }

        /* DELETE */
        $sql = "DELETE FROM proposePlatMenu
                WHERE idProposePlatMenu = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $id]);

        return $this->json(['message' => 'Relation supprimée']);
    }
}