#ifndef EMBARCADO_GPS_H
#define EMBARCADO_GPS_H

#include <Arduino.h>
#include <SoftwareSerial.h>
#include <TinyGPSPlus.h>

TinyGPSPlus GpsProcessor;
SoftwareSerial GpsSerial(4, 3);

class GPS {
private:
	double latitude = 0.0;
	double longitude = 0.0;

public:
	void poll() {
		GpsSerial.listen();
		unsigned long time_limit = millis() + 3000;

		while (time_limit - millis() > 0) {
			if (GpsSerial.available()) {
				GpsProcessor.encode(GpsSerial.read());

				if (GpsProcessor.location.isUpdated()) {
					latitude = GpsProcessor.location.lat();
					longitude = GpsProcessor.location.lng();
					return;
				}
			}
		}
	}

	bool hasLocation() {
		return latitude != 0.0 || longitude != 0.0;
	}

	void getLocation(double &lat, double &lon) {
		lat = latitude;
		lon = longitude;
		latitude = 0.0;
		longitude = 0.0;
	}
};

GPS GPS;

#endif