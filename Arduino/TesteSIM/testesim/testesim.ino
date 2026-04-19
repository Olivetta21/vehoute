#include <SoftwareSerial.h>

SoftwareSerial sim800(7, 8); // RX, TX

String buffer = "";

void setup() {
  delay(5000);
  Serial.begin(9600);
  sim800.begin(9600);
  delay(1000);
  Serial.println("Iniciando...");


  Serial.println("Digite: send:mensagem");
}

void loop() {
  // Captura entrada do Serial do PC
  if (Serial.available()) {
    char c = Serial.read();
    buffer += c;

    // Quando chegar um Enter (\n ou \r), processa
    if (c == '\n') {
      buffer.trim(); // remove espaços e \r\n

      if (buffer.startsWith("send:")) {
        String msg = buffer.substring(5); // pega só a mensagem após "send:"
        sendMessage(msg);
      }
      else {
        sim800.println(buffer);
      }

      buffer = ""; // limpa para próxima entrada
    }
  }

  // Caso queira ler resposta do SIM800
  if (sim800.available()) {
    Serial.write(sim800.read());
  }
}

void sendMessage(String msg) {
  Serial.println("Enviando: " + msg);

  // Envia comando de envio de dados
  sim800.println("AT+CIPSEND");
  delay(100);

  // Envia a mensagem
  sim800.print(msg);

  // Envia CTRL+Z (0x1A)
  sim800.write(0x1A);

  Serial.println("Mensagem enviada!");
}
