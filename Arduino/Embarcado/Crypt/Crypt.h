#ifndef EMBARCADO_CRYPT_H
#define EMBARCADO_CRYPT_H

typedef unsigned short b2byte;

#define ISARDUINO
#ifdef ISARDUINO
    #define RANDOM_INTEGER_FUNCTION random()
#else
    #include <iostream>
    typedef unsigned char byte;
    #define RANDOM_INTEGER_FUNCTION std::rand()
    #define PROGMEM
#endif

// Encryption config
#define KEY_SIZE 16
#define IV_SIZE 16
#define BUFFER_SIZE 33
#define CHECKSUM_SIZE 2
#define ROUNDS 2
//

// Test
#if IV_SIZE + CHECKSUM_SIZE >= BUFFER_SIZE
#error "buffer size too small"
#endif


class Crypt {
    private:
    inline static byte keys[ROUNDS][KEY_SIZE];

    /**
     * @brief Generate subkeys for encryption and decryption.
     * @note Runs only at initialization, immediately after key set.
     *
     */
    static void generateSubkeys(){
        for (byte i = 1; i < ROUNDS; i++) {
            for (byte j = 0; j < KEY_SIZE; j++) {
                byte prevByte = keys[i-1][j];

                keys[i][j] = ((prevByte << 3) | (prevByte >> 5)) ^ (j + i * 7);
            }
        }
    }

    /**
     * @brief Encode a single byte.
     * @param byte_ The byte to encode.
     * @param key The key to use for encoding.
     * @param mod The mode to use for encoding.
     * @return The encoded byte.
     *
     */
    static byte encodeByte(byte byte_, byte key, byte mod){
      switch (mod) {
        case 0:
          // XOR
          byte_ ^= key;
          break;
        case 1:
          // Addition
          byte_ += key;
          break;
        default:
          // Substitution + XOR
          byte_ = ((byte_ << 4) | (byte_ >> 4)) ^ key;
      }
      return byte_;
    }

    /**
     * @brief Decode a single byte.
     * @param byte_ The byte to decode.
     * @param key The key to use for decoding.
     * @param mod The mode to use for decoding.
     * @return The decoded byte.
     *
     */
    static byte decodeByte(byte byte_, byte key, byte mod){
      switch (mod) {
        case 0:
          // XOR
          byte_ ^= key;
          break;
        case 1:
          // Subtraction
          byte_ -= key;
          break;
        default:
          // XOR + inverse substitution
          byte temp = byte_ ^ key;
          byte_ = (temp >> 4) | (temp << 4);
      }
      return byte_;
    }


    /**
     * @brief Encrypt or decrypt an array of bytes.
     * @param data Pointer to the array to be encrypted or decrypted.
     * @param size Size of the array.
     * @param round Round number.
     * @param operation Pointer to the encryption or decryption function.
     * @note This function does not care about the result. Good luck!
     *
     */
    static void enc_dec_byteArray(byte* data, byte size, byte round, byte (*operation)(byte, byte, byte)){
        for (byte i = 0; i < size; i++) {
            data[i] = operation(
                data[i],
                keys[round][i % KEY_SIZE],
                ((i + round) % 3)
            );
        }
    }

    /**
     * @brief Generate and insert an IV in the beginning of the array.
     * @param data Pointer to the array to be inserted into.
     * @param size Size of the array.
     * @return The new size of the data including the IV, Zero if failed.
     *
     */
    static byte genAndInsertIV(byte* data, byte size) {
        if (size + IV_SIZE + CHECKSUM_SIZE > BUFFER_SIZE) {
            return 0;
        }
        byte temp[size];
        memcpy(temp, data, size);
        memcpy(data + IV_SIZE, temp, size);

        for (byte i = 0; i < IV_SIZE; i++) {
            data[i] = (byte) (RANDOM_INTEGER_FUNCTION % 255);
        }

        return size + IV_SIZE;
    }

