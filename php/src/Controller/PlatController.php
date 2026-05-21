<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

require_once __DIR__ . '/../Database/connexion.php';

#[Route('/api/plat', name: 'api_plat')]
class PlatController extends AbstractController
{
    /* READ ALL avec GET /api/plat */
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $pdo = getPDO();
        $sql = "SELECT * FROM plat";
        $stmt = $pdo->query($sql);
        $plats = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $this->json($plats);
    }

    /* READ ONE avec GET /api/plat/{id} */
    #[Route('/show/{id}', name: 'show', methods: ['GET'])]
    public function show($id): JsonResponse
    {
        if (!is_numeric($id)) {
            return $this->json(['message' => 'ID invalide'], 400);
        }

        $pdo = getPDO();

        $sql = "SELECT * FROM plat
                WHERE idPlat = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $plat = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$plat) {
            return $this->json(['message' => 'Plat introuvable'], 404);
        }

        return $this->json($plat);
    }

    /* CREATE avec POST /api/plat */
    #[Route('/create', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $pdo = getPDO();

        /* Récupération données */
        $titre = $request->request->get('titre_plat');
        $categorie = $request->request->get('categorie');

        /* Validation champs */
        if (
            empty($titre) ||
            empty($categorie)
        ) {
            return $this->json(['message' => 'Champs obligatoires manquants'], 400);
        }

        /* Validation image */
        $photo = $request->files->get('photo');

        if (!$photo) {
            return $this->json(['message' => 'Photo obligatoire'], 400);
        }

        /* Extensions autorisées */
        $extensionsAutorisees = [
            'jpg',
            'jpeg',
            'png',
            'webp'
        ];

        $extension = strtolower(
            $photo->guessExtension());

        if (!in_array($extension, $extensionsAutorisees)) {
            return $this->json(['message' => 'Format image invalide'], 400);
        }

        /* Taille maximale */
        if ($photo->getSize() > 2000000) {
            return $this->json(['message' => 'Image trop volumineuse'], 400);
        }

        /* Nom fichier sécurisé */
        $nomFichier = uniqid() . '.' . $extension;

        /* Upload image */
        $photo->move(
            $this->getParameter('kernel.project_dir')
            . '/public/uploads/plats',
            $nomFichier
        );

        /* INSERT */
        $sql = "INSERT INTO plat(titre_plat, categorie, photo)
                VALUES(:titre, :categorie, :photo)";
            

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'titre' => $titre,
            'categorie' => $categorie,
            'photo' => $nomFichier
        ]);

        return $this->json(['message' => 'Plat créé'], 201);
    }

    /* UPDATE avec PUT /api/plat/{id}*/
    #[Route('/edit/{id}', name: 'edit', methods: ['PUT'])]
    public function update($id, Request $request): JsonResponse
    {
        if (!is_numeric($id)) {
            return $this->json(['message' => 'ID invalide'], 400);
        }

        $pdo = getPDO();

        /* Vérification existence */
        $sqlCheck = "SELECT * FROM plat
                    WHERE idPlat = :id";
            

        $stmtCheck = $pdo->prepare($sqlCheck);
        $stmtCheck->execute(['id' => $id]);
        $plat = $stmtCheck->fetch();

        if (!$plat) {
            return $this->json(['message' => 'Plat introuvable'], 404);
        }

        /* Récupération données */
        $titre = $request->request->get('titre_plat');
        $categorie = $request->request->get('categorie');

        /* UPDATE SQL */
        $sql = "UPDATE plat
                SET
                    titre_plat = :titre,
                    categorie = :categorie
                WHERE idPlat = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'titre' => $titre,
            'categorie' => $categorie,
            'id' => $id
        ]);

        return $this->json(['message' => 'Plat modifié']);
    }

    /* DELETE /api/plat/{id} */
    #[Route('/delete/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete($id): JsonResponse
    {
        if (!is_numeric($id)) {
            return $this->json(['message' => 'ID invalide'], 400);
        }

        $pdo = getPDO();

        /* Vérification existence */
        $sqlCheck = "SELECT * FROM plat
                WHERE idPlat = :id";
        $stmtCheck = $pdo->prepare($sqlCheck);
        $stmtCheck->execute(['id' => $id]);
        $plat = $stmtCheck->fetch(\PDO::FETCH_ASSOC);

        if (!$plat) {
            return $this->json(['message' => 'Plat introuvable'], 404);
        }

        /* Suppression image */
        $cheminImage =
            $this->getParameter('kernel.project_dir')
            . '/public/uploads/plats/'
            . $plat['photo'];

        if (file_exists($cheminImage)) {
            unlink($cheminImage);
        }

        /*DELETE SQL */
        $sql = "DELETE FROM plat
                WHERE idPlat = :id";
            
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $id]);

        return $this->json(['message' => 'Plat supprimé']);
    }
}