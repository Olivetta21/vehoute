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
	GSM_EVENT_TCP_SERVER_RECEIVED_PRIVATE_TOKEN
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

	GSM_STAGE_READY_FOR_LOCATION
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

	uint8_t send_location_stage = 0;
	struct {
		double lat;
		double lon;
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
		gsm_print("line received: (");
		gsm_print(line_buffer);
		gsm_println(")");

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

	static int tmp = 0;

	int getEvent() {
		GSMEvent event;
		if (!nextEvent(event) || event == GSM_EVENT_NONE) return 0;
		gsm_print("event received: (");
		char evt_cstr[12];
		itoa(event, evt_cstr, 10);
		gsm_print(evt_cstr);
		gsm_println(")");
		

		switch (event) {
			case GSM_EVENT_OK: {
				Serial.print(tmp++);
				gsm_println("GET EVENT: OK");
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
				gsm_println("GET EVENT: TCP SERVER CLOSED");
				if (gsm_stage == GSM_STAGE_WAIT_CIPSTART_OK) {
					gsm_stage = GSM_STAGE_SEND_CIPSTART;
				}
				else return -1;
				return 1;
			}
			case GSM_EVENT_CONNECT_OK: {
				gsm_println("GET EVENT: CONNECT OK");
				if (gsm_stage == GSM_STAGE_WAIT_CIPSTART_OK) {
					gsm_stage = GSM_STAGE_CONNECTED;
				}
				else return -1;
				return 1;
			}
			case GSM_EVENT_SHUT_OK: {
				gsm_println("GET EVENT: SHUT OK");
				if (gsm_stage == GSM_STAGE_WAIT_CIPSHUT_OK) {
					gsm_stage = GSM_STAGE_INITIALIZATION;
				}
				else return -1;
				return 1;
			}
			case GSM_EVENT_IP:{
				gsm_println("GET EVENT: IP");
				if (gsm_stage == GSM_STAGE_WAIT_CIFSR_OK) {
					gsm_stage = GSM_STAGE_SEND_CIPSTART;
				}
				else return -1;
				return 1;

			}
			case GSM_EVENT_ERROR: {
				gsm_print("GET EVENT: ERROR at stage (");
				char stage_cstr[12];
				itoa(gsm_stage, stage_cstr, 10);
				gsm_print(stage_cstr);
				gsm_println(")");
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
				else return -1;
				return 1;
			}
			case GSM_EVENT_CLOSED: {
				gsm_println("GET EVENT: CLOSED");			
				if (gsm_stage >= GSM_STAGE_CONNECTED) {
					gsm_stage = GSM_STAGE_SEND_CIPSTART;
				}
				else return -1;
				return 1;
			}

			
			case GSM_EVENT_PROMPT: {
				gsm_println("GET EVENT: PROMPT");			
				if (gsm_stage == GSM_STAGE_PREPARE_SEND_PLAIN_TOKEN) {
					gsm_stage = GSM_STAGE_SEND_PLAIN_TOKEN;
				}
				else if (gsm_stage == GSM_STAGE_PREPARE_SEND_PRIVATE_TOKEN) {
					gsm_stage = GSM_STAGE_SEND_PRIVATE_TOKEN;
				}
				else return -1;
				return 1;
			}
			case GSM_EVENT_TCP_SERVER_RECEIVED_PLAIN_TOKEN: {
				gsm_println("GET EVENT: TCP SERVER RECEIVED PLAIN TOKEN");
				if (gsm_stage == GSM_STAGE_WAIT_PLAIN_TOKEN_OK) {
					gsm_stage = GSM_STAGE_PLAIN_TOKEN_ACCEPTED;
				}
				else return -1;
				return 1;
			}
			case GSM_EVENT_TCP_SERVER_RECEIVED_PRIVATE_TOKEN: {
				gsm_println("GET EVENT: TCP SERVER RECEIVED PRIVATE TOKEN");
				if (gsm_stage == GSM_STAGE_WAIT_PRIVATE_TOKEN_OK) {
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
		static GSM_STAGES setStage(GSM_STAGES stage_, unsigned long wait_timeout_, GSM_STAGES stageIfTimedOut_, int retries_){
			stage = stage_;
			wait_timeout = wait_timeout_;
			return stage;
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
	bool identificated = false;
	bool isSendingLocation = false;

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
	}

	bool nextEvent(GSMEvent &event) {
		return popEvent(event);
	}

	int runStagesMachine() {
		int evt = getEvent();
		if (evt <= 0) {
			if (passBy(LastStage::getWaitTimeout())) {
				gsm_println("GET EVENT: timed out");
				gsm_stage = LastStage::getTimedOutStage();
			} else if (gsm_stage != GSM_STAGE_INITIALIZATION) return evt;
		}
		updtTime();

		switch (gsm_stage) {
			case GSM_STAGE_INITIALIZATION: {
				gsm_println("trying to connect TCP");
				GsmSerial.println("ATE0");
				LastStage::setStage(gsm_stage, 2000UL, GSM_STAGE_INITIALIZATION, 0);
				gsm_stage = GSM_STAGE_WAIT_AT_OK;
				return GSM_STAGE_INITIALIZATION;
			}
			case GSM_STAGE_REINITIALIZATION: {
				gsm_println("trying to reconnect TCP");
				GsmSerial.println("AT+CIPSHUT");
				LastStage::setStage(gsm_stage, 10000UL, GSM_STAGE_INITIALIZATION, 0);
				gsm_stage = GSM_STAGE_WAIT_CIPSHUT_OK;
				return GSM_STAGE_REINITIALIZATION;
			}
			case GSM_STAGE_SEND_CGATT: {
				gsm_println("OK received, sending CGATT");
				GsmSerial.println("AT+CGATT=1");
				LastStage::setStage(gsm_stage, 10000UL, GSM_STAGE_REINITIALIZATION, 1);
				gsm_stage = GSM_STAGE_WAIT_CGATT_OK;
				return GSM_STAGE_SEND_CGATT;
			}
			case GSM_STAGE_SEND_CSTT: {
				gsm_println("CGATT received, sending CSTT");
				GsmSerial.println("AT+CSTT=\"agnc.algar.br\",\"algar\",\"1212\"");
				LastStage::setStage(gsm_stage, 10000UL, GSM_STAGE_REINITIALIZATION, 1);
				gsm_stage = GSM_STAGE_WAIT_CSTT_OK;
				return GSM_STAGE_SEND_CSTT;
			}
			case GSM_STAGE_SEND_CIICR: {
				gsm_println("CSTT is ok, sending CIICR");
				GsmSerial.println("AT+CIICR");
				LastStage::setStage(gsm_stage, 10000UL, GSM_STAGE_REINITIALIZATION, 1);
				gsm_stage = GSM_STAGE_WAIT_CIICR_OK;
				return GSM_STAGE_SEND_CIICR;
			}
			case GSM_STAGE_SEND_CIFSR: {
				gsm_println("CIICR is ok, sending CIFSR");
				GsmSerial.println("AT+CIFSR");
				LastStage::setStage(gsm_stage, 10000UL, GSM_STAGE_REINITIALIZATION, 1);
				gsm_stage = GSM_STAGE_WAIT_CIFSR_OK;
				return GSM_STAGE_SEND_CIFSR;
			}
			case GSM_STAGE_SEND_CIPSTART: {
				gsm_println("CIFSR is ok, sending CIPSTART");
				GsmSerial.println("AT+CIPSTART=\"TCP\",\"138.97.218.44\",\"12346\"");
				LastStage::setStage(gsm_stage, 65000UL, GSM_STAGE_REINITIALIZATION, 1);
				gsm_stage = GSM_STAGE_WAIT_CIPSTART_OK;
				return GSM_STAGE_SEND_CIPSTART;
			}


			case GSM_STAGE_CONNECTED: {
				gsm_println("TCP task finished");
				gsm_println("Preparing to send plain token to server");
				GsmSerial.println("AT+CIPSEND=5");
				LastStage::setStage(gsm_stage, 2000UL, GSM_STAGE_REINITIALIZATION, 1);
				gsm_stage = GSM_STAGE_PREPARE_SEND_PLAIN_TOKEN;
				return GSM_STAGE_CONNECTED;
			}
			case GSM_STAGE_SEND_PLAIN_TOKEN: {
				gsm_println("Sending plain token");
				GsmSerial.write("teste");
				GsmSerial.write(0x1A);
				LastStage::setStage(gsm_stage, 65000UL, GSM_STAGE_CONNECTED, 0);
				gsm_stage = GSM_STAGE_WAIT_PLAIN_TOKEN_OK;
				return GSM_STAGE_SEND_PLAIN_TOKEN;
			}
			case GSM_STAGE_PLAIN_TOKEN_ACCEPTED: {
				gsm_println("Plain token accepted");
				gsm_println("Preparing to send private token to server");
				GsmSerial.println("AT+CIPSEND=26");
				LastStage::setStage(gsm_stage, 2000UL, GSM_STAGE_REINITIALIZATION, 1);
				gsm_stage = GSM_STAGE_PREPARE_SEND_PRIVATE_TOKEN;
				return GSM_STAGE_PLAIN_TOKEN_ACCEPTED;
			}
			case GSM_STAGE_SEND_PRIVATE_TOKEN: {
				gsm_println("Sending private token");
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
				gsm_println("Private token accepted");
				LastStage::setStage(gsm_stage, 0, GSM_STAGE_REINITIALIZATION, 1);
				gsm_stage = GSM_STAGE_READY_FOR_LOCATION;
				return GSM_STAGE_PRIVATE_TOKEN_ACCEPTED;
			}

		}
		return -3;
	}

	bool setLocationToSend(double lat, double lon) {
		if (isSendingLocation || send_location_stage != 0) {
			return false;
		}

		Location.lat = lat;
		Location.lon = lon;
		
		isSendingLocation = true;
		send_location_stage = 0;
		
		return true;
	}

	bool isStage(GSM_STAGES stage) {
		return gsm_stage == stage;
	}

	void setListen() {
		GsmSerial.listen();
	}
};

GSM GSM;

#endif