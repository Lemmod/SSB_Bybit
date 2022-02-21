#Update the address to the correct absolute directory where alert_processor.php is located

while true; 
    do /opt/plesk/php/7.4/bin/php /var/www/vhosts/yoursite/public/alert_processor.php; 
    sleep 5; 
done


#DELETE FROM debug_log WHERE timestamp < DATE_SUB(NOW(), INTERVAL 3 DAY);
#DELETE FROM raw_tv_input WHERE timestamp < DATE_SUB(NOW(), INTERVAL 3 DAY);
#DELETE FROM log WHERE timestamp < DATE_SUB(NOW(), INTERVAL 3 DAY);