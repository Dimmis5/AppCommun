#include <Arduino.h>

// Pin de connexion du capteur DHT11 sur la Tiva
#define DHT11_PIN PA_2  // Pin PA2 sur Tiva

// Buffer pour stocker les données lues du capteur
int bufferDHT[5];  // 5 octets : Humidité H, Humidité L, Température H, Température L, Checksum

//------------------------------------------------------------------------------
//	Librairie : ZLib_CapteurDHT11.ino
//
// One Wire Digital I/O du capteur DHT11
// Appeler la fonction Init_CapDHT11() dans "setup()".
// Appeler la fonction Lect_CapDHT11() dans "loop()".
//
//------------------------------------------------------------------------------
void	Init_CapDHT11(int Num_Pin)
//------------------------------------------------------------------------------
// Parametre en entree : le numero de pin de connexion du capteur 
// Parametre en sortie : la fonction ne retourne rien (déclaration void)
//------------------------------------------------------------------------------
{
	pinMode(Num_Pin, OUTPUT);
	digitalWrite(Num_Pin, HIGH);
	pinMode(Num_Pin, INPUT_PULLUP);
	delay(10);
}

#define	TIME_OUT	200		/* 200 µs */
int		Lect_CapDHT11(int Num_Pin, int pBufRead[])
//------------------------------------------------------------------------------
// Parametre en entree : 1- le numero de pin de connexion du capteur 
//                       2 - le buffer qui va contenir les valeurs lues
// Parametre en sortie : nerr indique s'il y a une erreur de lecture du capteur
//						 nerr = 0; indique pas d'erreur
//							  > 0; indique le numero (type) d'erreur
//------------------------------------------------------------------------------
{
	int nerr, nbyt, nbit, data;		unsigned long dtime;
	//---- Start comm
	pinMode(Num_Pin, OUTPUT);
	digitalWrite(Num_Pin, LOW);			// debut bit de "Start"
	delay(22);							// duree du bit >= 18 ms 
	pinMode(Num_Pin, INPUT_PULLUP);		// pin en entrée
	delayMicroseconds(20);
	//---- Attente ACK du capteur
	dtime = pulseIn(Num_Pin, HIGH, TIME_OUT);
	if (dtime < 40)	{					// Ack = 80 µs High
		nbit = 1;	goto sortie1;
	}
	//---- Lecture des 40 bits = 5 octets de 8 bits 
	for (nbyt = 0; nbyt < 5; nbyt++) {
		data = 0;
		for (nbit = 0; nbit < 8; nbit++) {
			dtime = pulseIn(Num_Pin, HIGH, TIME_OUT);
			if (dtime < 20)	{				// bit =  26 ou 70 µs High
				nbit = 3;	goto sortie1;
			}
			data = data << 1;
			if (dtime > 40)				// duree = 26 (bit 0) ou 70 (bit 1)
				data = data | 1;		// bit = 1
		}
		*pBufRead++ = data;
	}
	//Serial.println("             +++ Lecture Ok ");
	nerr = 0;	goto sortie2;		// Pas d'erreur. Tout est Ok
sortie1:
	//----- Afficher les messages d'erreur
	nerr = nbit;	// numero de l'erreur
	if (dtime == 0)
		Serial.print("             +++ Time-Out Lecture  : ");
	else {
		Serial.print("             +++ Pulse trop courte : ");
		Serial.print(dtime);	Serial.print("  ");
		nerr += 1;
	}
	Serial.print(" Seq : ");	Serial.print(nbit);	Serial.println("  ");
sortie2:
	return nerr;
}

//------------------------------------------------------------------------------
// SETUP - Initialisation
//------------------------------------------------------------------------------
void setup() {
  // Initialisation de la communication série
  Serial.begin(9600);
  while (!Serial) {
    ; // Attendre que le port série soit prêt
  }
  
  Serial.println("=== Initialisation du capteur DHT11 ===");
  Serial.println("Pin utilisé : PA2");
  
  // Initialisation du capteur DHT11
  Init_CapDHT11(DHT11_PIN);
  
  Serial.println("Capteur DHT11 initialisé avec succès");
  Serial.println("Début des mesures dans 2 secondes...");
  delay(2000);
}

//------------------------------------------------------------------------------
// LOOP - Boucle principale
//------------------------------------------------------------------------------
void loop() {
  int erreur;
  int humidite, temperature;
  float temperature_float;
  int checksum_calcule, checksum_recu;
  
  // Lecture des données du capteur
  erreur = Lect_CapDHT11(DHT11_PIN, bufferDHT);
  
  if (erreur == 0) {
    // Pas d'erreur - traitement des données
    
    // Extraction des valeurs 
    humidite = bufferDHT[0];      // Partie entière de l'humidité
    temperature = bufferDHT[2];   // Partie entière de la température
    // Création de la température avec décimale (partie décimale dans bufferDHT[3])
    temperature_float = temperature + (bufferDHT[3] / 10.0);
    checksum_recu = bufferDHT[4]; // Checksum reçu
    
    // Vérification du checksum
    checksum_calcule = (bufferDHT[0] + bufferDHT[1] + bufferDHT[2] + bufferDHT[3]) & 0xFF;
    
    if (checksum_calcule == checksum_recu) {
      // Checksum correct - affichage des résultats
      Serial.println("========================================");
      Serial.print("Humidité    : ");
      Serial.print(humidite);
      Serial.println(" %");
      
      Serial.print("Température : ");
      Serial.print(temperature_float, 1);  // Affichage avec 1 chiffre après la virgule
      Serial.println(" °C");
      
      //Serial.print("Checksum    : OK (");
      //Serial.print(checksum_recu);
      //Serial.println(")");
      
      // Affichage des données brutes (optionnel)
      //Serial.print("Données brutes : ");
      for (int i = 0; i < 5; i++) {
        //Serial.print(bufferDHT[i]);
        //Serial.print(" ");
      }
      //Serial.println();
      
    } else {
      // Erreur de checksum
      Serial.println("*** ERREUR CHECKSUM ***");
      Serial.print("Calculé : ");
      Serial.print(checksum_calcule);
      Serial.print(" - Reçu : ");
      Serial.println(checksum_recu);
    }
    
  } else {
    // Erreur de lecture
    Serial.print("*** ERREUR DE LECTURE - Code : ");
    Serial.println(erreur);
  }
  
  Serial.println("----------------------------------------");
  
  // Attente avant la prochaine mesure (DHT11 nécessite au moins 1 seconde entre les lectures)
  delay(2000);  // 2 secondes entre chaque mesure
}