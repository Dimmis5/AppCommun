import serial
import mysql.connector
import re

# Connexion à la base de données
db = mysql.connector.connect(
    host='romantcham.fr',         # Ton serveur externe
    database='Domotic_db',        # Ta base
    user='G7E',
    password='afyubr'
)
cursor = db.cursor()

ser = serial.Serial('/dev/ttyACM0', 9600, timeout=1)

# IDs de tes capteurs dans la table `composant`
ID_CAPTEUR_TEMPERATURE = 6  # Remplace par l'ID exact de ton capteur température
ID_CAPTEUR_HUMIDITE = 7    # Remplace par l'ID exact de ton capteur humidité

print("Lecture et insertion des données du capteur... (CTRL+C pour arrêter)")

try:
    humidite = None
    temperature = None

    while True:
        line = ser.readline().decode('utf-8').strip()
        if line:
            print(f"Données reçues : {line}")

            if "Humidité" in line:
                humidite = int(re.search(r'(\d+)', line).group(1))
            elif "Température" in line:
                temperature = float(re.search(r'([\d.]+)', line).group(1))

            # Dès qu'on a la température, on l'insère
            if temperature is not None:
                sql = "INSERT INTO mesure (id_composant, valeur) VALUES (%s, %s)"
                cursor.execute(sql, (ID_CAPTEUR_TEMPERATURE, temperature))
                db.commit()
                print(f"Température {temperature} insérée en base.")
                temperature = None  # reset pour la prochaine mesure

            # Dès qu'on a l'humidité, on l'insère
            if humidite is not None:
                sql = "INSERT INTO mesure (id_composant, valeur) VALUES (%s, %s)"
                cursor.execute(sql, (ID_CAPTEUR_HUMIDITE, humidite))
                db.commit()
                print(f"Humidité {humidite} insérée en base.")
                humidite = None  # reset pour la prochaine mesure

except KeyboardInterrupt:
    print("\nArrêt par utilisateur")

finally:
    ser.close()
    cursor.close()
    db.close()
