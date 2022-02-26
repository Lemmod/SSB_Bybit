<?php
error_reporting(E_ERROR);
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

    
    /**
     * 
     * Check if account exist , if not we can skip this iteration
     * 
     */
    if(!$account_info) {
        //echo 'Account not found...';
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
            
                try {
                    $bybit = new BybitConnector($account_info['api_key'] , $account_info['api_secret']);
                    $symbols = $bybit->get_symbols();
                    $calls_bybit++;

                    $open_positions = $bybit->get_positions();
                    $deals = $bybit->result_open_positions($open_positions);

                    // Fail safe if deals aren't processed
                    if($open_positions['ret_msg'] != "OK") {
                        echo 'No connection to Bybit API , skip';
                        continue;
                    }
                   
                    $calls_bybit++;
                    $count_active_deals = count($deals);

                    $active_deal_bots = array();
                    $active_deal_bots_long = array();
                    $active_deal_bots_short = array();

                    foreach($deals as $deal) {
                        $active_deal_bots[] = $deal['symbol'];

                        // When using hedge we want to check seperate for long and short
                        if ($deal['side'] == 'Buy') {
                            $active_deal_bots_long[] = $deal['symbol'];
                        }
                        if ($deal['side'] == 'Sell') {
                            $active_deal_bots_short[] = $deal['symbol'];
                        }
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
             * Close (partial) position based on TV Alert
             * 
             */
            if($message == 'close_position') {

                // If away mode is disabled for current account we can skip these alerts
                if($account_settings['away_mode'] == 0) {

                    $dataMapper->insert_log($data['account_id'] , 0 , $data['pair'] , 'Away mode not enabled, skip alert');
                    continue;
                }

                if( empty($data['direction']) || $data['direction'] == 'long') {

                    $pos_info = $bybit->get_open_position($open_positions , $data['pair'] , 'Buy');

                    // Check if deal is open
                    if(empty($pos_info)) {
                        $dataMapper->insert_log($data['account_id'] , 0 , $data['pair'] , 'Deal not open , skip close alert [Long]');
                        continue;
                    } else {

                        $dataMapper->insert_log($data['account_id'] , 0 , $data['pair'] , 'Close position alert received [Long]');

                        // Change S/L when deal is confirmed
                        $stop_loss_price = false;
                        if($account_settings['use_stoploss']) {
                            $stop_loss_price = number_format( $pos_info['entry_price'] - ($pos_info['entry_price']  * ($account_settings['away_stoploss'] / 100)) , 4);
                        }
        
                        // Update Stop Loss
                        $update_sl = $bybit->update_position(['symbol' => $data['pair'] , 'side' => 'Buy' , 'stop_loss' => $stop_loss_price]);
                        $dataMapper->insert_log($data['account_id'] , 0 , $data['pair'] , 'Updated S/L to '.$stop_loss_price);

                        // Add closure order
                        $close_amount = $bybit->calculate_clean_close_amount($symbols , $data['pair'] , ($pos_info['size'] * ($account_settings['away_closure'] / 100)));

                        $order_params = [
                            'side' => 'Sell',
                            'symbol' => $data['pair'],
                            'order_type' => 'Market',
                            'qty' => $close_amount ,
                            'time_in_force' => 'GoodTillCancel',
                            'close_on_trigger' => false ,
                            'reduce_only' => true 
                        ];
        
                        $order = $bybit->create_order($order_params);
        
                        $dataMapper->insert_order_log($data['account_id'] , $data['pair'] , 'Partially closed long position' , json_encode($order));

                        $dataMapper->insert_log($data['account_id'] , 0 , $data['pair'] , '(Partially) closing long '.$account_settings['away_closure'].'% ('.$close_amount.' '.$data['pair'].')');
                       
                    }
                                        
                } elseif ($data['direction'] == 'short') {

                    $pos_info = $bybit->get_open_position($open_positions , $data['pair'] , 'Sell');

                    // Check if deal is open
                    if(empty($pos_info)) {
                        $dataMapper->insert_log($data['account_id'] , 0 , $data['pair'] , 'Deal not open , skip close alert [Short]');
                        continue;
                    } else {

                        $dataMapper->insert_log($data['account_id'] , 0 , $data['pair'] , 'Close position alert received [Short]');

                        // Change S/L when deal is confirmed
                        $stop_loss_price = false;
                        if($account_settings['use_stoploss']) {
                            $stop_loss_price = number_format( $pos_info['entry_price'] + ($pos_info['entry_price']  * ($account_settings['away_stoploss'] / 100)) , 4);
                        }
        
                        // Update Stop Loss
                        $update_sl = $bybit->update_position(['symbol' => $data['pair'] , 'side' => 'Sell' , 'stop_loss' => $stop_loss_price]);        
                        $dataMapper->insert_log($data['account_id'] , 0 , $data['pair'] , 'Updated S/L to '.$stop_loss_price);

                        // Add closure order
                        $close_amount = $bybit->calculate_clean_close_amount($symbols , $data['pair'] , ($pos_info['size'] * ($account_settings['away_closure'] / 100)));

                         // Reduce short order by Buying and reduce only
                        $order_params = [
                            'side' => 'Buy',
                            'symbol' => $data['pair'],
                            'order_type' => 'Market',
                            'qty' => $close_amount ,
                            'time_in_force' => 'GoodTillCancel',
                            'close_on_trigger' => false ,
                            'reduce_only' => true 
                        ];

                        $order = $bybit->create_order($order_params);

                        $dataMapper->insert_order_log($data['account_id'] , $data['pair'] , 'Partially closed short position' , json_encode($order));

                        $dataMapper->insert_log($data['account_id'] , 0 , $data['pair'] , '(Partially) closing short '.$account_settings['away_closure'].'% ('.$close_amount.' '.$data['pair'].')');
                    }
                }

                continue;                
            }

            /**
             * 
             * If message is info we need to process this in the info box
             * 
             */

            if ($message == 'info') {

                //$dataMapper->insert_info($data['account_id'] , $data['pair'] , json_encode($data['params']));
                continue;
            }


            /**
             * 
             * Check if account is active , if not we can skip this record
             * 
             */
            if($account_settings['active'] == 0) {
                $dataMapper->insert_log($data['account_id'] , 0 , '' , 'Account disabled...');
                //echo 'Account disabled...';
                continue;
            }
        
            /**
             * 
             * 
             * Get deals on the current bot , first check if there isn't allready running an order , in that case we can skipe the rest
             * 
             */


            // If hedge mode is off we just check all deals
            if(in_array($data['pair'] , $active_deal_bots ) && !$account_settings['hedge_mode']) {

                $dataMapper->insert_log($data['account_id'] , 0 , $data['pair'] , 'Deal allready running');

            // Check for open long deals if alert condition is long so we only open one long
            } elseif (in_array($data['pair'] , $active_deal_bots_long) && (empty($data['direction']) || $data['direction'] == 'long') && $account_settings['hedge_mode']) {

                $dataMapper->insert_log($data['account_id'] , 0 , $data['pair'] , 'Deal allready running [Long]');

            // Check for open short deals if alert condition is short so we only open one short
            } elseif (in_array($data['pair'] , $active_deal_bots_short) && $data['direction'] == 'short' && $account_settings['hedge_mode'] ) {

                $dataMapper->insert_log($data['account_id'] , 0 , $data['pair'] , 'Deal allready running [Short]');

            } else {

                if ( ($count_active_deals < $account_settings['max_active_deals'])  && !is_null($deals)) {

                    /**
                     * Create the deal on Bybit directly
                    */ 
                    try {

                        // First set the desired leverage
                        $set_leverage = $bybit->set_leverage(['symbol' => $data['pair'] , 'is_isolated' => $account_settings['leverage_mode'] == 'cross' ? false : true , 'buy_leverage' => $account_settings['leverage'], 'sell_leverage' => $account_settings['leverage']] );
                        $set_leverage_size = $bybit->set_leverage_size(['symbol' => $data['pair'] , 'buy_leverage' => $account_settings['leverage'], 'sell_leverage' => $account_settings['leverage']]);
                        
                        $wallet_balance = $bybit->wallet_info()['result']['USDT']['equity'];
                        $get_price = $bybit->get_symbol_value($data['pair']);

                        $current_price = $get_price['result'][0]['price'];

                        // Calculate rough order_size based on wallet balance and percentage
                        $order_size = ($wallet_balance * ( $account_settings['bo_size'] / 100)) / $current_price;

                        $clean_order_size = $bybit->calculate_clean_order_size($symbols , $data['pair'] , $order_size);

                        // Open an long position if either the direction is empty or direction is long
                        if( empty($data['direction']) || $data['direction'] == 'long') {

                            $stop_loss_price = false;
                            if($account_settings['use_stoploss']) {
                                $stop_loss_price = $current_price - ($current_price * ($account_settings['stoploss_percentage'] / 100));
                            }

                            $order_params = [
                                'side' => 'Buy',
                                'symbol' => $data['pair'],
                                'order_type' => 'Market',
                                'qty' => $clean_order_size ,
                                'time_in_force' => 'GoodTillCancel',
                                'close_on_trigger' => false ,
                                'reduce_only' => false ,
                                'stop_loss' => number_format( $stop_loss_price , 4)
                            ];

                            $order = $bybit->create_order($order_params);

                            $dataMapper->insert_order_log($data['account_id'] , $data['pair'] , 'Opened long position' , json_encode($order));

                        } elseif ($data['direction'] == 'short') {

                            $stop_loss_price = false;
                            if($account_settings['use_stoploss']) {
                                $stop_loss_price = $current_price + ($current_price * ($account_settings['stoploss_percentage'] / 100));
                            }

                            $order_params = [
                                'side' => 'Sell',
                                'symbol' => $data['pair'],
                                'order_type' => 'Market',
                                'qty' => $clean_order_size ,
                                'time_in_force' => 'GoodTillCancel',
                                'close_on_trigger' => false ,
                                'reduce_only' => false ,
                                'stop_loss' => number_format( $stop_loss_price , 4)
                            ];

                            $order = $bybit->create_order($order_params);

                            $dataMapper->insert_order_log($data['account_id'] , $data['pair'] , 'Opened short position' , json_encode($order));
                        }                        

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
        // Lets process general information not linked to an account , for example Trading Info for AlphaEdge
        } else {

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

echo 'SSB - Bybit '.date('Y-m-d H:i:s').' script ran for '.$total_time.PHP_EOL;

$dataMapper->insert_debug_log(basename(__FILE__) , $total_alerts , $errors_3c , $calls_bybit , $total_time) ;

/**
 * Close connection
 */
$dataMapper->close_connection(); 
