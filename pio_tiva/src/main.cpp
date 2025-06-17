#include <Arduino.h>

#define DHT11_PIN PA_2 

int bufferDHT[5];  /

void	Init_CapDHT11(int Num_Pin)

{
	pinMode(Num_Pin, OUTPUT);
	digitalWrite(Num_Pin, HIGH);
	pinMode(Num_Pin, INPUT_PULLUP);
	delay(10);
}

#define	TIME_OUT	200		
int		Lect_CapDHT11(int Num_Pin, int pBufRead[])
{
	int nerr, nbyt, nbit, data;		unsigned long dtime;
	pinMode(Num_Pin, OUTPUT);
	digitalWrite(Num_Pin, LOW);			
	delay(22);							
	pinMode(Num_Pin, INPUT_PULLUP);		
	delayMicroseconds(20);

	dtime = pulseIn(Num_Pin, HIGH, TIME_OUT);
	if (dtime < 40)	{					
		nbit = 1;	goto sortie1;
	}

	for (nbyt = 0; nbyt < 5; nbyt++) {
		data = 0;
		for (nbit = 0; nbit < 8; nbit++) {
			dtime = pulseIn(Num_Pin, HIGH, TIME_OUT);
			if (dtime < 20)	{				
				nbit = 3;	goto sortie1;
			}
			data = data << 1;
			if (dtime > 40)			
				data = data | 1;		
		}
		*pBufRead++ = data;
	}

	nerr = 0;	goto sortie2;		
sortie1:
	//----- Afficher les messages d'erreur
	nerr = nbit;	
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


void setup() {
  Serial.begin(9600);
  while (!Serial) {
    ; 
  }
  
  Serial.println("=== Initialisation du capteur DHT11 ===");
  Serial.println("Pin utilisé : PA2");
  

  Init_CapDHT11(DHT11_PIN);
  
  Serial.println("Capteur DHT11 initialisé avec succès");
  Serial.println("Début des mesures dans 2 secondes...");
  delay(2000);
}

void loop() {
  int erreur;
  int humidite, temperature;
  float temperature_float;
  int checksum_calcule, checksum_recu;
  

  erreur = Lect_CapDHT11(DHT11_PIN, bufferDHT);
  
  if (erreur == 0) {

    humidite = bufferDHT[0];      
    temperature = bufferDHT[2];  

    temperature_float = temperature + (bufferDHT[3] / 10.0);
    checksum_recu = bufferDHT[4]; 
    
    checksum_calcule = (bufferDHT[0] + bufferDHT[1] + bufferDHT[2] + bufferDHT[3]) & 0xFF;
    
    if (checksum_calcule == checksum_recu) {
      Serial.println("========================================");
      Serial.print("Humidité    : ");
      Serial.print(humidite);
      Serial.println(" %");
      
      Serial.print("Température : ");
      Serial.print(temperature_float, 1);  
      Serial.println(" °C");
      

      for (int i = 0; i < 5; i++) {

      }
            
    } else {
      Serial.println("*** ERREUR CHECKSUM ***");
      Serial.print("Calculé : ");
      Serial.print(checksum_calcule);
      Serial.print(" - Reçu : ");
      Serial.println(checksum_recu);
    }
    
  } else {

    Serial.print("*** ERREUR DE LECTURE - Code : ");
    Serial.println(erreur);
  }
  
  Serial.println("----------------------------------------");
  
  delay(2000); 
}