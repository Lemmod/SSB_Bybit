<?php
error_reporting(E_ERROR);
/*****

Remember , script is under construction and not documented but the basics.

Use this script at your own risk!

It won't contain all possibilitys from the 3c API , mainly used for updating multiple bots at once

(c) 2021 - MileCrypto (Lemmod)

*/

include ('app/Config.php');
include ('app/Core.php');
include ('app/DataMapper.php');
include ('app/functions.php');

if (empty(DB_DBNAME) OR DB_DBNAME == 'your_database_name') {
    echo '<h1> Database name not set , please change Config.php to correct settings.</h1>';
    die();
}

$dataMapper = new DataMapper();

// Steps
// 1 - Setup database
// 2 - Add user
// 3 - Forward user to front end

$step = $_REQUEST['step'];
$action = $_REQUEST['action'];

if(empty($step)) {
    $step = 1;
}

// Built-in checks

$accounts_exist = $dataMapper->dbh->prepare('SHOW tables like "accounts"');
$accounts_exist->execute();
$accounts_exist_res = $accounts_exist->fetch(PDO::FETCH_ASSOC);

$user_exists = $dataMapper->dbh->prepare('SELECT COUNT(*) AS total FROM users;');
$user_exists->execute();
$user_exists_res = $user_exists->fetch(PDO::FETCH_ASSOC);





// Step 1 - Setup the database. Check if the accounts table allready exist (probably allready installed)
if ($step == 1) {

    echo '<h1> Step 1/3 - Setup database</h1>';

    if (!$accounts_exist_res) {
        echo '<h2> <a href="install.php?action=create_tables">Setup the database.</a></h2>';
    } else {
        echo '<h2> Seems the database is allready set. Click <a href="install.php?step=2">here</a> if you want to add an user</h2>';
    }
}


if ($step == 2) {

    echo '<h1> Step 2/3 - Add user</h1>';

    // If user attempts to go step 2 but the database isn't installed yet return to step 1
    if (!$accounts_exist_res) {
        header('Location: install.php?step=1');
    }

    echo 'Set up a username and password :
    <form action="install.php" method="post">
        <input type="hidden" name="action" value="insert_user">

        
        <label for="user_name">
            Username :
        </label>
        <input type="text" name="user_name" placeholder="Username" id="user_name" required>
        <label for="password">
            Password :
        </label>
        <input type="password" name="password" placeholder="Password" id="password" required>
        <input type="submit" value="Add user">
    </form>';
}


if ($step == 3) {

    echo '<h1> Step 3/3 - Setup complete</h1>';

    // If user attempts to go step 3 but there are no users return to step 2. Maybe even go to step 1 if the database isn't set.
    if ($user_exists_res['total'] == 0) {
        header('Location: install.php?step=2');
    }

    echo '<h2> Setup completed. Login <a href="admin/index.php">on the admin homepage.</a> </h2>';

}


