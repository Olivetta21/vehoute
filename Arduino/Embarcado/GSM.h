#ifndef EMBARCADO_GSM_H
#define EMBARCADO_GSM_H

#include <Arduino.h>
#include <SoftwareSerial.h>
#include <string.h>

enum GSMEvent {
	GSM_EVENT_NONE = 0,
	GSM_EVENT_INITIATE_CON_TCP,
	GSM_EVENT_OK,
	GSM_EVENT_ERROR,
	GSM_EVENT_SHUT_OK,
	GSM_EVENT_CONNECT_OK,
	GSM_EVENT_SEND_OK,
	GSM_EVENT_IP,
	GSM_EVENT_PROMPT,
	GSM_EVENT_UNKNOWN,
	GSM_EVENT_CIPSTATE_CONNECT_OK,
	GSM_EVENT_TCP_SERVER_CLOSED,
	GSM_EVENT_CLOSED,

	GSM_EVENT_TCP_SERVER_RECEIVED_PLAIN_TOKEN,
	GSM_EVENT_TCP_SERVER_RECEIVED_PRIVATE_TOKEN,

	GSM_EVENT_NEW_LOCATION_AVAILABLE
};

enum GSM_STAGES {
	GSM_STAGE_INITIALIZATION = 0,
	GSM_STAGE_REINITIALIZATION,
	GSM_STAGE_WAIT_CIPSHUT_OK,
	GSM_STAGE_WAIT_AT_OK,
	GSM_STAGE_SEND_CGATT,
	GSM_STAGE_WAIT_CGATT_OK,
	GSM_STAGE_SEND_CSTT,
	GSM_STAGE_WAIT_CSTT_OK,
	GSM_STAGE_SEND_CIICR,
	GSM_STAGE_WAIT_CIICR_OK,
	GSM_STAGE_SEND_CIFSR,
	GSM_STAGE_WAIT_CIFSR_OK,
	GSM_STAGE_SEND_CIPSTART,
	GSM_STAGE_WAIT_CIPSTART_OK,
	GSM_STAGE_CONNECTED,

	GSM_STAGE_PREPARE_SEND_PLAIN_TOKEN,
	GSM_STAGE_SEND_PLAIN_TOKEN,
	GSM_STAGE_WAIT_PLAIN_TOKEN_OK,
	GSM_STAGE_PLAIN_TOKEN_ACCEPTED,
	GSM_STAGE_PREPARE_SEND_PRIVATE_TOKEN,
	GSM_STAGE_SEND_PRIVATE_TOKEN,
	GSM_STAGE_WAIT_PRIVATE_TOKEN_OK,
	GSM_STAGE_PRIVATE_TOKEN_ACCEPTED,

	GSM_STAGE_READY_FOR_LOCATION,
	GSM_STAGE_LOC_WAITING_FOR_SEND,
	GSM_STAGE_PREPARE_SEND_LOCATION,
	GSM_STAGE_SEND_LOCATION,
	GSM_STAGE_WAIT_LOCATION_OK
};

// Fila fixa de eventos: evita String, alocacao dinamica e fragmentacao de heap.
SoftwareSerial GsmSerial(7, 8);

class GSM {
private:
	static const uint8_t kLineBufferSize = 96;
	static const uint8_t kEventQueueSize = 8;

	uint8_t tcp_conn_stage = 0;
	unsigned long time = 0;
	char line_buffer[kLineBufferSize];
	uint8_t line_length = 0;
	bool line_overflow = false;

	GSMEvent event_queue[kEventQueueSize];
	uint8_t event_head = 0;
	uint8_t event_tail = 0;
	uint8_t event_count = 0;

	uint8_t identify_stage = 0;

	class LocationData {
	private:
		bool pendente = false;
		bool sending = false;
		double lat = 0.0;
		double lon = 0.0;

	public:
		void startSending() {
			sending = true;
		}

		bool isSending() {
			return sending;
		}

		void setLocation(double lat_, double lon_) {
			lat = lat_;
			lon = lon_;
			pendente = true;
		}

