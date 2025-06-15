<?php
// Configuration de la connexion série
$serial_port = '/dev/ttyACM0'; // Ajustez selon votre système (Linux/Mac)
// Pour Windows, utilisez 'COM3' ou 'COM4' etc.
$baud_rate = 9600;

// Fonction pour lire les données depuis le port série
function lire_donnees_serie($port, $timeout = 10) {
    // Configuration du port série (Linux/Mac)
    if (PHP_OS_FAMILY === 'Linux' || PHP_OS_FAMILY === 'Darwin') {
        // Configuration plus robuste du port série
        exec("stty -F $port {$GLOBALS['baud_rate']} cs8 -cstopb -parity raw -echo", $output, $return_code);
        if ($return_code !== 0) {
            error_log("Erreur configuration port série: " . implode("\n", $output));
            return false;
        }
        
        $handle = fopen($port, "r+b");
    } else {
        // Pour Windows, vous pourriez utiliser une bibliothèque comme php_serial
        return false;
    }
    
    if (!$handle) {
        error_log("Impossible d'ouvrir le port série: $port");
        return false;
    }
    
    // Vider le buffer d'entrée
    stream_set_blocking($handle, false);
    while (fread($handle, 1024)) { /* vider le buffer */ }
    stream_set_blocking($handle, true);
    
    // Attendre les données avec pattern spécifique
    $start_time = time();
    $data = '';
    $complete_reading = '';
    
    while ((time() - $start_time) < $timeout) {
        $line = fgets($handle, 1024);
        if ($line === false) {
            usleep(100000); // 100ms
            continue;
        }
        
        $complete_reading .= $line;
        
        // Chercher une lecture complète avec les séparateurs exacts de votre format
        if (preg_match('/={30,}.*?Humidité\s+:\s+\d+\s+%.*?Température\s+:\s+[\d.]+\s+°C.*?-{30,}/s', $complete_reading)) {
            // On a une lecture complète avec les séparateurs ======== et --------
            $data = $complete_reading;
            break;
        }
        
        // Éviter l'accumulation excessive de données
        if (strlen($complete_reading) > 5000) {
            $complete_reading = substr($complete_reading, -2000);
        }
    }
    
    fclose($handle);
    
    if (empty($data)) {
        error_log("Timeout ou données incomplètes. Données reçues: " . substr($complete_reading, -500));
        return $complete_reading; // Retourner quand même les données partielles pour debug
    }
    
    return $data;
}

// Fonction pour parser les données du capteur
function parser_donnees_dht11($raw_data) {
    $donnees = array(
        'humidite' => null,
        'temperature' => null,
        'erreur' => false,
        'message' => '',
        'raw_data_debug' => $raw_data // Pour debug
    );
    
    // Recherche des patterns dans les données - Format exact de votre Arduino
    // Pattern pour l'humidité : "Humidité    : 36 %"
    if (preg_match('/Humidité\s+:\s+(\d+)\s+%/', $raw_data, $matches_hum)) {
        $donnees['humidite'] = intval($matches_hum[1]);
    }
    
    // Pattern pour la température : "Température : 28.1 °C"  
    if (preg_match('/Température\s+:\s+([\d]+\.?[\d]*)\s+°C/', $raw_data, $matches_temp)) {
        $donnees['temperature'] = floatval($matches_temp[1]);
    }
    
    // Vérifier s'il y a des erreurs
    if (strpos($raw_data, 'ERREUR') !== false || strpos($raw_data, '***') !== false) {
        $donnees['erreur'] = true;
        if (strpos($raw_data, 'CHECKSUM') !== false) {
            $donnees['message'] = 'Erreur de checksum - Données corrompues';
        } else if (strpos($raw_data, 'LECTURE') !== false) {
            $donnees['message'] = 'Erreur de lecture du capteur';
        } else if (strpos($raw_data, 'Time-Out') !== false) {
            $donnees['message'] = 'Timeout de lecture du capteur';
        } else if (strpos($raw_data, 'Pulse trop courte') !== false) {
            $donnees['message'] = 'Signal du capteur trop faible';
        } else {
            $donnees['message'] = 'Erreur inconnue du capteur';
        }
    }
    
    // Vérification supplémentaire : si on n'a pas pu extraire les données
    if ($donnees['humidite'] === null && $donnees['temperature'] === null && !$donnees['erreur']) {
        $donnees['erreur'] = true;
        $donnees['message'] = 'Impossible de parser les données reçues';
    }
    
    return $donnees;
}

