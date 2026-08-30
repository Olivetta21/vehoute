#include "Crypt.h"

void printBytes(const byte* data, byte size) {
    for (byte i = 0; i < size; i++) {
        Serial.print(static_cast<int>(data[i]));
        Serial.print(" ");
    }
    Serial.println(" ");
}

const byte masterKey[] PROGMEM = {0x23, 0x45, 0x67, 0x89, 0xAB, 0xCD, 0xEF, 0x01, 0x23, 0x45, 0x67, 0x89, 0xAB, 0xCD, 0xEF, 0x01};

void setup() {
  Serial.begin(9600);
  delay(2000);
  Serial.print("\ninit:");
  randomSeed(analogRead(0));
  Serial.println("");
  {
    byte tmp[KEY_SIZE] = {0};
    for (byte i = 0; i < KEY_SIZE; i++) {
      tmp[i] = pgm_read_byte(&masterKey[i]);
    }
    Crypt::setKeys(tmp);
  }
}

void loop() {
  // put your main code here, to run repeatedly:
  
  byte data[BUFFER_SIZE] = {0x01, 0x02, 0x03, 0x04, 0x05, 0x06, 0x07, 0x08, 0x09};
  byte dataSize = 9;
  Serial.println(F("Original data: "));
  printBytes(data, dataSize);

  byte encryptedSize = Crypt::encrypt(data, dataSize);
  Serial.println(F("Encrypted data: "));
  printBytes(data, encryptedSize);

  byte decryptedSize = Crypt::decrypt(data, encryptedSize);
  Serial.println(F("Decrypted data: "));
  printBytes(data, decryptedSize);
  delay(10000);

}
