<?php

if (!function_exists('getPDO')) {

    function getPDO()
    {
        $host = "db";
        $dbname = "vitegourmand";
        $user = "root";
        $password = "root";

        try {

            $pdo = new PDO(
                "mysql:host=$host;dbname=$dbname;charset=utf8",
                $user,
                $password
            );

            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            return $pdo;

        } catch (PDOException $e) {

            die("Erreur connexion : " . $e->getMessage());
        }
    }
}