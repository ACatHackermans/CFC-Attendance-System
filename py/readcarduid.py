import json
from smartcard.System import readers
from smartcard.scard import *

def read_mifare_uid():
    try:
        r = readers()
        if len(r) < 1:
            return json.dumps({"error": "No NFC reader found."})

        reader = r[0]
        connection = reader.createConnection()
        connection.connect()

        # Mifare Classic Get UID command
        GET_UID = [0xFF, 0xCA, 0x00, 0x00, 0x04]
        data, sw1, sw2 = connection.transmit(GET_UID)

        if sw1 == 0x90 and sw2 == 0x00:
            uid = "".join(["{:02X}".format(b) for b in data])
            return json.dumps({"uid": uid})
        elif sw1 == 0x6A and sw2 == 0x82: #File not found
            return json.dumps({"error": "No card detected or card removed."})
        else:
            return json.dumps({"error": f"Error reading UID: SW1: {sw1:02X}, SW2: {sw2:02X}"})

    except Exception as e:
        return json.dumps({"error": str(e)})

if __name__ == "__main__":
    nfc_data = read_mifare_uid()
    print(nfc_data)