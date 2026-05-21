<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

require_once __DIR__ . '/../Database/connexion.php';

class PlatAllergeneController extends AbstractController
{
    /*
    ===================================
    READ ALL
    GET /api/plat-allergene
    ===================================
    */
    #[Route('/api/plat-allergene', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $pdo = getPDO();

        $sql = "SELECT pa.idPlatAllergene, plat.titre_plat, allergene.libelle
                FROM platAllergene pa
                INNER JOIN plat
                    ON pa.idPlat = plat.idPlat
                INNER JOIN allergene
                    ON pa.idAllergene = allergene.idAllergene";

            
        $stmt = $pdo->query($sql);
        $resultats = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $this->json($resultats);
    }

    /*
    ===================================
    READ ONE
    GET /api/plat-allergene/{id}
    ===================================
    */
    #[Route('/api/plat-allergene/{id}', methods: ['GET'])]
    public function show($id): JsonResponse
    {
        if (!is_numeric($id)) {
            return $this->json([
                'message' => 'ID invalide'
            ], 400);
        }

        $pdo = getPDO();

        $sql = "
            SELECT
                pa.idPlatAllergene,
                plat.titre_plat,
                allergene.libelle
            FROM platAllergene pa
            INNER JOIN plat
                ON pa.idPlat = plat.idPlat
            INNER JOIN allergene
                ON pa.idAllergene = allergene.idAllergene
            WHERE pa.idPlatAllergene = :id
        ";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            'id' => $id
        ]);

        $resultat = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$resultat) {
            return $this->json([
                'message' => 'Relation introuvable'
            ], 404);
        }

        return $this->json($resultat);
    }

    /*
    ===================================
    CREATE
    POST /api/plat-allergene
    ===================================
    */
    #[Route('/api/plat-allergene', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $pdo = getPDO();

        $data = json_decode($request->getContent(), true);

        /*
        ==========================
        Validation JSON
        ==========================
        */
        if (
            !isset($data['idPlat']) ||
            !isset($data['idAllergene'])
        ) {
            return $this->json([
                'message' => 'Champs obligatoires manquants'
            ], 400);
        }

        /*
        ==========================
        Validation numérique
        ==========================
        */
        if (
            !is_numeric($data['idPlat']) ||
            !is_numeric($data['idAllergene'])
        ) {
            return $this->json([
                'message' => 'IDs invalides'
            ], 400);
        }

        /*
        ==========================
        Vérification plat
        ==========================
        */
        $sqlPlat = "
            SELECT * FROM plat
            WHERE idPlat = :id
        ";

        $stmtPlat = $pdo->prepare($sqlPlat);

        $stmtPlat->execute([
            'id' => $data['idPlat']
        ]);

        if (!$stmtPlat->fetch()) {
            return $this->json([
                'message' => 'Plat inexistant'
            ], 404);
        }

        /*
        ==========================
        Vérification allergène
        ==========================
        */
        $sqlAllergene = "
            SELECT * FROM allergene
            WHERE idAllergene = :id
        ";

        $stmtAllergene = $pdo->prepare($sqlAllergene);

        $stmtAllergene->execute([
            'id' => $data['idAllergene']
        ]);

        if (!$stmtAllergene->fetch()) {
            return $this->json([
                'message' => 'Allergène inexistant'
            ], 404);
        }

        /*
        ==========================
        Vérification doublon
        ==========================
        */
        $sqlDoublon = "
            SELECT * FROM platAllergene
            WHERE idPlat = :plat
            AND idAllergene = :allergene
        ";

        $stmtDoublon = $pdo->prepare($sqlDoublon);

        $stmtDoublon->execute([
            'plat' => $data['idPlat'],
            'allergene' => $data['idAllergene']
        ]);

        if ($stmtDoublon->fetch()) {
            return $this->json([
                'message' => 'Relation déjà existante'
            ], 409);
        }

        /*
        ==========================
        INSERT
        ==========================
        */
        $sql = "
            INSERT INTO platAllergene
            (
                idPlat,
                idAllergene
            )
            VALUES
            (
                :plat,
                :allergene
            )
        ";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            'plat' => $data['idPlat'],
            'allergene' => $data['idAllergene']
        ]);

        return $this->json([
            'message' => 'Relation créée'
        ], 201);
    }

    /*
    ===================================
    DELETE
    DELETE /api/plat-allergene/{id}
    ===================================
    */
    #[Route('/api/plat-allergene/{id}', methods: ['DELETE'])]
    public function delete($id): JsonResponse
    {
        if (!is_numeric($id)) {
            return $this->json([
                'message' => 'ID invalide'
            ], 400);
        }

        $pdo = getPDO();

        /*
        ==========================
        Vérification existence
        ==========================
        */
        $sqlCheck = "
            SELECT * FROM platAllergene
            WHERE idPlatAllergene = :id
        ";

        $stmtCheck = $pdo->prepare($sqlCheck);

        $stmtCheck->execute([
            'id' => $id
        ]);

        if (!$stmtCheck->fetch()) {
            return $this->json([
                'message' => 'Relation introuvable'
            ], 404);
        }

        /*
        ==========================
        DELETE
        ==========================
        */
        $sql = "
            DELETE FROM platAllergene
            WHERE idPlatAllergene = :id
        ";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            'id' => $id
        ]);

        return $this->json([
            'message' => 'Relation supprimée'
        ]);
    }
}