		bool getLocation(double &lat_, double &lon_) {
			if (!pendente) {
				return false;
			}
			lat_ = lat;
			lon_ = lon;
			return true;
		}

		void sucessfullySent() {
			pendente = false;
			sending = false;
		}

		void setNotSending() {
			sending = false;
		}

		bool isPendente() {
			return pendente;
		}

	} Location;

	void gsm_print(const char* msg, bool newline = false) {
		Serial.print(msg);
		if (newline) {
			Serial.println("");
		}
	}
	void gsm_println(const char* msg) {
		gsm_print(msg, true);
	}

	void updtTime() {
		time = millis();
	}

	bool passBy(unsigned long milis) {
		if (!milis) {
			return false;
		}
		return (millis() - time > milis);
	}

	void resetLine() {
		line_length = 0;
		line_overflow = false;
		line_buffer[0] = '\0';
	}

	void trimLine() {
		uint8_t start = 0;
		while (start < line_length && (line_buffer[start] == ' ' || line_buffer[start] == '\t' || line_buffer[start] == '\r')) {
			start++;
		}

		uint8_t end = line_length;
		while (end > start && (line_buffer[end - 1] == ' ' || line_buffer[end - 1] == '\t' || line_buffer[end - 1] == '\r')) {
			end--;
		}

		if (start > 0 && end > start) {
			memmove(line_buffer, line_buffer + start, end - start);
		}
		line_length = end - start;
		line_buffer[line_length] = '\0';
	}

	GSMEvent classifyLine() {
		gsm_print("LR");
		gsm_println(line_buffer);

		if (line_overflow) {
			return GSM_EVENT_UNKNOWN;
		}

		if (line_length == 0) {
			return GSM_EVENT_NONE;
		}

		if (strcmp(line_buffer, "OK") == 0) {
			return GSM_EVENT_OK;
		}

		if (strcmp(line_buffer, "SHUT OK") == 0) {
			return GSM_EVENT_SHUT_OK;
		}

		if (strcmp(line_buffer, "ERROR") == 0) {
			return GSM_EVENT_ERROR;
		}

		if (strcmp(line_buffer, "CLOSED") == 0) {
			return GSM_EVENT_CLOSED;
		}

		if (strcmp(line_buffer, "CONNECT OK") == 0) {
			return GSM_EVENT_CONNECT_OK;
		}

		if (strcmp(line_buffer, "SEND OK") == 0) {
			return GSM_EVENT_SEND_OK;
		}

		if (strcmp(line_buffer, "STATE: CONNECT OK") == 0) {
			return GSM_EVENT_CIPSTATE_CONNECT_OK;
		}

		if (strcmp(line_buffer, "STATE: TCP CLOSED") == 0) {
			return GSM_EVENT_TCP_SERVER_CLOSED;
		}
		if (strcmp(line_buffer, "+PDP: DEACT") == 0) {
			return GSM_EVENT_TCP_SERVER_CLOSED;
		}

		if (strcmp(line_buffer, "plain") == 0) {
			return GSM_EVENT_TCP_SERVER_RECEIVED_PLAIN_TOKEN;
		}
		
		if (strcmp(line_buffer, "priva") == 0) {
			return GSM_EVENT_TCP_SERVER_RECEIVED_PRIVATE_TOKEN;
		}


		if (line_length > 8) {
			if (line_buffer[0] >= '0' && line_buffer[0] <= '9') {
				return GSM_EVENT_IP;
			}
		}

		return GSM_EVENT_UNKNOWN;
	}

	void pushEvent(GSMEvent event) {
		if (event == GSM_EVENT_NONE) {
			return;
		}

		if (event_count >= kEventQueueSize) {
			return;
		}

		event_queue[event_tail] = event;
		event_tail = (event_tail + 1) % kEventQueueSize;
		event_count++;
	}