    /**
     * @brief Generate and insert a checksum at the end of the array.
     * @param data Pointer to the array to be inserted into.
     * @param size Size of the array.
     * @return The new size of the data including the checksum, Zero if failed.
     *
     */
    static byte genAndInsertChecksum(byte* data, byte size) {
        if (size + CHECKSUM_SIZE > BUFFER_SIZE) {
            return 0;
        }
        b2byte checksum = 0;
        if (sizeof(checksum) / sizeof(byte) != CHECKSUM_SIZE) {
            return 0;
        }
        for (byte i = 0; i < size; i++) {
            checksum += data[i];
        }

        byte nibbles[CHECKSUM_SIZE] = {0};
        for (byte i = 0; i < CHECKSUM_SIZE; i++) {
            nibbles[i] = (checksum >> (8 * (CHECKSUM_SIZE - 1 - i))) & 0xFF;
            data[size + i] = nibbles[i];
        }

        return size + CHECKSUM_SIZE;
    }

    /**
     * @brief Obfuscate or deobfuscate the data with the IV.
     * @param data Pointer to the array to be obfuscated/deobfuscated.
     * @param size Size of the array.
     * @return The size of data, zero if failed.
     *
     */
    static byte de_ob_fuscateWithIV(byte* data, byte size) {
        if (size <= IV_SIZE) return 0;

        for (byte i = IV_SIZE; i < size; i++) {
            data[i] ^= data[i % IV_SIZE];
        }
        return size;
    }

    /**
     * @brief Test and remove the checksum from the data.
     * @param data Pointer to the array to be tested.
     * @param size Size of the array.
     * @return The new size of the data excluding the checksum, Zero if failed.
     *
     */
    static byte testAndRemoveChecksum(byte* data, byte size) {
        b2byte constructedChecksum = 0;
        if (sizeof(constructedChecksum) / sizeof(byte) != CHECKSUM_SIZE) {
            return 0;
        }
        for (byte i = 0; i < CHECKSUM_SIZE; i++) {
            constructedChecksum |= (static_cast<b2byte>(data[size - CHECKSUM_SIZE + i]) << (8 * (CHECKSUM_SIZE - 1 - i)));
        }

        for (byte i = 0; i < size - CHECKSUM_SIZE; i++) {
            constructedChecksum -= data[i];
        }

        if (constructedChecksum != 0) {
            return 0;
        }

        return size - CHECKSUM_SIZE;
    }

    public:
    /**
     *  @brief Encrypt the given data.
     *  @param data Pointer to the array to be encrypted.
     *  @param size Size of the array.
     *  @return The size of the encrypted data. Zero if failed.
     *
     */
    static byte encrypt(byte* data, byte size) {
        if (
            !(size = genAndInsertIV(data, size)) ||
            !(size = genAndInsertChecksum(data, size)) ||
            !(size = de_ob_fuscateWithIV(data, size))
        ) {
            return 0;
        }

      for (byte round = 0; round < ROUNDS; round++) {
            enc_dec_byteArray(data, size, round, &Crypt::encodeByte);
      }
        return size;
    }

    /**
     *  @brief Decrypt the given data.
     *  @param data Pointer to the array to be decrypted.
     *  @param size Size of the array.
     *  @return The size of the decrypted data. Zero if failed.
     *
     */
    static byte decrypt(byte* data, byte size){
      for (byte round = ROUNDS; round-- > 0;) {
            enc_dec_byteArray(data, size, round, &Crypt::decodeByte);
      }
        if (
            !(size = de_ob_fuscateWithIV(data, size)) ||
            !(size = testAndRemoveChecksum(data, size)) ||
            IV_SIZE >= size
        ) {
            return 0;
        }
        memcpy(data, data + IV_SIZE, size);
        return size - IV_SIZE;
    }

    /**
     *  @brief Set the encryption keys.
     *  @param key Pointer to the key array.
     * 
     */
    static void setKeys(const byte* key) {
      memcpy(keys[0], key, KEY_SIZE);
      generateSubkeys();
    }
};

Crypt Crypt;

#endif