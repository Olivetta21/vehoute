#ifndef EMBARCADO_GPS_H
#define EMBARCADO_GPS_H

#include <Arduino.h>
#include <SoftwareSerial.h>
#include <TinyGPSPlus.h>

enum GPSEvent {
	GPS_EVENT_NONE = 0,
	GPS_EVENT_LOCATION_UPDATED,
	GPS_EVENT_UNKNOWN
};

TinyGPSPlus GpsProcessor;
SoftwareSerial GpsSerial(3, 4);

class GPS {
private:
	static const uint8_t kEventQueueSize = 4;

	GPSEvent event_queue[kEventQueueSize];
	uint8_t event_head = 0;
	uint8_t event_tail = 0;
	uint8_t event_count = 0;

	void pushEvent(GPSEvent event) {
		if (event == GPS_EVENT_NONE) {
			return;
		}

		if (event_count >= kEventQueueSize) {
			return;
		}

		event_queue[event_tail] = event;
		event_tail = (event_tail + 1) % kEventQueueSize;
		event_count++;
	}

	bool popEvent(GPSEvent &event) {
		if (event_count == 0) {
			return false;
		}

		event = event_queue[event_head];
		event_head = (event_head + 1) % kEventQueueSize;
		event_count--;
		return true;
	}

public:
	String state = "none";
	double latitude = 0.0;
	double longitude = 0.0;
	double altitude = 0.0;
	unsigned long satellites = 0;

	void poll() {
		GpsSerial.listen();
		delay(20);

		int qnt = GpsSerial.available();
		bool location_event_emitted = false;
		while (qnt--) {
			GpsProcessor.encode(GpsSerial.read());

			if (GpsProcessor.location.isUpdated() && !location_event_emitted) {
				latitude = GpsProcessor.location.lat();
				longitude = GpsProcessor.location.lng();
				altitude = GpsProcessor.altitude.meters();
				satellites = GpsProcessor.satellites.value();
				pushEvent(GPS_EVENT_LOCATION_UPDATED);
				location_event_emitted = true;
			}
		}
	}

	bool nextEvent(GPSEvent &event) {
		return popEvent(event);
	}
};

GPS GPS;

#endif