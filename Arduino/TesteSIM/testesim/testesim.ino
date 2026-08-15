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

void sendMensByte(String textoHex) {
  Serial.println("EnviandoB: " + textoHex);
  int tamanho = textoHex.length() / 2;
  sim800.print("AT+CIPSEND=");
  sim800.println(tamanho);
  delay(100);

  for (unsigned int i = 0; i < textoHex.length(); i += 2) {
    String byteString = textoHex.substring(i, i + 2); // Converte o par de letras hexa para um número de 0 a 255
    byte valorByte = strtol(byteString.c_str(), NULL, 16);
    sim800.write(valorByte); // Envia o byte puro
  }
  sim800.write(0x1A);
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
      else if (buffer.startsWith("byte:")) {
        String msg = buffer.substring(5); // pega só a mensagem após "send:"
        sendMensByte(msg);
      }
      else {
        Serial.println("Comando: " + buffer);
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
