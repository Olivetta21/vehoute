from Crypt import Crypt

arr = bytearray([1, 2, 3, 4, 10, 5, 6, 7, 8, 9, ])
cr = Crypt()

key = bytearray([0x23, 0x45, 0x67, 0x89, 0xAB, 0xCD, 0xEF, 0x01, 0x23, 0x45, 0x67, 0x89, 0xAB, 0xCD, 0xEF, 0x01])
cr.setKeys(key)

print("Before: ",arr)
cr.encrypt(arr)
print("Encrypted: ",arr)
cr.decrypt(arr)
print("Decrypted: ",arr)

encr = bytearray([104, 141, 136, 254, 253, 128, 196, 9, 14, 27, 1, 153, 92, 230, 172, 178, 166, 28, 235, 173, 93, 155, 232, 121, 175, 5, 188])

res = cr.decrypt(encr)
print("Other Decrypted: ", encr if res else "Failed")