	bool popEvent(GSMEvent &event) {
		if (event_count == 0) {
			return false;
		}

		event = event_queue[event_head];
		event_head = (event_head + 1) % kEventQueueSize;
		event_count--;
		return true;
	}

	void pushLineEvent(bool prompt_line) {
		trimLine();

		if (prompt_line && line_length == 0) {
			pushEvent(GSM_EVENT_PROMPT);
			resetLine();
			return;
		}

		pushEvent(classifyLine());
		resetLine();
	}


	GSM_STAGES gsm_stage = GSM_STAGE_INITIALIZATION;

	int getEvent() {
		GSMEvent event;
		if (!nextEvent(event) || event == GSM_EVENT_NONE) return 0;
		gsm_print("ER");
		char evt_cstr[12];
		itoa(event, evt_cstr, 10);
		gsm_println(evt_cstr);
		

		switch (event) {
			case GSM_EVENT_OK: {
				gsm_println("eOK");
				if (gsm_stage == GSM_STAGE_WAIT_AT_OK) {
					gsm_stage = GSM_STAGE_SEND_CGATT;
				}
				else if (gsm_stage == GSM_STAGE_WAIT_CGATT_OK) {
					gsm_stage = GSM_STAGE_SEND_CSTT;
				}
				else if (gsm_stage == GSM_STAGE_WAIT_CSTT_OK) {
					gsm_stage = GSM_STAGE_SEND_CIICR;
				}
				else if (gsm_stage == GSM_STAGE_WAIT_CIICR_OK) {
					gsm_stage = GSM_STAGE_SEND_CIFSR;
				}
				else return -1;
				return 1;
			}
			case GSM_EVENT_TCP_SERVER_CLOSED: {
				gsm_println("eTSC");
				if (gsm_stage >= GSM_STAGE_WAIT_CIPSTART_OK) {
					gsm_stage = GSM_STAGE_SEND_CIFSR;
				}
				else return -1;
				return 1;
			}
			case GSM_EVENT_CONNECT_OK: {
				gsm_println("eCOK");
				if (gsm_stage == GSM_STAGE_WAIT_CIPSTART_OK) {
					gsm_stage = GSM_STAGE_CONNECTED;
				}
				else return -1;
				return 1;
			}
			case GSM_EVENT_SHUT_OK: {
				gsm_println("eSOK");
				if (gsm_stage == GSM_STAGE_WAIT_CIPSHUT_OK) {
					gsm_stage = GSM_STAGE_INITIALIZATION;
				}
				else return -1;
				return 1;
			}
			case GSM_EVENT_IP:{
				gsm_println("eIP");
				if (gsm_stage == GSM_STAGE_WAIT_CIFSR_OK) {
					gsm_stage = GSM_STAGE_SEND_CIPSTART;
				}
				else return -1;
				return 1;

			}
			case GSM_EVENT_ERROR: {
				gsm_print("eERR");
				char stage_cstr[12];
				itoa(gsm_stage, stage_cstr, 10);
				gsm_println(stage_cstr);
				if (gsm_stage == GSM_STAGE_WAIT_CGATT_OK) {
					gsm_stage = GSM_STAGE_INITIALIZATION;
				}
				else if (gsm_stage == GSM_STAGE_WAIT_CSTT_OK) {
					gsm_stage = GSM_STAGE_SEND_CIFSR;
				}
				else if (gsm_stage == GSM_STAGE_WAIT_CIICR_OK) {
					gsm_stage = GSM_STAGE_SEND_CIFSR;
				}
				else if (gsm_stage == GSM_STAGE_WAIT_CIFSR_OK) {
					gsm_stage = GSM_STAGE_REINITIALIZATION;
				}
				else if (gsm_stage == GSM_STAGE_WAIT_CIPSTART_OK) {
					gsm_stage = GSM_STAGE_REINITIALIZATION;
				}
				else if (gsm_stage == GSM_STAGE_CONNECTED) {
					gsm_stage = GSM_STAGE_REINITIALIZATION;
				}
				else if (gsm_stage == GSM_STAGE_PREPARE_SEND_LOCATION) {
					gsm_stage = GSM_STAGE_SEND_CIFSR;
				}
				else return -1;
				return 1;
			}
			case GSM_EVENT_CLOSED: {
				gsm_println("eC");			
				if (gsm_stage >= GSM_STAGE_CONNECTED) {
					gsm_stage = GSM_STAGE_SEND_CIPSTART;
				}
				else return -1;
				return 1;
			}

			
			case GSM_EVENT_PROMPT: {
				gsm_println("eP");			
				if (gsm_stage == GSM_STAGE_PREPARE_SEND_PLAIN_TOKEN) {
					gsm_stage = GSM_STAGE_SEND_PLAIN_TOKEN;
				}
				else if (gsm_stage == GSM_STAGE_PREPARE_SEND_PRIVATE_TOKEN) {
					gsm_stage = GSM_STAGE_SEND_PRIVATE_TOKEN;
				}
				else if (gsm_stage == GSM_STAGE_PREPARE_SEND_LOCATION) {
					gsm_stage = GSM_STAGE_SEND_LOCATION;
				}
				else return -1;
				return 1;
			}
			case GSM_EVENT_TCP_SERVER_RECEIVED_PLAIN_TOKEN: {
				gsm_println("eTSRPT");
				if (gsm_stage == GSM_STAGE_WAIT_PLAIN_TOKEN_OK) {
					gsm_stage = GSM_STAGE_PLAIN_TOKEN_ACCEPTED;
				}
				else return -1;
				return 1;
			}
			case GSM_EVENT_TCP_SERVER_RECEIVED_PRIVATE_TOKEN: {
				gsm_println("eTSRPRT");
				if (gsm_stage == GSM_STAGE_WAIT_PRIVATE_TOKEN_OK) {
					gsm_stage = GSM_STAGE_PRIVATE_TOKEN_ACCEPTED;
				}
				else return -1;
				return 1;
			}
			case GSM_EVENT_NEW_LOCATION_AVAILABLE: {
				gsm_println("eL");
				if (gsm_stage == GSM_STAGE_READY_FOR_LOCATION) {
					gsm_stage = GSM_STAGE_LOC_WAITING_FOR_SEND;
				}
				else return -1;
				return 1;
			}
			case GSM_EVENT_SEND_OK: {
				gsm_println("eS");
				if (gsm_stage == GSM_STAGE_WAIT_LOCATION_OK) {
					Location.sucessfullySent();
					gsm_stage = GSM_STAGE_PRIVATE_TOKEN_ACCEPTED;
				}
				else return -1;
				return 1;
			}
			
		}
		
		return -2;
	}

	
	class LastStage {
		inline static GSM_STAGES stage = GSM_STAGE_INITIALIZATION;
		inline static GSM_STAGES stageIfTimedOut = GSM_STAGE_REINITIALIZATION;
		inline static int retries = 0;
		inline static unsigned long wait_timeout = 0;
	public:
		static void setStage(GSM_STAGES stage_, unsigned long wait_timeout_, GSM_STAGES stageIfTimedOut_, int retries_) {
			if (stage_ == stage) return;
			stage = stage_;
			wait_timeout = wait_timeout_;
			stageIfTimedOut = stageIfTimedOut_;
			retries = retries_;
		}

