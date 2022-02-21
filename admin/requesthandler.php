<?php
error_reporting(E_ERROR);
// We need to use sessions, so you should always start sessions using the below code.
session_start();
// If the user is not logged in redirect to the login page...
if (!isset($_SESSION['loggedin'])) {
	header('Location: index.php?response=notloggedin');
	die;
}

include ('../app/Config.php');
include ('../app/Core.php');
include ('../app/BybitConnector.php');
include ('../app/DataMapper.php');
include ('../app/DataReader.php');
include ('../app/Table.php');
include ('../app/functions.php');

$dataMapper = new DataMapper();
$dataReader = new DataReader();

$action = $_REQUEST['action'];

/**
 * Load all accounts for current user
 */
if($action == 'load_all_accounts') {

    $accounts = $dataReader->get_user_accounts($_SESSION['user_id']);

    // Terminate if the user is nog logged in
    check_credentials($_SESSION['user_id']);

    $account_response = [];

    $i = 0;
    foreach ($accounts as $account) {

        $settings = $dataReader->get_account_settings($account['internal_account_id']);

        $account_info = $dataReader->get_account_info_internal($account['internal_account_id']);

     
        $account_response[$i]['internal_id'] = $account['internal_account_id'];
        $account_response[$i]['3c_id'] = $account['bot_account_id'];
        $account_response[$i]['internal_name'] = $account['account_name'];
        $account_response[$i]['mad'] = $settings['max_active_deals'];
        $account_response[$i]['bo_size'] = $settings['bo_size'];
        $account_response[$i]['active'] = $settings['active'];
        $account_response[$i]['leverage'] = $settings['leverage'];
        $account_response[$i]['leverage_mode'] = $settings['leverage_mode'];
        $account_response[$i]['hedge_mode'] = $settings['hedge_mode'];
        $account_response[$i]['use_stoploss'] = $settings['use_stoploss'];
        $account_response[$i]['stoploss_percentage'] = $settings['stoploss_percentage'];
        $account_response[$i]['away_mode'] = $settings['away_mode'];
        $account_response[$i]['away_closure'] = $settings['away_closure'];
        $account_response[$i]['away_stoploss'] = $settings['away_stoploss'];
 
        $i++;
    }

   

    $table = new STable();
    $table->class = 'table table-hover table-striped table-dark table-bordered';
    $table->id = 'account_table';
    $table->width = '100%';

    $table->thead()
        ->th('Account ID')
        ->th('Name')
        ->th('Max deals')
        ->th('BO (%)')
        ->th('Leverage x')
        ->th('Leverage type')
        ->th('Hedge mode')
        ->th('Stop Loss')
        ->th('Stop Loss %')
        ->th('Away mode')
        ->th('Away close %')
        ->th('Away move S/L %')
        //->th('S/L %')
        ->th('Status')
        ->th('TV Alerts')
        ->th('Logs')
        ->th('Delete');
        
   
    foreach ($account_response as $response) {

        if($response['active'] == 1) {
            $switch = '<a class="disable_account_link" id="account_'.$response['internal_id'].'"><i class="fas fa-stop-circle"></i> Disable</a>';
        }
        if($response['active'] == 0) {
            $switch = '<a class="enable_account_link" id="account_'.$response['internal_id'].'"><i class="fas fa-play-circle"></i> Enable</a>';
        }

        $table->tr()
        ->td($response['3c_id'])
        ->td($response['internal_name'])
        ->td(create_dropdown_number_with_id(0 , 20 , 'mad_dropdown' , 'mad_dropdown' , 'account_'.$response['internal_id'] , $response['mad']))
        ->td(create_dropdown_number_with_id(1 , 500 , 'bo_size' , 'bo_size' , 'account_'.$response['internal_id'] , $response['bo_size'] , 1))
        ->td(create_dropdown_number_with_id(0 , 100 , 'leverage' , 'leverage' , 'account_'.$response['internal_id'] , $response['leverage']))
        ->td(create_dropdown_options(['cross' , 'isolated'], '' , 'leverage_mode' , 'account_'.$response['internal_id'] , $response['leverage_mode']))
        ->td(create_dropdown_options(['0' => 'Disabled' , '1' => 'Enabled'], '' , 'hedge_mode' , 'account_'.$response['internal_id'] , $response['hedge_mode'] , true))
        ->td(create_dropdown_options(['0' => 'Disabled' , '1' => 'Enabled'], '' , 'use_stoploss' , 'account_'.$response['internal_id'] , $response['use_stoploss'] , true))
        ->td(create_dropdown_number_with_id(0 , 100 , 'stoploss_percentage' , 'stoploss_percentage' , 'account_'.$response['internal_id'] , $response['stoploss_percentage'] , 0.5))
        ->td(create_dropdown_options(['0' => 'Disabled' , '1' => 'Enabled'], '' , 'away_mode' , 'account_'.$response['internal_id'] , $response['away_mode'] , true))
        ->td(create_dropdown_number_with_id(0 , 100 , 'away_closure' , 'away_closure' , 'account_'.$response['internal_id'] , $response['away_closure']))
        ->td(create_dropdown_number_with_id(0 , 100 , 'away_stoploss' , 'away_stoploss' , 'account_'.$response['internal_id'] , $response['away_stoploss']))
        ->td(create_dropdown_options(['0' => 'Disabled' , '1' => 'Enabled'], '' , 'active' , 'account_'.$response['internal_id'] , $response['active'] , true))
        //->td($switch)
        ->td('<a class="tv_alerts_link" id="account_'.$response['internal_id'].'"><i class="fas fa-chart-bar"></i>  Trading View alerts</a>')
        ->td('<a class="logbook_link" id="account_'.$response['internal_id'].'"><i class="fas fa-book"></i>  Logbook</a>')
        ->td('<a class="delete_account_link" id="account_'.$response['internal_id'].'"><i class="fas fa-trash"></i>  Delete</a>');
    }

    echo $table->getTable();

}

