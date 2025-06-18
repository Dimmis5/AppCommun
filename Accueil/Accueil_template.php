<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tempérium</title>
    <link rel="icon" href="../logo/logo.png" type="image/x-icon">
    <link rel="stylesheet" href="Accueil.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
</head>
<body>
    <header class="spacing">
        <div class="header-content">
            <h1>Projet Domotique : Gestion Rideau & Lampe</h1>
            <div class="company-name"> Tempérium </div>
            <div class="top-right-logo"><img src="../logo/logo.png" alt="logo" class="logo-img"></div>
        </div>
    </header>

    <?php if ($isLoggedIn): ?>
        <div class="user-info spacing">
            <span class="welcome-message">
                Bienvenue, <?php echo htmlspecialchars($userName); ?> !
            </span>
            <a href="logout.php" class="logout-btn">Déconnexion</a>
        </div>
    <?php else: ?>
        <div class="guest-message spacing">
            Vous n'êtes pas connecté. 
            <a href="../Connexion/Connexion.php" class="login-link">Se connecter</a> 
            pour accéder à toutes les fonctionnalités.
        </div>
    <?php endif; ?>

    <nav>
        <a href="Accueil.php">Accueil</a>
        <a href="<?php echo $isLoggedIn ? '../Affichage/Affichage3.php' : '../Connexion/Connexion.php'; ?>">Donnée capteur & Actionneurs</a>
    </nav>

    <?php if ($isLoggedIn): ?>
        <!-- Front pour la météo -->
        <div class="weather-container spacing">
            <h2>Météo à Paris</h2>
            <div class="weather-info">
                <div class="weather-icon">
                    <img id="weatherIcon" src="https://openweathermap.org/img/wn/01d@2x.png" alt="Weather Icon">
                </div>
                <div class="weather-details">
                    <div class="weather-temp">
                        <span id="currentOutdoorTemp">--</span>°C
                    </div>
                    <div class="weather-desc" id="weatherDescription">Chargement...</div>
                    <div class="weather-humidity">
                        Humidité: <span id="outdoorHumidity">--</span>%
                    </div>
                </div>
            </div>
            <div class="weather-update-time" id="weatherUpdateTime"></div>
        </div>
        <!-- Front pour démarre/arreter le capteur -->
        <div class="sensor-control spacing">
            <h2>Contrôle du Capteur DHT11</h2>
            <button id="startSensorBtn" class="start-btn">Démarrer Capteur</button>
            <button id="stopSensorBtn" class="stop-btn">Arrêter Capteur</button>
            <div id="sensorStatus" class="sensor-status">Statut: Chargement...</div>
        </div>

        <!-- Front pour voir l'état du volet -->
        <div class="shutter-control spacing">
            <h2>Contrôle des Volets</h2>
            <div class="shutter-info">
                <div class="temperature-display">
                    <span class="temp-label">Température actuelle:</span>
                    <span id="currentTemp" class="temp-value">--°C</span>
                </div>
                <div class="shutter-status-container">
                    <div class="shutter-visual">
                        <div id="shutterAnimation" class="shutter-animation">
                            <div class="shutter-slat"></div>
                            <div class="shutter-slat"></div>
                            <div class="shutter-slat"></div>
                            <div class="shutter-slat"></div>
                            <div class="shutter-slat"></div>
                        </div>
                    </div>
                    <div class="shutter-status-text">
                        <span class="status-label">État des volets:</span>
                        <span id="shutterStatus" class="status-value">En attente...</span>
                    </div>
                </div>
                <div class="manual-control">
                    <button id="openShuttersBtn" class="control-btn open-btn">Ouvrir Manuellement</button>
                    <button id="closeShuttersBtn" class="control-btn close-btn">Fermer Manuellement</button>
                </div>
                <div class="shutter-rules">
                    <div class="rule-item">
                        <span class="rule-text">T° ≥ 28°C → Fermeture automatique </span>
                    </div>
                    <div class="rule-item">
                        <span class="rule-text">T° ≤ 27°C → Ouverture automatique </span>
                    </div>
                </div>
            </div>
        </div>