if($action == "create_tables") {

    $create_tables = '
    
    SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
    SET AUTOCOMMIT = 0;
    START TRANSACTION;

    DROP TABLE IF EXISTS `accounts`;
    CREATE TABLE `accounts` (
      `internal_account_id` int(12) NOT NULL,
      `user_id` int(12) NOT NULL,
      `bot_account_id` text NOT NULL,
      `api_key` text NOT NULL,
      `api_secret` text NOT NULL,
      `account_name` text NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1;
    
    DROP TABLE IF EXISTS `account_settings`;
    CREATE TABLE `account_settings` (
      `account_setting_id` int(12) NOT NULL,
      `internal_account_id` int(8) NOT NULL,
      `max_active_deals` int(8) NOT NULL,
      `bo_size` decimal(10,2) NOT NULL DEFAULT 5.00,
      `active` tinyint(1) NOT NULL DEFAULT 0,
      `leverage` text NOT NULL,
      `leverage_mode` enum(\'cross\',\'isolated\') NOT NULL DEFAULT \'cross\',
      `hedge_mode` tinyint(1) NOT NULL DEFAULT 0,
      `use_stoploss` tinyint(1) NOT NULL DEFAULT 0,
      `stoploss_percentage` decimal(10,2) NOT NULL,
      `away_mode` tinyint(1) NOT NULL DEFAULT 0,
      `away_closure` decimal(10,2) NOT NULL,
      `away_stoploss` decimal(10,2) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1;
    DROP VIEW IF EXISTS `debug_calls`;
    CREATE TABLE `debug_calls` (
    `time` varchar(24)
    ,`jobs` bigint(21)
    ,`alerts` decimal(32,0)
    ,`calls` decimal(32,0)
    ,`average_calls` decimal(36,4)
    ,`errors` decimal(32,0)
    ,`avg_job_time` decimal(38,8)
    ,`max_job_time` decimal(12,4)
    ,`exceed_15s` decimal(22,0)
    ,`exceed_20s` decimal(22,0)
    ,`exceed_30s` decimal(22,0)
    );
    
    DROP TABLE IF EXISTS `debug_log`;
    CREATE TABLE `debug_log` (
      `debug_id` int(11) NOT NULL,
      `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
      `file_name` text NOT NULL,
      `alerts_processed` int(11) NOT NULL,
      `errors_3c` int(11) NOT NULL,
      `calls_3c` int(11) NOT NULL,
      `time_passed` decimal(12,4) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1;
    
    DROP TABLE IF EXISTS `info`;
    CREATE TABLE `info` (
      `info_id` int(30) NOT NULL,
      `timestamp` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
      `account_id` varchar(255) NOT NULL,
      `pair` varchar(255) NOT NULL,
      `params` text NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
    
    DROP TABLE IF EXISTS `log`;
    CREATE TABLE `log` (
      `log_id` int(12) NOT NULL,
      `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
      `account_id` text NOT NULL,
      `bot_id` int(12) NOT NULL,
      `pair` text NOT NULL,
      `message` text NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1;
    DROP VIEW IF EXISTS `logbook`;
    CREATE TABLE `logbook` (
    `log_id` int(12)
    ,`timestamp` timestamp
    ,`account_id` text
    ,`account_name` text
    ,`pair` text
    ,`message` text
    );
    
    DROP TABLE IF EXISTS `order_log`;
    CREATE TABLE `order_log` (
      `order_id` int(11) NOT NULL,
      `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
      `account_id` varchar(255) NOT NULL,
      `pair` varchar(255) NOT NULL,
      `message` text NOT NULL,
      `json_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
    
    DROP TABLE IF EXISTS `raw_tv_input`;
    CREATE TABLE `raw_tv_input` (
      `input_id` int(20) NOT NULL,
      `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
      `input` text NOT NULL,
      `file_name` text NOT NULL,
      `processed` tinyint(1) NOT NULL DEFAULT 0,
      `processed_time` timestamp NOT NULL DEFAULT \'0000-00-00 00:00:00\'
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1;
    
    DROP TABLE IF EXISTS `users`;
    CREATE TABLE `users` (
      `user_id` int(11) NOT NULL,
      `user_name` varchar(50) NOT NULL,
      `password` varchar(255) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
    DROP TABLE IF EXISTS `debug_calls`;
    
    DROP VIEW IF EXISTS `debug_calls`;
    CREATE OR REPLACE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `debug_calls`  AS SELECT date_format(`debug_log`.`timestamp`,\'%Y-%m-%d %H:00:00\') AS `time`, count(0) AS `jobs`, sum(`debug_log`.`alerts_processed`) AS `alerts`, sum(`debug_log`.`calls_3c`) AS `calls`, sum(`debug_log`.`calls_3c`) / sum(`debug_log`.`alerts_processed`) AS `average_calls`, sum(`debug_log`.`errors_3c`) AS `errors`, sum(`debug_log`.`time_passed`) / count(0) AS `avg_job_time`, max(`debug_log`.`time_passed`) AS `max_job_time`, sum(case when `debug_log`.`time_passed` > 15 then 1 else 0 end) AS `exceed_15s`, sum(case when `debug_log`.`time_passed` > 20 then 1 else 0 end) AS `exceed_20s`, sum(case when `debug_log`.`time_passed` > 30 then 1 else 0 end) AS `exceed_30s` FROM `debug_log` GROUP BY date_format(`debug_log`.`timestamp`,\'%Y-%m-%d %H:00:00\') ORDER BY date_format(`debug_log`.`timestamp`,\'%Y-%m-%d %H:00:00\') DESC ;
    DROP TABLE IF EXISTS `logbook`;
    
    DROP VIEW IF EXISTS `logbook`;
    CREATE OR REPLACE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `logbook`  AS SELECT `log`.`log_id` AS `log_id`, `log`.`timestamp` AS `timestamp`, `log`.`account_id` AS `account_id`, `accounts`.`account_name` AS `account_name`, `log`.`pair` AS `pair`, `log`.`message` AS `message` FROM (`log` left join `accounts` on(`log`.`account_id` = `accounts`.`bot_account_id`)) ORDER BY `log`.`log_id` DESC ;
    
    
    ALTER TABLE `accounts`
      ADD PRIMARY KEY (`internal_account_id`);
    
    ALTER TABLE `account_settings`
      ADD PRIMARY KEY (`account_setting_id`);
    
    ALTER TABLE `debug_log`
      ADD PRIMARY KEY (`debug_id`);
    
    ALTER TABLE `info`
      ADD PRIMARY KEY (`info_id`);
    
    ALTER TABLE `log`
      ADD PRIMARY KEY (`log_id`);
    
    ALTER TABLE `order_log`
      ADD PRIMARY KEY (`order_id`);
    
    ALTER TABLE `raw_tv_input`
      ADD PRIMARY KEY (`input_id`);
    
    ALTER TABLE `users`
      ADD PRIMARY KEY (`user_id`);
    
    
    ALTER TABLE `accounts`
      MODIFY `internal_account_id` int(12) NOT NULL AUTO_INCREMENT;
    
    ALTER TABLE `account_settings`
      MODIFY `account_setting_id` int(12) NOT NULL AUTO_INCREMENT;
    
    ALTER TABLE `debug_log`
      MODIFY `debug_id` int(11) NOT NULL AUTO_INCREMENT;
    
    ALTER TABLE `info`
      MODIFY `info_id` int(30) NOT NULL AUTO_INCREMENT;
    
    ALTER TABLE `log`
      MODIFY `log_id` int(12) NOT NULL AUTO_INCREMENT;
    
    ALTER TABLE `order_log`
      MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT;
    
    ALTER TABLE `raw_tv_input`
      MODIFY `input_id` int(20) NOT NULL AUTO_INCREMENT;
    
    ALTER TABLE `users`
      MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT;
    COMMIT;
    ';

    // Create the tables
    $stmt = $dataMapper->dbh->prepare($create_tables);
    $stmt->execute();
    $stmt = null;

    header('Location: install.php?step=2');
    // Set the username and password
}

if($action == "insert_user") {
    $insert_user = $dataMapper->dbh->prepare("INSERT INTO users (user_name , password) VALUES (:user_name , :password)");
    $insert_user->bindParam(':user_name', $_POST['user_name']);
    $insert_user->bindParam(':password', password_hash($_POST['password'] , PASSWORD_DEFAULT));
    $insert_user->execute();


    $insert_user = null;

    header('Location: install.php?step=3');

}

if ($step == "sh_creator") {

  echo '<h2> Text for .sh file </h2>';

  echo '<textarea cols="150" rows="4">'.$_SERVER['DOCUMENT_ROOT'].'/alert_processor.php</textarea>';

  echo '<h2> Command to run screen </h2>';

  echo '<textarea cols="150" rows="4">screen -d -m sh '.$_SERVER['DOCUMENT_ROOT'].'/auto_processor.sh</textarea>';

}

