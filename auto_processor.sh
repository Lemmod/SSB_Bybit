#Update the address to the correct absolute directory where alert_processor.php is located

while true; 
    do /opt/plesk/php/7.4/bin/php /var/www/vhosts/yoursite/public/alert_processor.php; 
    sleep 2; 
done