<!-- pour les graphiques -->
        <div class="charts-container">
            <div class="chart-wrapper">
                <h3>Évolution de la Température (100 dernières mesures)</h3>
                <canvas id="temperatureChart"></canvas>
            </div>
            <div class="chart-wrapper">
                <h3>Évolution de l'Humidité (100 dernières mesures)</h3>
                <canvas id="humidityChart"></canvas>
            </div>
        </div>

        <div id="table-container">
            <?php echo $tableHtml; ?>
        </div>
        
        
            <script>
                let isInitialLoad = true;
                let temperatureChart = null;
                let humidityChart = null;
                let currentShutterState = 'unknown';
                let lastTemperature = null;
                let isManualOverride = false;

                const chartConfig = {
                    type: 'line',
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            x: {
                                display: true,
                                grid: {
                                    color: 'rgba(255, 255, 255, 0.1)'
                                },
                                ticks: {
                                    color: 'rgba(255, 255, 255, 0.8)',
                                    maxTicksLimit: 10
                                }
                            },
                            y: {
                                display: true,
                                grid: {
                                    color: 'rgba(255, 255, 255, 0.1)'
                                },
                                ticks: {
                                    color: 'rgba(255, 255, 255, 0.8)'
                                }
                            }
                        },
                        elements: {
                            point: {
                                radius: 2,
                                hoverRadius: 5
                            },
                            line: {
                                tension: 0.3,
                                borderWidth: 2
                            }
                        },
                        interaction: {
                            intersect: false,
                            mode: 'index'
                        }
                    }
                };

                // Pour avoir la météo de Paris (extérieur)
                async function fetchParisWeather() {
                    const apiKey = 'b553bf515578e25be910fffcf28e5dc7'; 
                    const city = 'Paris,FR';
                    const lang = 'fr';
                    const units = 'metric';
                    
                    try {
                        const response = await fetch(
                            `https://api.openweathermap.org/data/2.5/weather?q=${city}&units=${units}&lang=${lang}&appid=${apiKey}`
                        );
                        
                        if (!response.ok) throw new Error('Erreur réseau');
                        
                        const data = await response.json();
                        
                        document.getElementById('currentOutdoorTemp').textContent = Math.round(data.main.temp);
                        document.getElementById('weatherDescription').textContent = data.weather[0].description;
                        document.getElementById('outdoorHumidity').textContent = data.main.humidity;
                        
                        const iconCode = data.weather[0].icon;
                        document.getElementById('weatherIcon').src = `https://openweathermap.org/img/wn/${iconCode}@2x.png`;

                        const now = new Date();
                        document.getElementById('weatherUpdateTime').textContent = 
                            `Dernière mise à jour: ${now.toLocaleTimeString('fr-FR')}`;
                        
                    } catch (error) {
                        console.error('Erreur lors de la récupération de la météo:', error);
                        document.getElementById('weatherDescription').textContent = 'Impossible de charger les données météo';
                    }
                }

                // pour voir les volets
                function updateShutterStatus(temperature) {
                    if (isManualOverride) return;
                    
                    const currentTempElement = document.getElementById('currentTemp');
                    const shutterStatusElement = document.getElementById('shutterStatus');
                    const shutterAnimation = document.getElementById('shutterAnimation');
                    
                    currentTempElement.textContent = temperature + '°C';
                    currentTempElement.className = 'temp-value';
                    
                    if (temperature >= 28) {
                        currentTempElement.classList.add('temp-hot');
                    } else if (temperature <= 27) {
                        currentTempElement.classList.add('temp-cold');
                    } else {
                        currentTempElement.classList.add('temp-normal');
                    }

                    if ((temperature >= 28 && currentShutterState !== 'closed' && currentShutterState !== 'closing') ||
                        (temperature <= 27 && currentShutterState !== 'open' && currentShutterState !== 'opening')) {
                        
                        if (temperature >= 28) {
                            currentShutterState = 'closing';
                            shutterStatusElement.textContent = 'Fermeture en cours...';
                            shutterStatusElement.className = 'status-value status-closing';
                            shutterAnimation.className = 'shutter-animation closing';
                            
                            setTimeout(() => {
                                currentShutterState = 'closed';
                                shutterStatusElement.textContent = 'Fermés (auto)';
                                shutterStatusElement.className = 'status-value status-closed';
                                shutterAnimation.className = 'shutter-animation closed';
                            }, 3000);
                            
                        } else if (temperature <= 27) {
                            currentShutterState = 'opening';
                            shutterStatusElement.textContent = 'Ouverture en cours...';
                            shutterStatusElement.className = 'status-value status-opening';
                            shutterAnimation.className = 'shutter-animation opening';
                            
                            setTimeout(() => {
                                currentShutterState = 'open';
                                shutterStatusElement.textContent = 'Ouverts (auto)';
                                shutterStatusElement.className = 'status-value status-open';
                                shutterAnimation.className = 'shutter-animation open';
                            }, 3000);
                        }
                    }
                    
                    lastTemperature = temperature;
                }

                // Contrôle manuel des volets
                const openShuttersBtn = document.getElementById('openShuttersBtn');
                const closeShuttersBtn = document.getElementById('closeShuttersBtn');

                function manualShutterControl(action) {
                    const shutterStatusElement = document.getElementById('shutterStatus');
                    const shutterAnimation = document.getElementById('shutterAnimation');
                    
                    openShuttersBtn.disabled = true;
                    closeShuttersBtn.disabled = true;
                    isManualOverride = true;
                    
                    if (action === 'open') {
                        currentShutterState = 'opening';
                        shutterStatusElement.textContent = 'Ouverture manuelle...';
                        shutterStatusElement.className = 'status-value status-opening';
                        shutterAnimation.className = 'shutter-animation opening';
                        
                        setTimeout(() => {
                            currentShutterState = 'open';
                            shutterStatusElement.textContent = 'Ouverts (manuel)';
                            shutterStatusElement.className = 'status-value status-open';
                            shutterAnimation.className = 'shutter-animation open';
                            openShuttersBtn.disabled = false;
                            closeShuttersBtn.disabled = false;
                            setTimeout(() => isManualOverride = false, 10000);
                        }, 3000);
                    } else {
                        currentShutterState = 'closing';
                        shutterStatusElement.textContent = 'Fermeture manuelle...';
                        shutterStatusElement.className = 'status-value status-closing';
                        shutterAnimation.className = 'shutter-animation closing';
                        
                        setTimeout(() => {
                            currentShutterState = 'closed';
                            shutterStatusElement.textContent = 'Fermés (manuel)';
                            shutterStatusElement.className = 'status-value status-closed';
                            shutterAnimation.className = 'shutter-animation closed';
                            openShuttersBtn.disabled = false;
                            closeShuttersBtn.disabled = false;
                            setTimeout(() => isManualOverride = false, 10000);
                        }, 3000);
                    }
                }

                openShuttersBtn.addEventListener('click', () => manualShutterControl('open'));
                closeShuttersBtn.addEventListener('click', () => manualShutterControl('close'));

                // Graphique
                function initCharts() {
                    const tempCtx = document.getElementById('temperatureChart').getContext('2d');
                    const humCtx = document.getElementById('humidityChart').getContext('2d');

                    temperatureChart = new Chart(tempCtx, {
                        ...chartConfig,
                        data: {
                            labels: [],
                            datasets: [{
                                label: 'Température (°C)',
                                data: [],
                                borderColor: '#ff6b6b',
                                backgroundColor: 'rgba(255, 107, 107, 0.1)',
                                fill: true
                            }]
                        }
                    });

                    humidityChart = new Chart(humCtx, {
                        ...chartConfig,
                        data: {
                            labels: [],
                            datasets: [{
                                label: 'Humidité (%)',
                                data: [],
                                borderColor: '#48dbfb',
                                backgroundColor: 'rgba(72, 219, 251, 0.1)',
                                fill: true
                            }]
                        }
                    });
                }

                // Charger les données pour les graphiques
                async function chargerDonneesGraphiques() {
                    try {
                        const response = await fetch('get_chart_data.php', {
                            headers: { 'X-Requested-With': 'XMLHttpRequest' }
                        });
                        
                        if (!response.ok) throw new Error('Erreur réseau');
                        
                        const data = await response.json();
                        
                        if (data.success && data.mesures) {
                            const labels = data.mesures.map(mesure => {
                                const date = new Date(mesure.date_mesure);
                                return date.toLocaleTimeString('fr-FR', { 
                                    hour: '2-digit', 
                                    minute: '2-digit' 
                                });
                            });
                            
                            const temperatures = data.mesures.map(mesure => parseFloat(mesure.temperature));
                            const humidites = data.mesures.map(mesure => parseFloat(mesure.humidite));
                            
                            temperatureChart.data.labels = labels;
                            temperatureChart.data.datasets[0].data = temperatures;
                            temperatureChart.update('none');
                            
                            humidityChart.data.labels = labels;
                            humidityChart.data.datasets[0].data = humidites;
                            humidityChart.update('none');
                            
                            if (temperatures.length > 0) {
                                const latestTemp = temperatures[temperatures.length - 1];
                                updateShutterStatus(latestTemp);
                            }
                        }
                    } catch (e) {
                        console.error('Erreur lors du chargement des données graphiques:', e);
                    }
                }

                async function chargerTableau() {
                    try {
                        const response = await fetch('get_data.php', {
                            headers: { 'X-Requested-With': 'XMLHttpRequest' }
                        });
                        if (!response.ok) throw new Error('Erreur réseau');
                        const html = await response.text();
                        
                        const container = document.getElementById('table-container');
                        
                        if (isInitialLoad) {
                            container.innerHTML = html;
                            isInitialLoad = false;
                        } else {
                            const newTable = document.createElement('div');
                            newTable.innerHTML = html;
                            const newTableElement = newTable.querySelector('table');
                            
                            if (newTableElement) {
                                newTableElement.style.animation = 'none';
                                newTableElement.classList.add('no-animation');
                                container.innerHTML = '';
                                container.appendChild(newTableElement);
                            }
                        }
                    } catch (e) {
                        document.getElementById('table-container').innerHTML = '<table><tr><td colspan="4">Erreur lors du chargement des données.</td></tr></table>';
                        console.error(e);
                    }
                }

                // Pour le capteur
                const startSensorBtn = document.getElementById('startSensorBtn');
                const stopSensorBtn = document.getElementById('stopSensorBtn');
                const sensorStatusDiv = document.getElementById('sensorStatus');

                async function updateSensorStatus() {
                    try {
                        const response = await fetch('sensor_control.py', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: 'action=status'
                        });
                        const data = await response.json();
                        if (data.success) {
                            sensorStatusDiv.textContent = 'Statut: ' + (data.status === 'running' ? 'En cours d\'exécution' : 'Arrêté');
                            sensorStatusDiv.className = 'sensor-status ' + (data.status === 'running' ? 'status-running' : 'status-stopped');
                        } else {
                            sensorStatusDiv.textContent = 'Statut: Erreur de vérification';
                            sensorStatusDiv.className = 'sensor-status';
                        }
                    } catch (error) {
                        sensorStatusDiv.textContent = '';
                        sensorStatusDiv.className = 'sensor-status';
                    }
                }

                async function controlSensor(action) {
                    const buttonToDisable = action === 'start' ? startSensorBtn : stopSensorBtn;
                    buttonToDisable.disabled = true;
                    sensorStatusDiv.textContent = 'Statut: Traitement...';
                    
                    try {
                        const response = await fetch('sensor_control.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: 'action=' + action
                        });
                        const data = await response.json();
                        if (data.success) {
                            alert(data.message);
                        } else {
                            alert('Erreur: ' + data.message);
                        }
                    } catch (error) {
                        alert('Erreur de connexion au serveur.');
                    } finally {
                        buttonToDisable.disabled = false;
                        updateSensorStatus(); 
                    }
                }

                startSensorBtn.addEventListener('click', () => controlSensor('start'));
                stopSensorBtn.addEventListener('click', () => controlSensor('stop'));


                document.addEventListener('DOMContentLoaded', function() {
                    initCharts();
                    chargerDonneesGraphiques();
                    updateSensorStatus();
                    fetchParisWeather(); 
                    
                    setInterval(chargerDonneesGraphiques, 2000);
                    setInterval(chargerTableau, 2000);
                    setInterval(updateSensorStatus, 5000);
                    setInterval(fetchParisWeather, 30 * 60 * 1000); // Actualiser la météo toutes les 30 minutes
                });
            </script>
        <?php endif; ?>
        
        <footer>
            &copy; 2025 Projet Domotique - Tous droits réservés
        </footer>
    </body>
</html>