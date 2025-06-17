import serial
import mysql.connector
import re

# Connexion à la base de données
db = mysql.connector.connect(
    host = 'localhost',
    database = 'mesures_dht11',
    user = 'arduino_user',
    password = 'monpassword',
)
cursor = db.cursor()

ser = serial.Serial('/dev/ttyACM0', 9600, timeout=1)

print("Lecture et insertion des données du capteur... (CTRL+C pour arrêter)")

try:
    while True:
        line = ser.readline().decode('utf-8').strip()
        if line:
            print(f"Données reçues : {line}")

            if "Humidité" in line:
                humidite = int(re.search(r'(\d+)', line).group(1))
            elif "Température" in line:
                temperature = float(re.search(r'([\d.]+)', line).group(1))
                sql = "INSERT INTO mesures (temperature, humidite) VALUES (%s, %s)"
                cursor.execute(sql, (temperature, humidite))
                db.commit()
                print("Données insérées en base")
except KeyboardInterrupt:
    print("\nArrêt par utilisateur")
finally:
    ser.close()
    cursor.close()
    db.close()