#include "Crypt.h"
#include <iomanip>
#include <iostream>
#include <stdint.h>


using namespace std;

void printArray(const uint8_t *buffer, uint8_t length) {
  cout << "[";

  for (uint8_t i = 0; i < length; i++) {
    cout << (int)buffer[i];

    if (i + 1 != length)
      cout << ", ";
  }

  cout << "]" << endl;
}

int main() {
  srand(time(nullptr));
  Crypt cr;

  uint8_t key[Crypt::KEY_SIZE] = {0x23, 0x45, 0x67, 0x89, 0xAB, 0xCD,
                                  0xEF, 0x01, 0x23, 0x45, 0x67, 0x89,
                                  0xAB, 0xCD, 0xEF, 0x01};

  cr.setKeys(key);

  //------------------------------------------------------
  // Mesmo teste do Python
  //------------------------------------------------------

  uint8_t arr[Crypt::BUFFER_SIZE] = {1, 2, 3, 4, 10, 5, 6, 7, 8, 9};

  uint8_t length = 10;

  cout << "Before: ";
  printArray(arr, length);

  if (cr.encrypt(arr, length)) {
    cout << "Encrypted: ";
    printArray(arr, length);
  } else {
    cout << "Encryption failed." << endl;
  }

  if (cr.decrypt(arr, length)) {
    cout << "Decrypted: ";
    printArray(arr, length);
  } else {
    cout << "Decrypt failed." << endl;
  }

  //------------------------------------------------------
  // Teste com pacote criptografado vindo do Python
  //------------------------------------------------------

  uint8_t encr[Crypt::BUFFER_SIZE] = {
      104, 141, 136, 254, 253, 128, 196, 9,   14,  27,  1,   153, 92, 230,
      172, 178, 166, 28,  235, 173, 93,  155, 232, 121, 175, 5,   188};

  uint8_t encrLength = 27;

  bool res = cr.decrypt(encr, encrLength);

  cout << "Other Decrypted: ";

  if (res)
    printArray(encr, encrLength);
  else
    cout << "Failed" << endl;

  return 0;
}
