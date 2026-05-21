<?php

namespace App\Security;

use PDO;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

class UserProvider implements UserProviderInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $sql = "
        SELECT utilisateur.*,
               role.libelle AS role
        FROM utilisateur

        INNER JOIN role
        ON utilisateur.idRole = role.idRole

        WHERE utilisateur.email = :email
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            'email' => $identifier
        ]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            throw new UserNotFoundException(
                'Utilisateur introuvable'
            );
        }

        return new User(
            $user['idUtilisateur'],
            $user['email'],
            $user['password'],
            [
                'ROLE_' . strtoupper($user['role'])
            ]
        );
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        return $this->loadUserByIdentifier(
            $user->getUserIdentifier()
        );
    }

    public function supportsClass(string $class): bool
    {
        return $class === User::class;
    }
}