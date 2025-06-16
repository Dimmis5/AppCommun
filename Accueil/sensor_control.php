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
// !!! IMPORTANT: Assurez-vous que ce chemin est correct sur votre serveur
$scriptPath = '/home/dimmis/Documents/GitHub/AppCommun/script/BDD_perso.py'; 

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'start') {
        // Vérifier si le capteur n'est pas déjà en cours d'exécution
        if (file_exists($pidFile)) {
            $pid = file_get_contents($pidFile);
            // Check if the process is actually running
            if (posix_kill($pid, 0)) {
                echo json_encode(['success' => false, 'message' => 'Le capteur est déjà en cours d\'exécution']);
                exit;
            } else {
                // If PID file exists but process is not running, clean up
                unlink($pidFile);
            }
        }
        
        // Démarrer le script Python en arrière-plan
        // Use full path for python3 if not in PATH, e.g., /usr/bin/python3
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
            if (posix_kill($pid, SIGTERM)) { // Send SIGTERM for graceful shutdown
                // Give a moment for the process to terminate
                usleep(500000); // 0.5 seconds
                if (!posix_kill($pid, 0)) { // Check if process is truly dead
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
            if (posix_kill($pid, 0)) { // Check if process with PID exists
                echo json_encode(['success' => true, 'status' => 'running', 'message' => 'Capteur en cours d\'exécution']);
            } else {
                unlink($pidFile); // Clean up stale PID file
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