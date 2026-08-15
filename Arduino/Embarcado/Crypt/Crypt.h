#pragma once

#include <stdint.h>
#include <stdlib.h>
#include <string.h>

class Crypt {
public:
  static constexpr uint8_t KEY_SIZE = 16;
  static constexpr uint8_t IV_SIZE = 16;
  static constexpr uint8_t BUFFER_SIZE = 33;
  static constexpr uint8_t CHECKSUM_SIZE = 2;
  static constexpr uint8_t ROUNDS = 2;

private:
  uint8_t keys[ROUNDS][KEY_SIZE];

public:
  Crypt() { memset(keys, 0, sizeof(keys)); }

  void generateSubkeys() {
    for (uint8_t i = 1; i < ROUNDS; i++) {
      for (uint8_t j = 0; j < KEY_SIZE; j++) {
        uint8_t prevByte = keys[i - 1][j];

        keys[i][j] = (uint8_t)((((prevByte << 3) & 0xFF) | (prevByte >> 5)) ^
                               (j + i * 7));
      }
    }
  }

  uint8_t encodeByte(uint8_t byte, uint8_t key, uint8_t mod) {
    switch (mod) {
    case 0:
      byte ^= key;
      break;

    case 1:
      byte += key;
      break;

    default:
      byte = (uint8_t)((((byte << 4) | (byte >> 4)) ^ key));
      break;
    }

    return byte;
  }

  uint8_t decodeByte(uint8_t byte, uint8_t key, uint8_t mod) {
    switch (mod) {
    case 0:
      byte ^= key;
      break;

    case 1:
      byte -= key;
      break;

    default: {
      uint8_t tmp = byte ^ key;
      byte = (uint8_t)((tmp >> 4) | (tmp << 4));
      break;
    }
    }

    return byte;
  }

  void encDecByteArray(uint8_t (&buffer)[BUFFER_SIZE], uint8_t length,
                       uint8_t round, bool encode) {
    for (uint8_t i = 0; i < length; i++) {
      uint8_t mod = (i + round) % 3;
      uint8_t key = keys[round][i % KEY_SIZE];

      if (encode)
        buffer[i] = encodeByte(buffer[i], key, mod);
      else
        buffer[i] = decodeByte(buffer[i], key, mod);
    }
  }

  bool genAndInsertIV(uint8_t (&buffer)[BUFFER_SIZE], uint8_t &length) {
    if (length + IV_SIZE + CHECKSUM_SIZE > BUFFER_SIZE)
      return false;

    memmove(buffer + IV_SIZE, buffer, length);

    for (uint8_t i = 0; i < IV_SIZE; i++)
      buffer[i] = random(256);

    length += IV_SIZE;

    return true;
  }

  bool genAndInsertChecksum(uint8_t (&buffer)[BUFFER_SIZE], uint8_t &length) {
    if (length + CHECKSUM_SIZE > BUFFER_SIZE)
      return false;

    uint16_t checksum = 0;

    for (uint8_t i = 0; i < length; i++)
      checksum += buffer[i];

    for (uint8_t i = 0; i < CHECKSUM_SIZE; i++) {
      buffer[length++] = (checksum >> (8 * (CHECKSUM_SIZE - 1 - i))) & 0xFF;
    }

    return true;
  }

  bool deObFuscateWithIV(uint8_t (&buffer)[BUFFER_SIZE], uint8_t length) {
    if (length <= IV_SIZE)
      return false;

    for (uint8_t i = IV_SIZE; i < length; i++)
      buffer[i] ^= buffer[i % IV_SIZE];

    return true;
  }

  bool testAndRemoveChecksum(uint8_t (&buffer)[BUFFER_SIZE], uint8_t &length) {
    uint16_t constructedChecksum = 0;

    for (uint8_t i = 0; i < CHECKSUM_SIZE; i++) {
      constructedChecksum |= (uint16_t)buffer[length - CHECKSUM_SIZE + i]
                             << (8 * (CHECKSUM_SIZE - 1 - i));
    }

    for (uint8_t i = 0; i < length - CHECKSUM_SIZE; i++)
      constructedChecksum -= buffer[i];

    if (constructedChecksum != 0)
      return false;

    length -= CHECKSUM_SIZE;

    return true;
  }

  bool encrypt(uint8_t (&buffer)[BUFFER_SIZE], uint8_t &length) {
    if (!genAndInsertIV(buffer, length))
      return false;

    if (!genAndInsertChecksum(buffer, length))
      return false;

    if (!deObFuscateWithIV(buffer, length))
      return false;

    for (uint8_t round = 0; round < ROUNDS; round++)
      encDecByteArray(buffer, length, round, true);

    return true;
  }

  bool decrypt(uint8_t (&buffer)[BUFFER_SIZE], uint8_t &length) {
    for (int8_t round = ROUNDS - 1; round >= 0; round--)
      encDecByteArray(buffer, length, round, false);

    if (!deObFuscateWithIV(buffer, length))
      return false;

    if (!testAndRemoveChecksum(buffer, length))
      return false;

    if (IV_SIZE >= length)
      return false;

    memmove(buffer, buffer + IV_SIZE, length - IV_SIZE);

    length -= IV_SIZE;

    return true;
  }

  void setKeys(const uint8_t (&key)[KEY_SIZE]) {
    memcpy(keys[0], key, KEY_SIZE);
    generateSubkeys();
  }
};
