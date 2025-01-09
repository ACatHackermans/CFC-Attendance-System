import sys
import serial
import time
import logging
from queue import Queue
from threading import Lock

# Configure logging
logging.basicConfig(
    filename='sms_notifications.log',
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s'
)

class SMSModem:
    def __init__(self, port='COM7', baudrate=115200, timeout=5):
        self.port = port
        self.baudrate = baudrate
        self.timeout = timeout
        self.ser = None
        self.lock = Lock()
        self.message_queue = Queue()

    def connect(self):
        try:
            self.ser = serial.Serial(
                port=self.port,
                baudrate=self.baudrate,
                timeout=self.timeout
            )
            return True
        except Exception as e:
            logging.error(f"Failed to connect to modem: {str(e)}")
            return False

    def disconnect(self):
        if self.ser and self.ser.is_open:
            self.ser.close()

    def send_command(self, command, max_wait=5, wait_for_response=True):
        try:
            with self.lock:
                self.ser.write(command.encode() + b'\r\n')
                time.sleep(0.5)

                if wait_for_response:
                    start_time = time.time()
                    response = []
                    while time.time() - start_time < max_wait:
                        if self.ser.in_waiting > 0:
                            line = self.ser.readline().decode('utf-8').strip()
                            response.append(line)
                            if "OK" in line or "ERROR" in line:
                                break
                    return response
            return []
        except Exception as e:
            logging.error(f"Error sending command: {str(e)}")
            return []

    def initialize_modem(self):
        try:
            # Reset modem
            self.send_command('AT+CFUN=1')
            time.sleep(1)

            # Basic initialization commands
            commands = [
                'AT',           # Test connection
                'ATE0',         # Turn off echo
                'AT+CMGF=1',    # Set SMS text mode
                'AT+CSCS="GSM"' # Set GSM character set
            ]

            for cmd in commands:
                response = self.send_command(cmd)
                if not any("OK" in r for r in response):
                    logging.error(f"Failed to initialize modem with command: {cmd}")
                    return False
                time.sleep(0.5)

            return True
        except Exception as e:
            logging.error(f"Error initializing modem: {str(e)}")
            return False

    def send_sms(self, phone_number, message):
        try:
            # Send message command
            self.ser.write(f'AT+CMGS="{phone_number}"\r'.encode())
            time.sleep(1)

            # Send message content in chunks
            chunk_size = 100
            message_chunks = [message[i:i+chunk_size] for i in range(0, len(message), chunk_size)]

            for chunk in message_chunks:
                self.ser.write(chunk.encode())
                time.sleep(0.1)

            # Send PDU terminator
            self.ser.write(b'\x1A')
            time.sleep(3)  # Wait for message to be sent

            # Check response
            response = []
            while self.ser.in_waiting:
                line = self.ser.readline().decode('utf-8').strip()
                response.append(line)

            if any("OK" in r for r in response):
                logging.info(f"Successfully sent SMS to {phone_number}")
                return True
            else:
                logging.error(f"Failed to send SMS to {phone_number}: {response}")
                return False

        except Exception as e:
            logging.error(f"Error sending SMS to {phone_number}: {str(e)}")
            return False

def main():
    if len(sys.argv) < 3:
        print("Usage: python smsnotif.py <phone_number> <message>")
        sys.exit(1)

    phone_number = sys.argv[1]
    message = sys.argv[2]

    modem = SMSModem()
    
    try:
        if not modem.connect():
            raise Exception("Failed to connect to modem")

        if not modem.initialize_modem():
            raise Exception("Failed to initialize modem")

        if modem.send_sms(phone_number, message):
            print("SMS Sent Successfully!")
        else:
            print("Failed to send SMS")

    except Exception as e:
        print(f"Error: {str(e)}")
        logging.error(str(e))

    finally:
        modem.disconnect()

if __name__ == "__main__":
    main()