#ifndef EMBARCADO_GPS_H
#define EMBARCADO_GPS_H

#include <Arduino.h>
#include <SoftwareSerial.h>
#include <TinyGPSPlus.h>

TinyGPSPlus GpsProcessor;
SoftwareSerial GpsSerial(4, 3);

class GPS {
private:
	bool has_location = false;
	float latitude = 0.0;
	float longitude = 0.0;

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
					has_location = true;
					return;
				}
			}
		}
	}

	bool hasLocation() {
		return has_location;
	}

	bool getLocation(float &lat, float &lon) {
		if (!hasLocation()) {
			return false;
		}
		lat = latitude;
		lon = longitude;
		has_location = false;
		return true;
	}
};

GPS GPS;

#endif