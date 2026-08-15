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
	
	Loc_XY fila_envio[MAX_LOC];
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

	void get(double &lat, double &lon) {
		if (older == atual_envio) {
			lat = 0.0;
			lon = 0.0;
			return;
		}

		lat = fila_envio[older].latitude;
		lon = fila_envio[older].longitude;

		older++;
		if (older >= MAX_LOC) older = 0;
	}
} Loc;


void processGPS_event() {
	// O GPS só dispara esse evento quando uma localização real foi atualizada.
	if (GPS.state != "loc_ok") GPS.state = "loc_ok";
	Serial.println(GPS.latitude, 6);
	Serial.println(GPS.longitude, 6);
	Serial.println(GPS.satellites);
	Serial.println(GPS.altitude, 6);
	Serial.print("GPS: ");
	Serial.println("localizacao");

	Loc.add(GPS.latitude, GPS.longitude);
}

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
	delay(1000);
	time = millis();

	GSM.poll();
	//GPS.poll();

	if (GPS.state == "none") {
		GPS.state = "trying_loc";
	}

	GPSEvent gpsEvent;
	while (GPS.nextEvent(gpsEvent)) {
		if (gpsEvent == GPS_EVENT_LOCATION_UPDATED) {
			processGPS_event();
			time_last_loc = time;
		}
	}
	if (time - time_last_loc > 5000UL) {
		//Serial.println("GPS: exceeding time without location");
		GPS.state = "trying_loc";
	}
	
	{
		int res = GSM.runStagesMachine();
		if (res != 0) {
			Serial.print("StagesMachine: ");
			if (res == -1) Serial.println("This stage is not waiting for that event");
			else if (res == -2) Serial.println("This event is not defined");
			else Serial.println(res);
			if (res == GSM_STAGE_CONNECTED) {
				Serial.println("GSM: tcp is connected");
			} 
		}
	}

}
	