/**
 * Add an account
 */
if($action == 'add_account') {

    try {
        $account_id = $dataMapper->insert_account($_SESSION['user_id'] , $_REQUEST['bot_account_id'] , $_REQUEST['account_name'] , $_REQUEST['api_key'] , $_REQUEST['api_secret']);
        $add_settings = $dataMapper->insert_account_settings($account_id);
    } catch (PDOExecption $e){
        echo $e->getMessage();
    }  

    echo 'Account added.';
}

/**
 * Edit an account
 */
if($action == 'edit_account') {

    $explode = explode('_' , $_REQUEST['id']);
    $internal_account_id = $explode[1];

    $account_info = $dataReader->get_account_info_internal($internal_account_id);

    // Terminate if the user is nog logged in
    check_credentials($account_info['user_id']);

    $dataMapper->edit_account($_SESSION['user_id'] , $internal_account_id);

    echo 'Account deleted.';
}

/**
 * Delete an account
 */
if($action == 'delete_account') {

    $explode = explode('_' , $_REQUEST['id']);
    $internal_account_id = $explode[1];

    $account_info = $dataReader->get_account_info_internal($internal_account_id);

    // Terminate if the user is nog logged in
    check_credentials($account_info['user_id']);

    $dataMapper->delete_account($_SESSION['user_id'] , $internal_account_id);

    echo 'Account deleted.';
}

/**
 * Disable an account
 */
if($action == 'disable_account') {

    $explode = explode('_' , $_REQUEST['id']);
    $internal_account_id = $explode[1];

    $account_info = $dataReader->get_account_info_internal($internal_account_id);

    // Terminate if the user is nog logged in
    check_credentials($account_info['user_id']);

    $dataMapper->enable_disable_account($internal_account_id , 0);

    echo 'Account disabled.';
}

/**
 * Enable an account
 */
if($action == 'enable_account') {

    $explode = explode('_' , $_REQUEST['id']);
    $internal_account_id = $explode[1];

    $account_info = $dataReader->get_account_info_internal($internal_account_id);

    // Terminate if the user is nog logged in
    check_credentials($account_info['user_id']);

    $dataMapper->enable_disable_account($internal_account_id , 1);

    echo 'Account enabled.';
}

