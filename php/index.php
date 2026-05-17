<?php
echo "Bonjour, Docker !";

header('Content-Type: application/json; charset=utf-8');

const DBHOST = 'db';
const DBUSER = 'user';
const DBPASS = 'password';
const DBNAME = 'app';

$dsn = "mysql:host=$DBHOST;dbname=$DBNAME";
$username = DBUSER;
$password = DBPASS;

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Connexion à la base de données réussie !";

} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}

?>