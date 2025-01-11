import sys
import serial
import time
import logging

# Configure logging
logging.basicConfig(
    filename='sms_notifications.log',
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s'
)

def wait_for_response(ser, timeout=5):
    """Wait for and collect complete response from modem"""
    start_time = time.time()
    response = ""
    
    while (time.time() - start_time) < timeout:
        if ser.in_waiting:
            chunk = ser.read(ser.in_waiting).decode(errors='ignore')
            response += chunk
            
            # If we see a complete response, return it
            if 'OK' in response or 'ERROR' in response or '+CMGS:' in response:
                time.sleep(0.5)  # Small delay to catch any trailing data
                final_chunk = ser.read(ser.in_waiting).decode(errors='ignore')
                return response + final_chunk
                
        time.sleep(0.1)
    
    return response

def send_command(ser, command, delay=1):
    """Send AT command and verify response"""
    ser.write(command.encode())
    time.sleep(delay)
    response = wait_for_response(ser)
    logging.info(f"Command: {command.strip()}, Response: {response.strip()}")
    return 'OK' in response

def check_modem_ready(ser):
    """Verify modem is responding correctly"""
    for _ in range(3):  # Try up to 3 times
        if send_command(ser, 'AT\r'):
            return True
        time.sleep(1)
    return False

def send_sms(phone_number, message_lines):
    ser = None
    try:
        # Open port with timeout
        for attempt in range(3):
            try:
                if ser and ser.is_open:
                    ser.close()
                    time.sleep(2)
                
                ser = serial.Serial('COM7', 115200, timeout=5)
                logging.info("Port opened successfully")
                
                if check_modem_ready(ser):
                    break
                    
            except serial.SerialException as e:
                logging.warning(f"Port open attempt {attempt + 1} failed: {str(e)}")
                if attempt == 2:
                    raise
                time.sleep(2)

        # Initialize modem
        if not all([
            send_command(ser, 'AT\r'),  # Basic check
            send_command(ser, 'ATE0\r'),  # Echo off
            send_command(ser, 'AT+CMGF=1\r')  # Text mode
        ]):
            raise Exception("Failed to initialize modem")

        # Send message command
        ser.write(f'AT+CMGS="{phone_number}"\r'.encode())
        time.sleep(1)
        
        # Wait for prompt
        prompt_response = wait_for_response(ser, 2)
        if '>' not in prompt_response:
            raise Exception("Did not receive message prompt")

        # Send message content
        logging.info(f"Sending message to {phone_number}")
        for i, line in enumerate(message_lines):
            if i > 0:
                ser.write(b'\r\n')
                time.sleep(0.2)
            ser.write(line.encode())
            time.sleep(0.2)

        # Send Ctrl+Z and wait for response
        time.sleep(0.5)
        ser.write(bytes([26]))
        
        # Wait for and verify final response
        final_response = wait_for_response(ser, 10)  # Longer timeout for message send
        logging.info(f"Final response: {final_response.strip()}")
        
        # Verify successful send
        if '+CMGS:' in final_response and 'OK' in final_response:
            logging.info(f"Message successfully sent to {phone_number}")
            return True
            
        logging.error(f"Message send failed. Response: {final_response.strip()}")
        return False

    except Exception as e:
        logging.error(f"Error sending SMS: {str(e)}")
        return False
        
    finally:
        if ser and ser.is_open:
            ser.close()
            time.sleep(1)
            logging.info("Port closed")

def main():
    if len(sys.argv) != 3:
        print("Usage: python smsnotif.py <phone_number> <message>")
        sys.exit(1)

    phone_number = sys.argv[1]
    message_lines = sys.argv[2].split('|')
    
    logging.info(f"Preparing to send message to {phone_number}")
    if send_sms(phone_number, message_lines):
        print("SMS sent successfully!")
        sys.exit(0)
    else:
        print("Failed to send SMS")
        sys.exit(1)

if __name__ == "__main__":
    main()