/**
 * Update max acticve deals
 */
if($action == 'update_max_active_deals') {

    $explode = explode('_' , $_REQUEST['id']);
    $internal_account_id = $explode[1];

    $account_info = $dataReader->get_account_info_internal($internal_account_id);

    // Terminate if the user is nog logged in
    check_credentials($account_info['user_id']);

    $dataMapper->update_max_active_deals($internal_account_id , $_REQUEST['deals']);

    echo 'Max deals set to '.$_REQUEST['deals'];
}

/**
 * Update BO/SO size
 */
if($action == 'update_bo_size') {

    $explode = explode('_' , $_REQUEST['id']);
    $internal_account_id = $explode[1];

    $size = $_REQUEST['size'];

    // Get settings to create 3C connection
    $account_info = $dataReader->get_account_info_internal($internal_account_id);

    // Terminate if the user is nog logged in
    check_credentials($account_info['user_id']);

    $dataMapper->update_bo_size($internal_account_id , $_REQUEST['size']);

    echo 'Size updated to '.$size.'.';
}

/**
 * Update Leverage
 */
if($action == 'update_leverage') {

    $explode = explode('_' , $_REQUEST['id']);
    $internal_account_id = $explode[1];

    $leverage = $_REQUEST['leverage'];

    // Get settings to create 3C connection
    $account_info = $dataReader->get_account_info_internal($internal_account_id);

    // Terminate if the user is nog logged in
    check_credentials($account_info['user_id']);

    $dataMapper->update_leverage($internal_account_id , $leverage);

    echo 'Leverage updated to '.$leverage.'x.';
}

/**
 * Update Leverage
 */
if($action == 'update_leverage_mode') {

    $explode = explode('_' , $_REQUEST['id']);
    $internal_account_id = $explode[1];

    $leverage_mode = $_REQUEST['mode'];

    // Get settings to create 3C connection
    $account_info = $dataReader->get_account_info_internal($internal_account_id);

    // Terminate if the user is nog logged in
    check_credentials($account_info['user_id']);

    $dataMapper->update_leverage_mode($internal_account_id , $leverage_mode);

    echo 'Leverage mode set to : '.$leverage_mode;
}


/**
 * Update Leverage
 */
if($action == 'update_hedge') {

    $explode = explode('_' , $_REQUEST['id']);
    $internal_account_id = $explode[1];

    $hedge_mode = $_REQUEST['mode'];

    // Get settings to create 3C connection
    $account_info = $dataReader->get_account_info_internal($internal_account_id);

    // Terminate if the user is nog logged in
    check_credentials($account_info['user_id']);

    $dataMapper->update_hedge($internal_account_id , $hedge_mode);

    echo 'Updated hedge mode setting.';
}

/**
 * Update S/L setting
 */
if($action == 'update_stoploss') {

    $explode = explode('_' , $_REQUEST['id']);
    $internal_account_id = $explode[1];

    $setting = $_REQUEST['setting'];

    // Get settings to create 3C connection
    $account_info = $dataReader->get_account_info_internal($internal_account_id);

    // Terminate if the user is nog logged in
    check_credentials($account_info['user_id']);

    $dataMapper->update_stoploss($internal_account_id , $setting);

    echo 'Updated S/L setting.';
}

/**
 * Update S/L percentage
 */
if($action == 'update_stoploss_percentage') {

    $explode = explode('_' , $_REQUEST['id']);
    $internal_account_id = $explode[1];

    $percentage = $_REQUEST['percentage'];

    // Get settings to create 3C connection
    $account_info = $dataReader->get_account_info_internal($internal_account_id);

    // Terminate if the user is nog logged in
    check_credentials($account_info['user_id']);

    $dataMapper->update_stoploss_percentage($internal_account_id , $percentage);

    echo 'Updated S/L percentage.';
}


/**
 * Update S/L percentage
 */
