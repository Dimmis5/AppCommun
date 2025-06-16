#!/usr/bin/env python3
import serial
import mysql.connector
import re
import signal
import sys
import time
import logging
from datetime import datetime

# Configuration du logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler('/tmp/sensor_capteur.log'),
        logging.StreamHandler()
    ]
)

class SensorManager:
    def __init__(self):
        self.running = True
        self.ser = None
        self.db = None
        self.cursor = None
        self.humidite = None
        self.temperature = None
        
        # Gestionnaire de signal pour arrêt propre
        signal.signal(signal.SIGTERM, self.signal_handler)
        signal.signal(signal.SIGINT, self.signal_handler)
    
    def signal_handler(self, signum, frame):
        """Gestionnaire pour arrêt propre du script"""
        logging.info(f"Signal {signum} reçu - Arrêt du capteur...")
        self.running = False
    
    def connect_database(self):
        """Connexion à la base de données"""
        try:
            self.db = mysql.connector.connect(
                host='localhost',
                database='mesures_dht11',
                user='arduino_user',
                password='monpassword',
                autocommit=True
            )
            self.cursor = self.db.cursor()
            logging.info("Connexion à la base de données établie")
            return True
        except mysql.connector.Error as e:
            logging.error(f"Erreur de connexion à la base de données: {e}")
            return False
    
    def connect_serial(self):
        """Connexion au port série"""
        try:
            self.ser = serial.Serial('/dev/ttyACM0', 9600, timeout=1)
            time.sleep(2)  # Attendre la stabilisation de la connexion
            logging.info("Connexion série établie")
            return True
        except serial.SerialException as e:
            logging.error(f"Erreur de connexion série: {e}")
            return False
    
    def parse_sensor_data(self, line):
        """Parse les données du capteur"""
        try:
            if "Humidité" in line:
                match = re.search(r'(\d+)', line)
                if match:
                    self.humidite = int(match.group(1))
                    logging.debug(f"Humidité reçue: {self.humidite}%")
            elif "Température" in line:
                match = re.search(r'([\d.]+)', line)
                if match:
                    self.temperature = float(match.group(1))
                    logging.debug(f"Température reçue: {self.temperature}°C")
                    
                    # Insérer en base dès qu'on a les deux valeurs
                    if self.humidite is not None:
                        self.insert_data()
                        
        except Exception as e:
            logging.error(f"Erreur lors du parsing: {e}")
    
    def insert_data(self):
        """Insérer les données dans la base"""
        try:
            sql = "INSERT INTO mesures (temperature, humidite) VALUES (%s, %s)"
            self.cursor.execute(sql, (self.temperature, self.humidite))
            logging.info(f"Données insérées: T={self.temperature}°C, H={self.humidite}%")
            
            # Réinitialiser les valeurs
            self.humidite = None
            self.temperature = None
            
        except mysql.connector.Error as e:
            logging.error(f"Erreur lors de l'insertion: {e}")
            # Tentative de reconnexion
            self.reconnect_database()
    
    def reconnect_database(self):
        """Tentative de reconnexion à la base de données"""
        try:
            if self.db:
                self.db.close()
            logging.info("Tentative de reconnexion à la base de données...")
            self.connect_database()
        except Exception as e:
            logging.error(f"Erreur lors de la reconnexion: {e}")
    
    def run(self):
        """Boucle principale du capteur"""
        logging.info("Démarrage du gestionnaire de capteur...")
        
        # Établir les connexions
        if not self.connect_database():
            logging.error("Impossible de se connecter à la base de données")
            return False
        
        if not self.connect_serial():
            logging.error("Impossible de se connecter au port série")
            return False
        
        logging.info("Lecture des données du capteur en cours... (SIGTERM pour arrêter)")
        
        try:
            while self.running:
                try:
                    if self.ser.in_waiting > 0:
                        line = self.ser.readline().decode('utf-8').strip()
                        if line:
                            logging.debug(f"Données reçues: {line}")
                            self.parse_sensor_data(line)
                    
                    time.sleep(0.1)  # Petite pause pour éviter la surcharge CPU
                    
                except serial.SerialException as e:
                    logging.error(f"Erreur série: {e}")
                    break
                except Exception as e:
                    logging.error(f"Erreur inattendue: {e}")
                    time.sleep(1)  # Pause avant de continuer
                    
        except Exception as e:
            logging.error(f"Erreur dans la boucle principale: {e}")
        
        finally:
            self.cleanup()
    
    def cleanup(self):
        """Nettoyage des ressources"""
        logging.info("Nettoyage des ressources...")
        
        if self.ser:
            try:
                self.ser.close()
                logging.info("Connexion série fermée")
            except:
                pass
        
        if self.cursor:
            try:
                self.cursor.close()
                logging.info("Curseur de base de données fermé")
            except:
                pass
        
        if self.db:
            try:
                self.db.close()
                logging.info("Connexion à la base de données fermée")
            except:
                pass
        
        logging.info("Arrêt du gestionnaire de capteur terminé")

def main():
    """Fonction principale"""
    sensor_manager = SensorManager()
    
    try:
        success = sensor_manager.run()
        sys.exit(0 if success else 1)
    except Exception as e:
        logging.error(f"Erreur fatale: {e}")
        sys.exit(1)

if __name__ == "__main__":
    main()