		static unsigned long getWaitTimeout() {
			return wait_timeout;
		}

		static GSM_STAGES getTimedOutStage() {
			if (retries--) {
				return stage;
			}
			return stageIfTimedOut;
		}
	};


public:
	GSM() {
		resetLine();
	}

	void poll() {
		// Fazer o arduino trocar para esse software serial:
		GsmSerial.listen();
		
		unsigned long time_limit = millis() + 100;
		while (millis() < time_limit) {
			int qnt = GsmSerial.available();
			while (qnt--) {
				char c = static_cast<char>(GsmSerial.read());

				if (c == '\r') {
					continue;
				}

				if (c == '>') {
					pushLineEvent(true);
					continue;
				}

				if (c == '\n') {
					if (line_length < 2) {
						resetLine();
						continue;
					};
					pushLineEvent(false);
					continue;
				}

				if (c < 32 || c > 126) {
					continue;
				}

				if (line_length < kLineBufferSize - 1) {
					line_buffer[line_length++] = c;
					line_buffer[line_length] = '\0';
				} else {
					line_overflow = true;
				}
			}
		}
		
		if (hasLocationToSend() && !Location.isSending()) {
			Location.startSending();
			pushEvent(GSM_EVENT_NEW_LOCATION_AVAILABLE);
		}
	}

