<?php
// Désactiver les notices PHP pour un affichage plus propre
error_reporting(E_ERROR | E_WARNING);

// Configuration pour l'affichage en temps réel (version Linux)
if (php_sapi_name() === 'cli') {
    // Mode ligne de commande
    ob_implicit_flush(true);
} else {
    // Mode serveur web
    ini_set('output_buffering', 'off');
    ini_set('zlib.output_compression', false);
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-cache');
    header('Connection: keep-alive');
    echo '<!DOCTYPE html><html><head><meta charset="utf-8">';
    echo '<title>Communication Série Arduino - Table ASCII</title></head><body>';
    echo '<pre>'; // Utilisation de <pre> pour garder le formatage texte
}

// Fonction pour affichage temps réel (version simplifiée)
function echoFlush($string) {
    echo $string . PHP_EOL;
    if (php_sapi_name() !== 'cli') {
        flush();
    }
    usleep(50000); // 50ms de pause
}

// Détecter automatiquement le port Arduino
function findArduinoPort() {
    $possiblePorts = [
        '/dev/ttyUSB0', '/dev/ttyUSB1', '/dev/ttyUSB2',
        '/dev/ttyACM0', '/dev/ttyACM1', '/dev/ttyACM2'
    ];
    
    foreach ($possiblePorts as $port) {
        if (file_exists($port) && is_readable($port)) {
            return $port;
        }
    }
    return null;
}

// Détection automatique du port
$portName = findArduinoPort();

if (!$portName) {
    echoFlush('❌ Aucun port Arduino détecté automatiquement');
    exit;
}

// Configuration du port série
$baudRate = 9600;
$bits = 8;
$stopBit = 1;

echoFlush('📡 Initialisation de la communication série...');
echoFlush("Port détecté: $portName | Baud Rate: $baudRate | Bits: $bits | Stop: $stopBit");

// Vérifier les permissions
if (!is_readable($portName) || !is_writable($portName)) {
    echoFlush('❌ Permissions insuffisantes sur le port série');
    exit;
}

// Ouverture du port série pour Linux
$serialPort = dio_open($portName, O_RDWR | O_NOCTTY | O_NONBLOCK);

if (!$serialPort) {
    echoFlush('❌ Impossible d\'ouvrir le port série ' . $portName);
    exit;
}

// Configuration du port série pour Linux
dio_fcntl($serialPort, F_SETFL, O_SYNC);

$result = dio_tcsetattr($serialPort, array(
    'baud' => $baudRate,
    'bits' => $bits,
    'stop'  => $stopBit,
    'parity' => 0
));

if (!$result) {
    echoFlush('❌ Erreur lors de la configuration du port série');
    dio_close($serialPort);
    exit;
}

echoFlush('✅ Port série ouvert et configuré avec succès!');

// Attendre un peu que l'Arduino se stabilise
sleep(2);

// Envoi d'un message initial à l'Arduino (optionnel)
$initialMessage = "PHP Linux Connected!\n";
dio_write($serialPort, $initialMessage);

echoFlush("👂 Écoute des données série en continu...");
echoFlush(str_repeat('-', 50));

// Boucle infinie pour la lecture continue
// Modifiez la partie lecture dans votre boucle principale comme ceci :
$buffer = ''; // Buffer global pour accumuler les données

while (true) {
    $data = dio_read($serialPort, 256);
    
    if ($data !== false && strlen($data) > 0) {
        $buffer .= $data;
        
        // Traiter uniquement les lignes complètes
        while (($newline_pos = strpos($buffer, "\n")) !== false) {
            $line = substr($buffer, 0, $newline_pos);
            $buffer = substr($buffer, $newline_pos + 1);
            
            $line = trim($line);
            if (!empty($line)) {
                $currentTime = date('H:i:s');
                
                // Regex améliorée pour capturer tous les formats
                if (preg_match('/^([^,]+),\s*dec:\s*(\d+),\s*hex:\s*([0-9A-F]+),\s*bin:\s*([01]+)$/i', $line, $matches)) {
                    $char = htmlspecialchars($matches[1]);
                    $dec = $matches[2];
                    $hex = strtoupper($matches[3]);
                    $bin = $matches[4];
                    
                    $displayChar = ($char === ' ') ? '[SPACE]' : $char;
                    echoFlush("[$currentTime] '$displayChar' → Dec:$dec | Hex:0x$hex | Bin:$bin");
                }
                // Gestion des lignes incomplètes
                elseif (preg_match('/^([^,]+),\s*(dec|hex|bin):/', $line)) {
                    // Stockage temporaire pour reconstruction
                    $buffer = $line . "\n" . $buffer;
                    break;
                }
            }
        }
    }
    
    usleep(10000); // Réduire le délai à 10ms
}
// Note: Ce code ne fermera jamais le port série (boucle infinie)
// Pour arrêter: Ctrl+C en CLI ou arrêter le script dans le navigateur
?>