// Traitement des requêtes AJAX
if (isset($_GET['action']) && $_GET['action'] === 'get_data') {
    header('Content-Type: application/json');
    
    // Simulation des données pour test (remplacez par la lecture série réelle)
    if (isset($_GET['simulation']) && $_GET['simulation'] === 'true') {
        $donnees_simulees = array(
            'humidite' => rand(40, 80),
            'temperature' => rand(180, 350) / 10.0, // 18.0 à 35.0°C
            'erreur' => false,
            'message' => '',
            'timestamp' => date('Y-m-d H:i:s')
        );
        echo json_encode($donnees_simulees);
        exit;
    }
    
    // Lecture réelle du port série
    $raw_data = lire_donnees_serie($serial_port);
    
    if ($raw_data === false) {
        echo json_encode(array(
            'erreur' => true,
            'message' => 'Impossible de lire le port série',
            'timestamp' => date('Y-m-d H:i:s')
        ));
    } else {
        $donnees = parser_donnees_dht11($raw_data);
        $donnees['timestamp'] = date('Y-m-d H:i:s');
        
        // Ajouter les données brutes pour debug (optionnel)
        if (isset($_GET['debug']) && $_GET['debug'] === 'true') {
            $donnees['raw_data'] = $raw_data;
        }
        
        echo json_encode($donnees);
    }
    exit;
}