if($action == 'update_away_mode') {

    $explode = explode('_' , $_REQUEST['id']);
    $internal_account_id = $explode[1];

    $status = $_REQUEST['status'];

    // Get settings to create 3C connection
    $account_info = $dataReader->get_account_info_internal($internal_account_id);

    // Terminate if the user is nog logged in
    check_credentials($account_info['user_id']);

    $dataMapper->update_away_mode($internal_account_id , $status);

    echo 'Away mode status changed'.$status;
}

/**
 * Update S/L percentage
 */
if($action == 'update_away_closure') {

    $explode = explode('_' , $_REQUEST['id']);
    $internal_account_id = $explode[1];

    $percentage = $_REQUEST['percentage'];

    // Get settings to create 3C connection
    $account_info = $dataReader->get_account_info_internal($internal_account_id);

    // Terminate if the user is nog logged in
    check_credentials($account_info['user_id']);

    $dataMapper->update_away_closure($internal_account_id , $percentage);

    echo 'Updated away setting close%.';
}

/**
 * Update S/L percentage
 */
if($action == 'update_away_stoploss') {

    $explode = explode('_' , $_REQUEST['id']);
    $internal_account_id = $explode[1];

    $percentage = $_REQUEST['percentage'];

    // Get settings to create 3C connection
    $account_info = $dataReader->get_account_info_internal($internal_account_id);

    // Terminate if the user is nog logged in
    check_credentials($account_info['user_id']);

    $dataMapper->update_away_stoploss($internal_account_id , $percentage);

    echo 'Updated away S/L percentage to '.$percentage.' %';
}

/**
 * Update S/L percentage
 */
if($action == 'update_active') {

    $explode = explode('_' , $_REQUEST['id']);
    $internal_account_id = $explode[1];

    $status = $_REQUEST['status'];

    // Get settings to create 3C connection
    $account_info = $dataReader->get_account_info_internal($internal_account_id);

    // Terminate if the user is nog logged in
    check_credentials($account_info['user_id']);

    $dataMapper->update_active($internal_account_id , $status);

    echo 'Bot status updated';
}

if($action == 'load_tv_alerts') {

    $explode = explode('_' , $_REQUEST['id']);
    $internal_account_id = $explode[1];

    $account_info = $dataReader->get_account_info_internal($internal_account_id);

    // Terminate if the user is nog logged in
    check_credentials($account_info['user_id']);

    $bybit = new BybitConnector($account_info['api_key'] , $account_info['api_secret']);
    $symbols = $bybit->get_symbols();

    echo '<h2> Trading view alerts </h2>';

    echo '<h3> General alerts </h2>';

    $table = new STable();
    $table->class = 'table table-hover table-striped table-dark table-bordered';
    $table->width = '100%';

    $table->thead()
    ->th('Type:')
    ->th('Alert');
    



    $table->tr()
    ->td('Enable account')
    ->td(json_encode(['account_id' => $account_info['bot_account_id'] , 'message' => 'enable_bots']  ));

    $table->tr()
    ->td('Disable account')
    ->td(json_encode(['account_id' => $account_info['bot_account_id'] , 'message' => 'disable_bots']  ));


    echo $table->getTable();

    $table = '';
         
    
    echo '<h3> Bot specific alerts</h2>';

    $table = new STable();
    $table->class = 'table table-hover table-striped table-dark table-bordered';
    $table->id = 'bot_spec_alerts';
    $table->width = '100%';

    $table->thead()
        ->th('Pair :')
        ->th('Long')
        ->th('Short')
        ->th('Long Take Profit')
        ->th('Short Take profit');
    
   
    foreach ($symbols['result'] as $bot) {

        if ($bot['quote_currency'] == 'USDT') {

            //$bot['id'] = '87654321';

            $result['account_id'] = $account_info['bot_account_id'];
            $result['pair'] = $bot['name'];
            $result['direction'] = 'long';

            $result_short['account_id'] = $account_info['bot_account_id'];
            $result_short['pair'] = $bot['name'];
            $result_short['direction'] = 'short';

            $result_tp_long['account_id'] = $account_info['bot_account_id'];
            $result_tp_long['message'] = 'close_position';
            $result_tp_long['pair'] = $bot['name'];
            $result_tp_long['direction'] = 'long';

            $result_tp_short['account_id'] = $account_info['bot_account_id'];
            $result_tp_short['message'] = 'close_position';
            $result_tp_short['pair'] = $bot['name'];
            $result_tp_short['direction'] = 'long';

            $table->tr()
                ->td($bot['name'])
                ->td('<span class="copy_text">'.json_encode($result).'</span>')
                ->td('<span class="copy_text">'.json_encode($result_short).'</span>')
                ->td('<span class="copy_text">'.json_encode($result_tp_long).'</span>')
                ->td('<span class="copy_text">'.json_encode($result_tp_short).'</span>');
        }
    }

    echo $table->getTable();

}

