@echo off
echo Starting SMS Queue Processor...
:loop
"C:\xampp\php\php.exe" -f "C:\xampp\htdocs\CFC\process_notification_queue.php"
timeout /t 120 /nobreak
goto loop