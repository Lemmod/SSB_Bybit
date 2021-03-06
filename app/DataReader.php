<?php

class DataReader extends Core
{

    public function get_account_info($bot_account_id) {

        try{
               
            $stmt = $this->dbh->prepare('SELECT * FROM accounts WHERE bot_account_id = :bot_account_id');
            $stmt->bindParam(':bot_account_id', $bot_account_id);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = null;

            return $result;
        }
        catch (PDOExecption $e){
            echo $e->getMessage();
        }    
    }

    public function get_account_info_internal($internal_account_id) {

        try{
               
            $stmt = $this->dbh->prepare('SELECT * FROM accounts WHERE internal_account_id = :internal_account_id');
            $stmt->bindParam(':internal_account_id', $internal_account_id);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = null;

            return $result;
        }
        catch (PDOExecption $e){
            echo $e->getMessage();
        }    
    }

    public function get_account_settings($internal_account_id) {

        try{

            $stmt = $this->dbh->prepare('SELECT * FROM account_settings WHERE internal_account_id = :internal_account_id');
            $stmt->bindParam(':internal_account_id', $internal_account_id);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = null;

            return $result;
        }
        catch (PDOExecption $e){
            echo $e->getMessage();
        }    
    }

    public function get_user_credentials($user_name) {

        try{

            $stmt = $this->dbh->prepare('SELECT * FROM users WHERE user_name = :user_name');
            $stmt->bindParam(':user_name', $user_name);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = null;

            return $result;
        }
        catch (PDOExecption $e){
            echo $e->getMessage();
        }    

    }

    public function get_user_accounts($user_id) {

        try{

            $stmt = $this->dbh->prepare('SELECT * FROM accounts WHERE user_id = :user_id ORDER BY bot_account_id ASC');
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = null;

            return $result;
        }
        catch (PDOExecption $e){
            echo $e->getMessage();
        }    

    }

    public function get_all_accounts() {

        try{

            $stmt = $this->dbh->prepare('SELECT internal_account_id , bot_account_id FROM accounts');
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = null;

            return $result;
        }
        catch (PDOExecption $e){
            echo $e->getMessage();
        }    

    }

    public function get_passed_time_debug() {

        

        try{

            $stmt = $this->dbh->prepare('SELECT TIMESTAMPDIFF(SECOND,timestamp,NOW()) as diff FROM debug_log ORDER BY timestamp DESC LIMIT 1');
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = null;

            return $result['diff'];
        }
        catch (PDOExecption $e){
            echo $e->getMessage();
        }    
    }

    public function get_unprocessed_alerts($seconds_passed) {

        // Only fetch result not older then current_time - $seconds_passed seconds
        $start_time = date('Y-m-d H:i:s',time() - $seconds_passed);

        try{

            $stmt = $this->dbh->prepare('SELECT * , CASE WHEN input like \'%message%\' THEN 1 ELSE 2 END prio FROM raw_tv_input WHERE processed = :processed AND file_name = :file_name AND timestamp >= :start_time ORDER BY prio ASC');
            $stmt->bindValue(':processed', 0);
            $stmt->bindValue(':file_name', 'alert_handler.php');
            $stmt->bindParam(':start_time', $start_time);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = null;

            return $result;
        }
        catch (PDOExecption $e){
            echo $e->getMessage();
        }    
    }

    public function get_unprocessed_alerts_test($seconds_passed) {

        // Only fetch result not older then current_time - $seconds_passed seconds
        $start_time = date('Y-m-d H:i:s',time() - $seconds_passed);

        try{

            $stmt = $this->dbh->prepare('SELECT * , CASE WHEN input like \'%message%\' THEN 1 ELSE 2 END prio FROM raw_tv_input WHERE processed = :processed AND input like \'%params%\' AND timestamp >= :start_time ORDER BY prio ASC');
            $stmt->bindValue(':processed', 1);
            $stmt->bindParam(':start_time', $start_time);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = null;

            return $result;
        }
        catch (PDOExecption $e){
            echo $e->getMessage();
        }    
    }

    public function get_logbook($bot_account_id , $lookback = 1) {

        $start_time = date('Y-m-d H:i:s',time() - (60 * 60 * 24 * $lookback));

        try{

            $stmt = $this->dbh->prepare('SELECT * FROM logbook WHERE account_id = :bot_account_id AND timestamp >= :start_time ORDER BY log_id');
            $stmt->bindParam(':bot_account_id', $bot_account_id);
            $stmt->bindParam(':start_time', $start_time);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = null;

            return $result;
        }
        catch (PDOExecption $e){
            echo $e->getMessage();
        }    
    }

    public function get_info($bot_account_id , $lookback = 1) {

        $start_time = date('Y-m-d H:i:s',time() - (60 * 60 * 24 * $lookback));

        try{

            $stmt = $this->dbh->prepare('SELECT * FROM info WHERE account_id = :bot_account_id AND timestamp >= :start_time ORDER BY info_id DESC');
            $stmt->bindParam(':bot_account_id', $bot_account_id);
            $stmt->bindParam(':start_time', $start_time);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = null;

            return $result;
        }
        catch (PDOExecption $e){
            echo $e->getMessage();
        }    
    }

    public function get_debuglog() {

        $start_time = date('Y-m-d H:i:s',time() - (60 * 60 * 24 * 7));

        try{

            $stmt = $this->dbh->prepare('SELECT * FROM debug_calls WHERE time >= :start_time ORDER BY time DESC');
            $stmt->bindParam(':start_time', $start_time);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = null;

            return $result;
        }
        catch (PDOExecption $e){
            echo $e->getMessage();
        }    
    }

    public function get_latest_order_trigger($bot_account_id , $pair , $direction) {

        try{

            $stmt = $this->dbh->prepare('SELECT * FROM order_log WHERE account_id = :bot_account_id AND pair = :pair AND direction = :direction ORDER BY timestamp DESC LIMIT 1 ');
            $stmt->bindParam(':bot_account_id', $bot_account_id);
            $stmt->bindParam(':pair', $pair);
            $stmt->bindParam(':direction', $direction);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = null;

            return $result;
        }
        catch (PDOExecption $e){
            echo $e->getMessage();
        }    

    }

    

}