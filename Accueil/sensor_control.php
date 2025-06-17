<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// Fichier PID pour stocker l'ID du processus
$pidFile = '/tmp/sensor_capteur.pid';

$scriptPath = '/home/dimmis/Documents/GitHub/AppCommun/script/BDD_perso.py'; 

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'start') {
        // Vérifier si le capteur n'est pas déjà en cours d'exécution
        if (file_exists($pidFile)) {
            $pid = file_get_contents($pidFile);
            if (posix_kill($pid, 0)) {
                echo json_encode(['success' => false, 'message' => 'Le capteur est déjà en cours d\'exécution']);
                exit;
            } else {
                unlink($pidFile);
            }
        }
        
        // Démarrer le script Python en arrière-plan
        $command = "python3 " . escapeshellarg($scriptPath) . " > /tmp/sensor_output.log 2>&1 & echo $!";
        $pid = shell_exec($command);
        
        if ($pid) {
            file_put_contents($pidFile, trim($pid));
            echo json_encode(['success' => true, 'message' => 'Capteur démarré avec succès', 'status' => 'running']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors du démarrage du capteur. Vérifiez les logs.']);
        }
        
    } elseif ($action === 'stop') {
        if (file_exists($pidFile)) {
            $pid = file_get_contents($pidFile);
            if (posix_kill($pid, SIGTERM)) { 
                usleep(500000); 
                if (!posix_kill($pid, 0)) {
                    unlink($pidFile);
                    echo json_encode(['success' => true, 'message' => 'Capteur arrêté avec succès', 'status' => 'stopped']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Le capteur ne s\'est pas arrêté correctement.']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'arrêt du capteur. Il n\'est peut-être pas en cours d\'exécution ou les permissions sont insuffisantes.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Aucun processus capteur en cours ou PID file manquant.']);
        }
        
    } elseif ($action === 'status') {
        if (file_exists($pidFile)) {
            $pid = file_get_contents($pidFile);
            if (posix_kill($pid, 0)) { 
                echo json_encode(['success' => true, 'status' => 'running', 'message' => 'Capteur en cours d\'exécution']);
            } else {
                unlink($pidFile); 
                echo json_encode(['success' => true, 'status' => 'stopped', 'message' => 'Capteur arrêté (PID file nettoyé)']);
            }
        } else {
            echo json_encode(['success' => true, 'status' => 'stopped', 'message' => 'Capteur arrêté']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Requête invalide']);
}
?>