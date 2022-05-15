<?php
/*****

(c) 2021 - Lemmod

Needed for new database settings when upgrading script , will notice when this need to be run

*/
error_reporting(E_ALL);
ini_set('display_errors', 1);


$time = microtime();
$time = explode(' ', $time);
$time = $time[1] + $time[0];
$start = $time;

include ('app/Config.php');
include ('app/Core.php');
include ('app/DataMapper.php');
include ('app/functions.php');


$dataMapper = new DataMapper();
$dataReader = new DataReader();

$upgrade_sql = "
ALTER TABLE `account_settings` ADD COLUMN IF NOT EXISTS `mad_direction` enum('both','short_only','long_only') NOT NULL DEFAULT 'both';

ALTER TABLE `order_log` ADD COLUMN IF NOT EXISTS `direction` varchar(50) NOT NULL;
ALTER TABLE `order_log` ADD COLUMN IF NOT EXISTS `trigger_condition` varchar(50) NOT NULL;
ALTER TABLE `order_log` ADD COLUMN IF NOT EXISTS `away_mode_triggered` tinyint(1) NOT NULL DEFAULT 0;
";

$stmt = $dataMapper->dbh->prepare($upgrade_sql);
$stmt->execute();
$stmt = null;

echo '<span style="color : green">Upgrade complete.</span>';

?>