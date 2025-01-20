import serial
import time
import sys
import json
import logging
from datetime import datetime

# Configure logging
logging.basicConfig(
    filename='sms_log.txt',
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s'
)

def initialize_modem(port='COM7', baudrate=9600, timeout=1):
    try:
        modem = serial.Serial(port, baudrate, timeout=timeout)
        time.sleep(2)  # Give modem time to stabilize
        logging.info("Modem initialized successfully")
        return modem
    except Exception as e:
        logging.error(f"Failed to initialize modem: {str(e)}")
        return None

def send_command(modem, command, expected_response='OK', timeout=3):
    try:
        # Clear any pending data
        modem.reset_input_buffer()
        modem.reset_output_buffer()
        
        # Send command
        modem.write(f"{command}\r".encode())
        time.sleep(timeout)
        
        # Read response
        response = ''
        while modem.in_waiting:
            response += modem.read_all().decode('utf-8', errors='ignore')
            time.sleep(0.1)
        
        logging.info(f"Command: {command}, Response: {response}")
        return expected_response in response
    except Exception as e:
        logging.error(f"Error sending command '{command}': {str(e)}")
        return False

def check_modem_ready(modem):
    """Check if modem is responding correctly"""
    if not send_command(modem, 'AT'):
        return False
    if not send_command(modem, 'AT+CMGF=1'):  # Set SMS text mode
        return False
    return True

def send_sms(phone_number, message, max_attempts=3):
    if not phone_number.startswith('+63'):
        phone_number = '+63' + phone_number

    # Split message by pipe symbol and rejoin with proper line endings
    message_parts = message.split('|')
    formatted_message = '\r'.join(part.strip() for part in message_parts)

    modem = None
    success = False
    attempts = 0

    while attempts < max_attempts and not success:
        try:
            if modem is None:
                modem = initialize_modem()
                if not modem:
                    raise Exception("Failed to initialize modem")

            # Check modem readiness
            if not check_modem_ready(modem):
                raise Exception("Modem not ready")

            # Clear any old messages
            send_command(modem, 'AT+CMGD=1,4')
            time.sleep(1)

            # Send message
            if not send_command(modem, f'AT+CMGS="{phone_number}"', '>'):
                raise Exception("Failed to start message sending")

            # Send message content
            modem.write(formatted_message.encode())
            modem.write(bytes([26]))  # CTRL+Z
            time.sleep(3)

            # Read response
            response = ''
            timeout = time.time() + 10  # 10 second timeout
            while time.time() < timeout:
                if modem.in_waiting:
                    response += modem.read_all().decode('utf-8', errors='ignore')
                    if '+CMGS:' in response:
                        success = True
                        logging.info(f"SMS sent successfully to {phone_number}")
                        break
                time.sleep(0.5)

            if not success:
                attempts += 1
                logging.warning(f"Attempt {attempts} failed. Response: {response}")
                # Close and reopen modem connection
                if modem:
                    modem.close()
                    modem = None
                time.sleep(2)

        except Exception as e:
            attempts += 1
            logging.error(f"Attempt {attempts} failed with error: {str(e)}")
            if modem:
                modem.close()
                modem = None
            time.sleep(2)

    if modem:
        modem.close()

    if not success:
        logging.error(f"Failed to send SMS after {max_attempts} attempts")
    
    return success

if __name__ == "__main__":
    if len(sys.argv) != 3:
        print("Usage: python smsnotif.py <phone_number> <message>")
        sys.exit(1)

    phone_number = sys.argv[1]
    message = sys.argv[2]
    success = send_sms(phone_number, message)
    
    # Print result for PHP to capture
    print(json.dumps({
        "success": success,
        "phone": phone_number
    }))