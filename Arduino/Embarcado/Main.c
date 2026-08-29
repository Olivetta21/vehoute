#include "GSM.h"
#include "GPS.h"

#define MAX_LOC 10

unsigned long time_last_loc = 0;
unsigned long time = 0;

class Localizacoes {
private:
	typedef struct {
		double latitude;
		double longitude;
	} Loc_XY;
	
	Loc_XY fila_envio[MAX_LOC] = {0};
	short older = 0;
	short atual_envio = 0;

public:
	void add(double lat, double lon) {
		fila_envio[atual_envio].latitude = lat;
		fila_envio[atual_envio].longitude = lon;
		
		atual_envio++;
		if (atual_envio >= MAX_LOC) atual_envio = 0;

		if (atual_envio == older) {
			older++;
			if (older >= MAX_LOC) older = 0;
		}
	}

	bool get(double &lat, double &lon) {
		if (older == atual_envio) {
			lat = 0.0;
			lon = 0.0;
			return false;
		}

		lat = fila_envio[older].latitude;
		lon = fila_envio[older].longitude;

		older++;
		if (older >= MAX_LOC) older = 0;
		return true;
	}
} Loc;


void setup() {
	Serial.begin(9600);
	GsmSerial.begin(9600);
	GpsSerial.begin(9600);

	Serial.println("Setup done");
	while(!Serial.available()) {
		delay(100);
	}

	Serial.println("Started");
}

void loop() {
	//delay(1000);
	time = millis();

	if (GSM.isStage(GSM_STAGE_READY_FOR_LOCATION) && !GPS.hasLocation() && time - time_last_loc > 10000) {
		GPS.poll();
		time_last_loc = time;
	}
	GSM.poll();

	{ // STAGES MACHINE
		int res = GSM.runStagesMachine();
		if (res != 0) {
			Serial.print("!");
			if (res == -1) Serial.println("SNWE");
			else if (res == -2) Serial.println("EIND");
			else Serial.println(res);
		}
	}

	{ // GET LOCATION IF AVAILABLE
		double lat, lon;
		if (GPS.getLocation(lat, lon)) {
			Serial.print("LR:");
			Serial.print(lat, 6);
			Serial.print(", ");
			Serial.println(lon, 6);
			Loc.add(lat, lon);
		}
		
		if (!GSM.hasLocationToSend() && Loc.get(lat, lon)) {
			GSM.setLocationToSend(lat, lon);
		}
	}

}
	
