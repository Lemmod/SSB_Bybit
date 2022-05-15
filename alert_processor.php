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

    if ($_REQUEST['mode'] == 'test') {
       $unprocessed_alerts = ['test' => 'test'];
    }


    foreach ($unprocessed_alerts as $alert) {

        /**
         * Get the data , decode the JSON
         */
        $data = json_clean_decode($alert['input'] , TRUE);

        if ($_REQUEST['mode'] == 'test') {
             $data['pair'] = 'ETHUSDT';
             //$data['direction'] = 'short';
             $data['account_id'] = 'ML123456'; 
        }



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

                    $count_active_deals = count($deals);
                    $count_active_long_deals = count($active_deal_bots_long);
                    $count_active_short_deals = count($active_deal_bots_short);

                   

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
             * Set the trigger value , replace if empty
             */
            if (empty($data['trigger'])) {
                $trigger = 'no_trigger';
            } else {
                $trigger = $data['trigger'];
            }

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
                $dataMapper->insert_log($data['account_id'] , 0 , $data['pair'] , 'Account disabled...');
                //echo 'Account disabled...';
                continue;
            }


            /**
             * 
             * Close (partial) position based on TV Alert
             * 
             */
            if($message == 'close_awaymode') {

                // If away mode is disabled for current account we can skip these alerts
                if($account_settings['away_mode'] == 0) {

                    $dataMapper->insert_log($data['account_id'] , 0 , $data['pair'] , 'Away mode not enabled, skip alert');
                    continue;
                }

                if( empty($data['direction']) || $data['direction'] == 'long') {

                    $pos_info = $bybit->get_open_position($open_positions , $data['pair'] , 'Buy');
                    $latest_trigger = $dataReader->get_latest_order_trigger($data['account_id'],$data['pair'],'open_long');

                    $type = '[AWAY_LONG]';

                    // Check if deal is open
                    if(empty($pos_info)) {
                        $dataMapper->insert_log($data['account_id'] , 0 , $data['pair'] , $type.' Deal not open , skip alert.');
                        continue;
                    } elseif ($latest_trigger['trigger_condition'] != $trigger) {
                        $dataMapper->insert_log($data['account_id'] , 0 , $data['pair'] , $type.' Different trigger , skip alert. Trigger alert was '.$trigger.' while deal was opened with trigger '.$latest_trigger['trigger_condition']);
                        continue; 
                    } elseif ($latest_trigger['away_mode_triggered']) {
                        $dataMapper->insert_log($data['account_id'] , 0 , $data['pair'] , $type.' Away mode already triggered , skip alert');
                        continue;   
                    } else {

                        $dataMapper->insert_log($data['account_id'] , 0 , $data['pair'] , $type.' Close position alert received');

                        // Change S/L when deal is confirmed
                        $stop_loss_price = false;
                        if($account_settings['use_stoploss']) {
                            $stop_loss_price = number_format( $pos_info['entry_price'] - ($pos_info['entry_price']  * ($account_settings['away_stoploss'] / 100)) , 4 , '.' , '');
                        }
        
                        // Update Stop Loss
                        $update_sl = $bybit->update_position(['symbol' => $data['pair'] , 'side' => 'Buy' , 'stop_loss' => $stop_loss_price]);
                        $dataMapper->insert_log($data['account_id'] , 0 , $data['pair'] , $type.' Updated S/L to '.$stop_loss_price);
                        $dataMapper->insert_order_log($data['account_id'] , $data['pair'] , 'edit_sl_long' , $data['condition'] , 'Edit S/L' , json_encode($update_sl));

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
        
                        $dataMapper->insert_order_log($data['account_id'] , $data['pair'] , 'partial_close_long' , $trigger  , 'Partially closed long position' , json_encode($order));

                        $dataMapper->insert_log($data['account_id'] , 0 , $data['pair'] , $type.' (Partially) closing long '.$account_settings['away_closure'].'% ('.$close_amount.' '.$data['pair'].')');

                        $dataMapper->update_order_awaymode_triggered($latest_trigger['order_id']);
                       
                    }
                                        
                } elseif ($data['direction'] == 'short') {

                    $pos_info = $bybit->get_open_position($open_positions , $data['pair'] , 'Sell');
                    $latest_trigger = $dataReader->get_latest_order_trigger($data['account_id'],$data['pair'],'open_short');

                    $type = '[AWAY_SHORT]';

                    // Check if deal is open
                    if(empty($pos_info)) {
                        $dataMapper->insert_log($data['account_id'] , 0 , $data['pair'] , $type.' Deal not open , skip alert');
                        continue;
                    } elseif ($latest_trigger['trigger_condition'] != $trigger) {
                        $dataMapper->insert_log($data['account_id'] , 0 , $data['pair'] , $type.' Different trigger , skip alert. Trigger alert was '.$trigger.' while latest trigger was '.$latest_trigger['trigger_condition']);
                        continue;  
                    } elseif ($latest_trigger['away_mode_triggered']) {
                        $dataMapper->insert_log($data['account_id'] , 0 , $data['pair'] , $type.' Away mode already triggered , skip alert');
                    } else {

                        $dataMapper->insert_log($data['account_id'] , 0 , $data['pair'] , $type.' Close position alert received');

                        // Change S/L when deal is confirmed
                        $stop_loss_price = false;
                        if($account_settings['use_stoploss']) {
                            $stop_loss_price = number_format( $pos_info['entry_price'] + ($pos_info['entry_price']  * ($account_settings['away_stoploss'] / 100)) , 4 , '.' , '');
                        }
        
                        // Update Stop Loss
                        $update_sl = $bybit->update_position(['symbol' => $data['pair'] , 'side' => 'Sell' , 'stop_loss' => $stop_loss_price]);        
                        $dataMapper->insert_log($data['account_id'] , 0 , $data['pair'] , $type.' Updated S/L to '.$stop_loss_price);
                        $dataMapper->insert_order_log($data['account_id'] , $data['pair'] , 'edit_sl_short' , $data['condition'] , 'Edit S/L' , json_encode($update_sl));

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

                        $dataMapper->insert_order_log($data['account_id'] , $data['pair'] , 'partial_close_short' , $trigger , 'Partially closed short position' , json_encode($order));

                        $dataMapper->insert_log($data['account_id'] , 0 , $data['pair'] , $type.' (Partially) closing short '.$account_settings['away_closure'].'% ('.$close_amount.' '.$data['pair'].')');

                        $dataMapper->update_order_awaymode_triggered($latest_trigger['order_id']);
                    }
                }
                continue;                
            }

            /**
             * 
             * Close the whole position. Similar to away mode but always with 100% of position size
             * 
             */
            if($message == 'close_position') {

                if( empty($data['direction']) || $data['direction'] == 'long') {

                    $pos_info = $bybit->get_open_position($open_positions , $data['pair'] , 'Buy');
                    $latest_trigger = $dataReader->get_latest_order_trigger($data['account_id'],$data['pair'],'open_long');

                    $type = '[CLOSE_LONG]';

                    // Check if deal is open
                    if(empty($pos_info)) {
                        $dataMapper->insert_log($data['account_id'] , 0 , $data['pair'] , $type.' Deal not open , skip alert.');
                        continue;
                    } elseif ($latest_trigger['trigger_condition'] != $trigger) {
                        $dataMapper->insert_log($data['account_id'] , 0 , $data['pair'] , $type.' Different trigger , skip alert. Trigger alert was '.$trigger.' while deal was opened with trigger '.$latest_trigger['trigger_condition']);
                        continue; 
                    } else {

                        $dataMapper->insert_log($data['account_id'] , 0 , $data['pair'] , $type.' Close position alert received');

                        // Add closure order
                        $close_amount = $bybit->calculate_clean_close_amount($symbols , $data['pair'] , $pos_info['size']);

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
        
                        $dataMapper->insert_order_log($data['account_id'] , $data['pair'] , 'whole_close_long' , $trigger  , 'Close whole long position' , json_encode($order));

                        $dataMapper->insert_log($data['account_id'] , 0 , $data['pair'] , $type.' Closing whole long position');
                       
                    }
                                        
                } elseif ($data['direction'] == 'short') {

                    $pos_info = $bybit->get_open_position($open_positions , $data['pair'] , 'Sell');
                    $latest_trigger = $dataReader->get_latest_order_trigger($data['account_id'],$data['pair'],'open_short');

                    $type = '[CLOSE_SHORT]';

                    // Check if deal is open
                    if(empty($pos_info)) {
                        $dataMapper->insert_log($data['account_id'] , 0 , $data['pair'] , $type.' Deal not open , skip alert');
                        continue;
                    } elseif ($latest_trigger['trigger_condition'] != $trigger) {
                        $dataMapper->insert_log($data['account_id'] , 0 , $data['pair'] , $type.' Different trigger , skip alert. Trigger alert was '.$trigger.' while latest trigger was '.$latest_trigger['trigger_condition']);
                        continue;  
                    } else {

                        $dataMapper->insert_log($data['account_id'] , 0 , $data['pair'] , $type.' Close position alert received');

                        // Add closure order
                        $close_amount = $bybit->calculate_clean_close_amount($symbols , $data['pair'] , $pos_info['size']);

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

                        $dataMapper->insert_order_log($data['account_id'] , $data['pair'] , 'whole_close_short' , $trigger , 'Close whole short position' , json_encode($order));

                        $dataMapper->insert_log($data['account_id'] , 0 , $data['pair'] , $type.' Closing whole short position');

                    }
                }

                continue;                
            }
        
            /**
             * 
             * 
             * Get deals on the current bot , first check if there isn't already running an order , in that case we can skip the rest
             * 
             */


            // If hedge mode is off we just check all deals
            if(in_array($data['pair'] , $active_deal_bots ) && !$account_settings['hedge_mode']) {

                $dataMapper->insert_log($data['account_id'] , 0 , $data['pair'] , 'Deal already running');

            // Check for open long deals if alert condition is long so we only open one long
            } elseif (in_array($data['pair'] , $active_deal_bots_long) && (empty($data['direction']) || $data['direction'] == 'long') && $account_settings['hedge_mode']) {

                $dataMapper->insert_log($data['account_id'] , 0 , $data['pair'] , 'Deal already running [Long]');

            // Check for open short deals if alert condition is short so we only open one short
            } elseif (in_array($data['pair'] , $active_deal_bots_short) && $data['direction'] == 'short' && $account_settings['hedge_mode'] ) {

                $dataMapper->insert_log($data['account_id'] , 0 , $data['pair'] , 'Deal already running [Short]');

            // If we are in short_only mode we don't want to open long deals
             } elseif ( (empty($data['direction']) || $data['direction'] == 'long') && $account_settings['mad_direction'] == 'short_only') {

                $dataMapper->insert_log($data['account_id'] , 0 , $data['pair'] , 'Bot is set to Short Only. Long alerts will be discarded');

            // If we are in short_only mode we don't want to open long deals
            } elseif ( $data['direction'] == 'short' && $account_settings['mad_direction'] == 'long_only') {

                $dataMapper->insert_log($data['account_id'] , 0 , $data['pair'] , 'Bot is set to Long Only. Short alerts will be discarded');
            
            // Skip if we exceeded the max active deals
            } elseif ( $account_settings['mad_direction'] == 'both' && $count_active_deals >= $account_settings['max_active_deals']) {

                $dataMapper->insert_log($data['account_id'] , 0 , $data['pair'] , 'Deal not added , max active deals hit on both direction mode ( Active : '.$count_active_deals.' , Max : '.$account_settings['max_active_deals'].' )');

            // Skip if we exceeded the max active deals
            } elseif ( $account_settings['mad_direction'] == 'short_only' && $count_active_short_deals >= $account_settings['max_active_deals']) {

                $dataMapper->insert_log($data['account_id'] , 0 , $data['pair'] , 'Deal not added , max active deals hit on short only direction mode ( Active : '.$count_active_short_deals.' , Max : '.$account_settings['max_active_deals'].' )');

            // Skip if we exceeded the max active deals
            } elseif ( $account_settings['mad_direction'] == 'long_only' && $count_active_long_deals >= $account_settings['max_active_deals']) {

                $dataMapper->insert_log($data['account_id'] , 0 , $data['pair'] , 'Deal not added , max active deals hit on long only direction mode ( Active : '.$count_active_long_deals.' , Max : '.$account_settings['max_active_deals'].' )');

            } else {

                // Extra safety check if Bybit API fails
                if ( !is_null($deals)) {

                    /**
                     * Create the deal on Bybit directly
                    */ 
                    try {

                        // First set the desired leverage
                        $set_leverage = $bybit->set_leverage(['symbol' => $data['pair'] , 'is_isolated' => $account_settings['leverage_mode'] == 'cross' ? false : true , 'buy_leverage' => $account_settings['leverage'], 'sell_leverage' => $account_settings['leverage']] );
                        $set_leverage_size = $bybit->set_leverage_size(['symbol' => $data['pair'] , 'buy_leverage' => $account_settings['leverage'], 'sell_leverage' => $account_settings['leverage']]);

                        $wallet_balance = $bybit->wallet_info()['result']['USDT']['wallet_balance'];
                        $get_price = $bybit->get_ticker($data['pair']);

                        // Get the current price to determine order size and S/L value
                        $current_price = $get_price['result'][0]['bid_price'];                        

                        // Calculate rough order_size based on wallet balance and percentage
                        $order_size = ($wallet_balance * ( $account_settings['bo_size'] / 100)) / $current_price;

                        $clean_order_size = $bybit->calculate_clean_order_size($symbols , $data['pair'] , $order_size);

                        // Open an long position if either the direction is empty or direction is long
                        if( empty($data['direction']) || $data['direction'] == 'long') {

                            $type = '[OPEN_LONG]';

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
                                'stop_loss' => number_format( $stop_loss_price , 4 , '.' , '') ,
                                'order_link_id' => $data['account_id'].'_openlong_'.time()
                            ];

                            $order = $bybit->create_order($order_params);
               
                            $dataMapper->insert_order_log($data['account_id'] , $data['pair'] , 'open_long' , $trigger , 'Opened long position' , json_encode($order));     

                        } elseif ($data['direction'] == 'short') {

                            $type = '[OPEN_SHORT]';

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
                                'stop_loss' => number_format( $stop_loss_price , 4 , '.' , '') ,
                                'order_link_id' => $data['account_id'].'_openshort_'.time()
                            ];

                            $order = $bybit->create_order($order_params);

                            $dataMapper->insert_order_log($data['account_id'] , $data['pair'] , 'open_short' , $trigger , 'Opened short position' , json_encode($order));
                        }                        

                        if ( $account_settings['mad_direction'] == 'both') {
                            $dataMapper->insert_log($data['account_id'] , 0 , $data['pair'] , $type.' Deal added ( Active : '.($count_active_deals + 1).' , Max : '.$account_settings['max_active_deals'].' ). Triggered by '.$trigger);
                            // Add the new deal to open deals and count the extra deal to not overshoot
                            array_push($active_deal_bots , $data['pair']);

                            if (empty($data['direction']) || $data['direction'] == 'long') {
                                array_push($active_deal_bots_long , $data['pair']);
                            }

                            if ($data['direction'] == 'short') {
                                array_push($active_deal_bots_short , $data['pair']);
                            }

                            $count_active_deals++;
                        } elseif ( $account_settings['mad_direction'] == 'long_only') {
                            $dataMapper->insert_log($data['account_id'] , 0 , $data['pair'] , $type.' Deal added ( Active : '.($count_active_long_deals + 1).' , Max : '.$account_settings['max_active_deals'].' ). Triggered by '.$trigger);
                            // Add the new deal to open deals and count the extra deal to not overshoot
                            array_push($active_deal_bots_long , $data['pair']);
                            $count_active_long_deals++;
                        } elseif ( $account_settings['mad_direction'] == 'short_only') {
                            $dataMapper->insert_log($data['account_id'] , 0 , $data['pair'] , $type.' Deal added ( Active : '.($count_active_short_deals + 1).' , Max : '.$account_settings['max_active_deals'].' ). Triggered by '.$trigger);
                            // Add the new deal to open deals and count the extra deal to not overshoot
                            array_push($active_deal_bots_short , $data['pair']);
                            $count_active_short_deals++;
                        }

                        $calls_bybit++;

                    } catch (Exception $e) {
                        echo ' > Caught exception: '.$e->getMessage().'.'.PHP_EOL;
                    }
                
                } else {
                    /**
                     * Log reason for not able to add deal
                     */
                    if (is_null($deals)) {
                        $errors_3c++;
                        $dataMapper->insert_log($data['account_id'] , 0, $data['pair'] , 'Deal not added , ERROR - Bybit deal count is null');
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