	bool nextEvent(GSMEvent &event) {
		return popEvent(event);
	}

	int runStagesMachine() {
		int evt = getEvent();
		if (evt <= 0) {
			if (passBy(LastStage::getWaitTimeout())) {
				gsm_println("geTO");
				gsm_stage = LastStage::getTimedOutStage();
			} else if (gsm_stage != GSM_STAGE_INITIALIZATION) return evt;
		}
		updtTime();

		switch (gsm_stage) {
			case GSM_STAGE_INITIALIZATION: {
				gsm_println("sCT");
				GsmSerial.println("ATE0");
				LastStage::setStage(gsm_stage, 2000UL, GSM_STAGE_INITIALIZATION, 0);
				gsm_stage = GSM_STAGE_WAIT_AT_OK;
				return GSM_STAGE_INITIALIZATION;
			}
			case GSM_STAGE_REINITIALIZATION: {
				gsm_println("sRCT");
				GsmSerial.println("AT+CIPSHUT");
				LastStage::setStage(gsm_stage, 10000UL, GSM_STAGE_INITIALIZATION, 0);
				gsm_stage = GSM_STAGE_WAIT_CIPSHUT_OK;
				return GSM_STAGE_REINITIALIZATION;
			}
			case GSM_STAGE_SEND_CGATT: {
				gsm_println("CGATT");
				GsmSerial.println("AT+CGATT=1");
				LastStage::setStage(gsm_stage, 10000UL, GSM_STAGE_REINITIALIZATION, 1);
				gsm_stage = GSM_STAGE_WAIT_CGATT_OK;
				return GSM_STAGE_SEND_CGATT;
			}
			case GSM_STAGE_SEND_CSTT: {
				gsm_println("CSTT");
				GsmSerial.println("AT+CSTT=\"agnc.algar.br\",\"algar\",\"1212\"");
				LastStage::setStage(gsm_stage, 10000UL, GSM_STAGE_REINITIALIZATION, 1);
				gsm_stage = GSM_STAGE_WAIT_CSTT_OK;
				return GSM_STAGE_SEND_CSTT;
			}
			case GSM_STAGE_SEND_CIICR: {
				gsm_println("CIICR");
				GsmSerial.println("AT+CIICR");
				LastStage::setStage(gsm_stage, 10000UL, GSM_STAGE_REINITIALIZATION, 1);
				gsm_stage = GSM_STAGE_WAIT_CIICR_OK;
				return GSM_STAGE_SEND_CIICR;
			}
			case GSM_STAGE_SEND_CIFSR: {
				gsm_println("CIFSR");
				GsmSerial.println("AT+CIFSR");
				LastStage::setStage(gsm_stage, 10000UL, GSM_STAGE_REINITIALIZATION, 1);
				gsm_stage = GSM_STAGE_WAIT_CIFSR_OK;
				return GSM_STAGE_SEND_CIFSR;
			}
			case GSM_STAGE_SEND_CIPSTART: {
				gsm_println("CIPSTART");
				GsmSerial.println("AT+CIPSTART=\"TCP\",\"138.97.218.44\",\"12346\"");
				LastStage::setStage(gsm_stage, 65000UL, GSM_STAGE_REINITIALIZATION, 1);
				gsm_stage = GSM_STAGE_WAIT_CIPSTART_OK;
				return GSM_STAGE_SEND_CIPSTART;
			}


			case GSM_STAGE_CONNECTED: {
				gsm_println("sPSPT");
				GsmSerial.println("AT+CIPSEND=5");
				LastStage::setStage(gsm_stage, 2000UL, GSM_STAGE_REINITIALIZATION, 1);
				gsm_stage = GSM_STAGE_PREPARE_SEND_PLAIN_TOKEN;
				return GSM_STAGE_CONNECTED;
			}
			case GSM_STAGE_SEND_PLAIN_TOKEN: {
				gsm_println("sSPT");
				GsmSerial.write("teste");
				GsmSerial.write(0x1A);
				LastStage::setStage(gsm_stage, 65000UL, GSM_STAGE_CONNECTED, 0);
				gsm_stage = GSM_STAGE_WAIT_PLAIN_TOKEN_OK;
				return GSM_STAGE_SEND_PLAIN_TOKEN;
			}
			case GSM_STAGE_PLAIN_TOKEN_ACCEPTED: {
				gsm_println("sPSPRT");
				GsmSerial.println("AT+CIPSEND=26");
				LastStage::setStage(gsm_stage, 2000UL, GSM_STAGE_REINITIALIZATION, 1);
				gsm_stage = GSM_STAGE_PREPARE_SEND_PRIVATE_TOKEN;
				return GSM_STAGE_PLAIN_TOKEN_ACCEPTED;
			}
			case GSM_STAGE_SEND_PRIVATE_TOKEN: {
				gsm_println("sSPRT");
				uint8_t private_token[] = {
					0x6D, 0x7E, 0x4C, 0xB9,
					0x71, 0xF2, 0x6D, 0x51,
					0x8F, 0x71, 0x1F, 0x3B,
					0xF7, 0xF7, 0x96, 0xD9,
					0xE6, 0x6F, 0x88, 0x72,
					0x81, 0x7A, 0x40, 0x54,
					0x97, 0xDB
				};
				GsmSerial.write(private_token, sizeof(private_token));
				GsmSerial.write(0x1A);
				LastStage::setStage(gsm_stage, 65000UL, GSM_STAGE_PLAIN_TOKEN_ACCEPTED, 0);
				gsm_stage = GSM_STAGE_WAIT_PRIVATE_TOKEN_OK;
				return GSM_STAGE_SEND_PRIVATE_TOKEN;
			}
			case GSM_STAGE_PRIVATE_TOKEN_ACCEPTED: {
				gsm_println("sRL");
				Location.setNotSending();
				LastStage::setStage(gsm_stage, 0, GSM_STAGE_REINITIALIZATION, 0);
				gsm_stage = GSM_STAGE_READY_FOR_LOCATION;
				return GSM_STAGE_PRIVATE_TOKEN_ACCEPTED;
			}
			case GSM_STAGE_LOC_WAITING_FOR_SEND: {
				gsm_println("sPSL");
				GsmSerial.println("AT+CIPSEND=3");
				LastStage::setStage(gsm_stage, 2000UL, GSM_STAGE_SEND_CIFSR, 1);
				gsm_stage = GSM_STAGE_PREPARE_SEND_LOCATION;
				return GSM_STAGE_LOC_WAITING_FOR_SEND;
			}
			case GSM_STAGE_SEND_LOCATION: {
				gsm_println("sSL");
				double lat, lon;
				Location.getLocation(lat, lon);

				GsmSerial.write("Loc");
				GsmSerial.write(0x1A);
				LastStage::setStage(gsm_stage, 3000UL, GSM_STAGE_PRIVATE_TOKEN_ACCEPTED, 1);
				gsm_stage = GSM_STAGE_WAIT_LOCATION_OK;
				return GSM_STAGE_SEND_LOCATION;
			}

		}
		return -3;
	}

	bool hasLocationToSend() {
		return Location.isPendente();
	}

	void setLocationToSend(double lat, double lon) {
		Location.setLocation(lat, lon);
	}

	bool isStage(GSM_STAGES stage) {
		return gsm_stage == stage;
	}

};

GSM GSM;

#endif