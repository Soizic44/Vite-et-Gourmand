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

class SystemMonitor {
    
    public function getReport() {
        return [
            'status' => 'OK',
            'message' => 'Back-end PHP-FPM opérationnel',
            
            'system_info' => [
                'container_id' => gethostname(),
                'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'PHP-FPM',
                'php_version' => PHP_VERSION,
            ],

            // Vérification de l'installation de l'extension faite dans le Dockerfile
            'extensions_check' => [
                'mysqli' => extension_loaded('mysqli') ? ' Installé' : '❌ Manquant',
                'pdo' => extension_loaded('pdo') ? ' Installé' : '❌ Manquant',
            ],

            'timestamp' => date('c')
        ];
    }
}

// Exécution
try {
    $monitor = new SystemMonitor();
    echo json_encode($monitor->getReport(), JSON_PRETTY_PRINT);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>