if($action == 'load_logbook') {

    $explode = explode('_' , $_REQUEST['id']);
    $internal_account_id = $explode[1];

    $account_info = $dataReader->get_account_info_internal($internal_account_id);

    // Terminate if the user is nog logged in
    check_credentials($account_info['user_id']);  

    $log_data = $dataReader->get_logbook($account_info['bot_account_id'] , 7);

    echo '<h2> Logbook </h2>';

    $table = new STable();
    $table->class = 'table table-hover table-striped table-dark table-bordered';
    $table->id = 'logbook_table';
    $table->width = '100%';

    $table->thead()
    ->th('Log ID')
    ->th('Date / time :')
    ->th('Pair :')
    ->th('Message :');

    foreach($log_data as $log) {
        $table->tr()
        ->td($log['log_id'])
        ->td($log['timestamp'])
        ->td($log['pair'])
        ->td($log['message']);

    }

    echo $table->getTable();

    /* $info_data = $dataReader->get_info($account_info['bot_account_id'] , 1);

    echo '<h2> Info </h2>';

    $table = new STable();
    $table->class = 'table table-hover table-striped table-dark table-bordered';
    $table->id = 'info_table';
    $table->width = '100%';

    $table->thead()
    ->th('Date / time :')
    ->th('Pair :')
    ->th('Information:');

    foreach($info_data as $info) {

        $params = json_decode($info['params']);

        $info_text = '';
        foreach ($params as $key => $value) {
            $info_text.= '<strong>'.$key.'</strong> : '.$value.' &emsp;';
        }

        $table->tr()
        ->td($info['timestamp'])
        ->td($info['pair'])
        ->td($info_text);

    }

    echo $table->getTable(); */
}

if($action == 'load_debuglog') {

    $log_data = $dataReader->get_debuglog();

    
    echo '<h2> Debug log </h2>';

    $table = new STable();
    $table->class = 'table table-hover table-striped table-dark table-bordered';
    $table->id = 'debug_table';
    $table->width = '100%';

    $table->thead()
    ->th('Date / time :')
    ->th('Jobs :')
    ->th('Alerts :')
    ->th('Calls to Bybit :')
    ->th('Calls / Alerts :')
    ->th('Errors :')
    ->th('Avg. job time :')
    ->th('Max. job time :')
    ->th('Exceed 15 secs :')
    ->th('Exceed 30 secs :');

    foreach($log_data as $log) {
        $table->tr()
        ->td($log['time'])
        ->td($log['jobs'])
        ->td($log['alerts'])
        ->td($log['calls'])
        ->td($log['average_calls'])
        ->td($log['errors'])
        ->td($log['avg_job_time'])
        ->td($log['max_job_time'])
        ->td($log['exceed_15s'])
        ->td($log['exceed_30s']);

    }

    echo $table->getTable();
}

if ($action == 'load_status') {

    $seconds_passed = $dataReader->get_passed_time_debug();

    // If there is no signal (debug log) for 30 seconds we may consider the bot offline
    if ($seconds_passed < 30) {
        echo '<span style="color :#00FF00">Online</span>';
    } else {
        echo '<span style="color :#FF0000">Offline</span>';
    }

}
?> 
