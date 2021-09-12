<?php
error_reporting(E_ALL);
/*****

Remember , script is under construction and not documented but the basics.

Use this script at your own risk!

It won't contain all possibilitys from the 3c API , mainly used for updating multiple bots at once

(c) 2021 - MileCrypto (Lemmod)

*/

$time = microtime();
$time = explode(' ', $time);
$time = $time[1] + $time[0];
$start = $time;

include (__DIR__.'/app/Config.php');
include (__DIR__.'/app/Core.php');
include (__DIR__.'/app/BybitConnector.php');
include (__DIR__.'/app/DataMapper.php');
include (__DIR__.'/app/DataReader.php');
include (__DIR__.'/app/functions.php');

$dataMapper = new DataMapper();
$dataReader = new DataReader();

$all_accounts = $dataReader->get_all_accounts();
$unprocessed_alerts = $dataReader->get_unprocessed_alerts(MAX_TIME_TO_CHECK_ALERT);

$total_alerts = count($unprocessed_alerts);
$errors_3c = 0;
$calls_bybit = 0;

/** Set the alerts in process. This to prevent lagging API on 3C side to sent the alert multiple times */
foreach ($unprocessed_alerts as $alert) {
    $dataMapper->update_alert_in_process($alert['input_id']); // NIET VERGETEN AAN TE ZETTEN
}

//pr($all_accounts);

foreach($all_accounts as $account_wrapper) {


    $account_info = $dataReader->get_account_info($account_wrapper['bot_account_id']);
    $account_settings = $dataReader->get_account_settings($account_info['internal_account_id']);


    //pr($unprocessed_alerts);

 
    /**
     * 
     * Check if account exist , if not we can skip this iteration
     * 
     */
    if(!$account_info) {
        echo 'Account not found...';
        continue; 
    } 

    $processed_alerts = 1;

    foreach ($unprocessed_alerts as $alert) {

        /**
         * Get the data , decode the JSON
         */
        $data = json_clean_decode($alert['input'] , TRUE);

        // Check if the current data belongs to the current account
        if($data['account_id'] == $account_wrapper['bot_account_id']) {

      

            // Only on first alert we need to setup Bybit connection
            if ($processed_alerts == 1) {
                $bybit = new BybitConnector($account_info['api_key'] , $account_info['api_secret']);
                $symbols = $bybit->get_symbols();
                $calls_bybit++;
            
                try {
                    $open_positions = $bybit->get_positions();
                    $deals = $bybit->result_open_positions($open_positions);
                    
                    $calls_bybit++;
                    $count_active_deals = count($deals);

                    $active_deal_bots = array();
                    foreach($deals as $deal) {
                        $active_deal_bots[] = $deal['symbol'];
                    }

                    
                } catch (Exception $e) {

                    continue;
                } 
            }

            /**
             * Set the alert as processed
             */
            $dataMapper->update_alert($alert['input_id'] , date('Y-m-d H:i:s',time()));
            $processed_alerts++;

            /**
             * Get source information from JSON and check account info
             */
            $bot_account_id = $data['account_id'];
            $message = $data['message'];

            /**
             * 
             * Enable bots / account on Smart Simple Bot (doesn't affect 3C bots / account)
             * 
             */
            if($message == 'enable_bots') {
                $dataMapper->enable_disable_account($account_info['internal_account_id'] , 1);
                $dataMapper->insert_log($data['account_id'] , 0 , '' , 'Bot enabled by TV message');
                continue; 
            }

            /**
             * 
             * Disable bots / account on Smart Simple Bot (doesn't affect 3C bots / account)
             * 
             */
            if($message == 'disable_bots') {
                $dataMapper->enable_disable_account($account_info['internal_account_id'] , 0);
                $dataMapper->insert_log($data['account_id'] , 0 , '' , 'Bot disabled by TV message');
                continue; 
            }

            /**
             * 
             * Check if account is active , if not we can skip this record
             * 
             */
            if($account_settings['active'] == 0) {
                echo 'Account disabled...';
                continue;
            }
        
            /**
             * 
             * 
             * Get deals on the current bot , first check if there isn't allready running an order , in that case we can skipe the rest
             * 
             */

            if(in_array($data['pair'] , $active_deal_bots )) {

                $dataMapper->insert_log($data['account_id'] , 0 , $data['pair'] , 'Deal allready running');

            } else {

                if ( ($count_active_deals < $account_settings['max_active_deals'])  && !is_null($deals)) {

                    /**
                     * Create the deal on Bybit directly
                    */ 
                    try {

                        // First set the desired leverage
                        $set_leverage = $bybit->set_leverage(['symbol' => $data['pair'] , 'buy_leverage' => $account_settings['leverage'], 'sell_leverage' => $account_settings['leverage']] );
                        $wallet_balance = $bybit->wallet_info()['result']['USDT']['equity'];
                        $get_price = $bybit->get_symbol_value($data['pair']);

                        $current_price = $get_price['result'][0]['price'];

                        // Calculate rough order_size based on wallet balance and percentage
                        $order_size = ($wallet_balance * ( $account_settings['bo_size'] / 100)) / $current_price;

                        $clean_order_size = $bybit->calculate_clean_order_size($symbols , $data['pair'] , $order_size);

                        $order_params = [
                            'side' => 'Buy',
                            'symbol' => $data['pair'],
                            'order_type' => 'Market',
                            'qty' => $clean_order_size ,
                            'time_in_force' => 'GoodTillCancel',
                            'close_on_trigger' => false ,
                            'reduce_only' => false
                        ];

                        $order = $bybit->create_order($order_params);

                        $dataMapper->insert_log($data['account_id'] , 0 , $data['pair'] , 'Deal added ( Active : '.($count_active_deals + 1).' , Max : '.$account_settings['max_active_deals'].' )');
                        $calls_bybit++;

                        $count_active_deals++;

                    } catch (Exception $e) {
                        echo ' > Caught exception: '.$e->getMessage().'.'.PHP_EOL;
                    }
                
                } else {
                    /**
                     * Log reason for not able to add deal
                     */
                    if (is_null($deals)) {
                        $errors_3c++;
                        $dataMapper->insert_log($data['account_id'] , 0, $data['pair'] , 'Deal not added , ERROR - 3Commas deal count is null');
                    }  else {
                        $dataMapper->insert_log($data['account_id'] , 0 , $data['pair'] , 'Deal not added , max active deals hit ( Active : '.$count_active_deals.' , Max : '.$account_settings['max_active_deals'].' )');
                    }
                }
            }
        }
    }

    $xcommas_main = null;
}


/**
 * 
 * Used for debug
 * 
 */
$time = microtime();
$time = explode(' ', $time);
$time = $time[1] + $time[0];
$finishtime = $time;
$total_time = round(($finishtime - $start), 4);

echo date('Y-m-d H:i:s').' script ran for '.$total_time.PHP_EOL;

$dataMapper->insert_debug_log(basename(__FILE__) , $total_alerts , $errors_3c , $calls_bybit , $total_time) ;

/**
 * Close connection
 */
$dataMapper->close_connection(); 