// Si c'est une requête pour sauvegarder les données
if (isset($_POST['action']) && $_POST['action'] === 'save_data') {
    header('Content-Type: application/json');
    
    $humidite = floatval($_POST['humidite']);
    $temperature = floatval($_POST['temperature']);
    $timestamp = date('Y-m-d H:i:s');
    
    // Sauvegarde dans un fichier CSV (ou base de données)
    $csv_file = 'donnees_dht11.csv';
    $data_line = "$timestamp,$humidite,$temperature\n";
    
    if (file_put_contents($csv_file, $data_line, FILE_APPEND | LOCK_EX)) {
        echo json_encode(array('success' => true, 'message' => 'Données sauvegardées'));
    } else {
        echo json_encode(array('success' => false, 'message' => 'Erreur de sauvegarde'));
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring Capteur DHT11</title>
    <link rel="stylesheet" href="Affichage.css">

</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🌡️ Monitoring DHT11</h1>
            <p>Surveillance en temps réel de la température et de l'humidité</p>
        </div>
        
        <div class="content">
            <div id="status" class="status loading">
                Chargement des données...
            </div>
            
            <div class="data-card">
                <div class="data-row">
                    <span class="data-label">💧 Humidité</span>
                    <span id="humidite" class="data-value humidity">-- %</span>
                </div>
                
                <div class="data-row">
                    <span class="data-label">🌡️ Température</span>
                    <span id="temperature" class="data-value temperature">-- °C</span>
                </div>
            </div>
            
            <div class="controls">
                <button id="refresh-btn" class="btn" onclick="rafraichirDonnees()">
                    🔄 Actualiser
                </button>
                
                <button id="save-btn" class="btn" onclick="sauvegarderDonnees()" disabled>
                    💾 Sauvegarder
                </button>
                
                <button id="debug-btn" class="btn" onclick="toggleDebug()">
                    🔍 Debug
                </button>
            </div>
            
            <div id="debug-info" style="display: none; background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 20px 0;">
                <h4>Données brutes reçues :</h4>
                <pre id="raw-data" style="background: #e9ecef; padding: 10px; border-radius: 4px; font-size: 0.9em; overflow-x: auto;"></pre>
            </div>
            
            <div class="auto-refresh">
                <input type="checkbox" id="auto-refresh" onchange="toggleAutoRefresh()">
                <label for="auto-refresh">Actualisation automatique (5s)</label>
            </div>
            
            <div id="timestamp" class="timestamp">
                Dernière mise à jour : --
            </div>
        </div>
    </div>

    <script>
        let autoRefreshInterval;
        let dernieresDonnees = null;
        let debugMode = false;
        
        // Fonction pour activer/désactiver le mode debug
        function toggleDebug() {
            debugMode = !debugMode;
            const debugInfo = document.getElementById('debug-info');
            const debugBtn = document.getElementById('debug-btn');
            
            if (debugMode) {
                debugInfo.style.display = 'block';
                debugBtn.textContent = '🔍 Cacher Debug';
                debugBtn.style.background = '#FF5722';
            } else {
                debugInfo.style.display = 'none';
                debugBtn.textContent = '🔍 Debug';
                debugBtn.style.background = 'linear-gradient(45deg, #2196F3, #21CBF3)';
            }
        }
        
        // Fonction pour rafraîchir les données
        function rafraichirDonnees() {
            const refreshBtn = document.getElementById('refresh-btn');
            const status = document.getElementById('status');
            
            refreshBtn.disabled = true;
            refreshBtn.textContent = '⏳ Lecture...';
            status.className = 'status loading';
            status.textContent = 'Lecture du capteur en cours...';
            
            // Construction de l'URL avec paramètres debug
            let url = '?action=get_data&simulation=false'; // MODE RÉEL - lecture du capteur
            if (debugMode) {
                url += '&debug=true';
            }
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    dernieresDonnees = data;
                    
                    // Afficher les données brutes en mode debug
                    if (debugMode && data.raw_data_debug) {
                        document.getElementById('raw-data').textContent = data.raw_data_debug;
                    }
                    
                    if (data.erreur) {
                        status.className = 'status error';
                        status.textContent = '❌ Erreur: ' + data.message;
                        document.getElementById('humidite').textContent = '-- %';
                        document.getElementById('temperature').textContent = '-- °C';
                        document.getElementById('save-btn').disabled = true;
                        
                        // En cas d'erreur, afficher plus d'infos en debug
                        if (debugMode) {
                            console.log('Données complètes:', data);
                        }
                    } else {
                        status.className = 'status success';
                        status.textContent = '✅ Données mises à jour avec succès';
                        
                        // Vérifier que les données sont valides
                        if (data.humidite !== null && data.temperature !== null) {
                            document.getElementById('humidite').textContent = data.humidite + ' %';
                            document.getElementById('temperature').textContent = data.temperature.toFixed(1) + ' °C';
                            document.getElementById('save-btn').disabled = false;
                        } else {
                            status.className = 'status error';
                            status.textContent = '⚠️ Données incomplètes reçues';
                            document.getElementById('save-btn').disabled = true;
                        }
                    }
                    
                    document.getElementById('timestamp').textContent = 
                        'Dernière mise à jour : ' + data.timestamp;
                })
                .catch(error => {
                    status.className = 'status error';
                    status.textContent = '❌ Erreur de communication: ' + error.message;
                    console.error('Erreur fetch:', error);
                })
                .finally(() => {
                    refreshBtn.disabled = false;
                    refreshBtn.textContent = '🔄 Actualiser';
                });
        }
        
        // Fonction pour sauvegarder les données
        function sauvegarderDonnees() {
            if (!dernieresDonnees || dernieresDonnees.erreur) {
                alert('Aucune donnée valide à sauvegarder');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'save_data');
            formData.append('humidite', dernieresDonnees.humidite);
            formData.append('temperature', dernieresDonnees.temperature);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Données sauvegardées avec succès');
                } else {
                    alert('❌ Erreur de sauvegarde: ' + data.message);
                }
            })
            .catch(error => {
                alert('❌ Erreur: ' + error.message);
            });
        }
        
        // Fonction pour activer/désactiver l'actualisation automatique
        function toggleAutoRefresh() {
            const checkbox = document.getElementById('auto-refresh');
            
            if (checkbox.checked) {
                autoRefreshInterval = setInterval(rafraichirDonnees, 5000);
                rafraichirDonnees(); // Première lecture immédiate
            } else {
                if (autoRefreshInterval) {
                    clearInterval(autoRefreshInterval);
                }
            }
        }
        
        // Chargement initial des données
        window.onload = function() {
            rafraichirDonnees();
        };
        
        // Nettoyage lors de la fermeture de la page
        window.onbeforeunload = function() {
            if (autoRefreshInterval) {
                clearInterval(autoRefreshInterval);
            }
        };
    </script>
</body>
</html>