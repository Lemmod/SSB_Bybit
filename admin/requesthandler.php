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
 
        $i++;
    }

    $table = new STable();
    $table->class = 'table table-hover table-striped table-bordered';
    $table->width = '100%';

    $table->thead()
        ->th('Account ID')
        ->th('Name')
        ->th('Max deals')
        ->th('BO (%)')
        ->th('Set leverage')
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
        ->td(create_dropdown_number_with_id(1 , 100 , 'bo_size' , 'bo_size' , 'account_'.$response['internal_id'] , $response['bo_size']))
        ->td(create_dropdown_number_with_id(0 , 100 , 'leverage' , 'leverage' , 'account_'.$response['internal_id'] , $response['leverage']))
        ->td($switch)
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

if($action == 'load_tv_alerts') {

    $explode = explode('_' , $_REQUEST['id']);
    $internal_account_id = $explode[1];

    $account_info = $dataReader->get_account_info_internal($internal_account_id);

    // Terminate if the user is nog logged in
    check_credentials($account_info['user_id']);

    $bybit = new BybitConnector($account_info['api_key'] , $account_info['api_secret']);
    $symbols = $bybit->get_symbols();

    echo '<h2> Trading view alerts </h2>';

    echo '<h3> General alerts</h2>';

    $table = new STable();
    $table->class = 'table table-hover table-striped table-bordered';
    $table->width = '100%';

    $table->thead()
    ->th('Type:')
    ->th('Alert');



    $table->tr()
    ->td('Enable account')
    ->td(json_encode(['account_id' => (int)$account_info['bot_account_id'] , 'message' => 'enable_bots']  ));

    $table->tr()
    ->td('Disable account')
    ->td(json_encode(['account_id' => (int)$account_info['bot_account_id'] , 'message' => 'disable_bots']  ));


    echo $table->getTable();

    $table = '';
         
    
    echo '<h3> Bot specific alerts</h2>';

    $table = new STable();
    $table->class = 'table table-hover table-striped table-bordered';
    $table->id = 'bot_spec_alerts';
    $table->width = '100%';

    $table->thead()
        ->th('Pair :')
        ->th('Alert');
    
   
    foreach ($symbols['result'] as $bot) {

        if ($bot['quote_currency'] == 'USDT') {

            //$bot['id'] = '87654321';

            $result['account_id'] = (int)$account_info['bot_account_id'];
            $result['pair'] = $bot['name'];

            $table->tr()
                ->td($bot['name'])
                ->td('<span class="copy_text">'.json_encode($result).'</span>');
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

    $log_data = $dataReader->get_logbook($account_info['bot_account_id']);

    echo '<h2> Logbook </h2>';

    $table = new STable();
    $table->class = 'table table-hover table-striped table-bordered';
    $table->id = 'logbook_table';
    $table->width = '100%';

    $table->thead()
    ->th('Date / time :')
    ->th('Pair :')
    ->th('Message :');

    foreach($log_data as $log) {
        $table->tr()
        ->td($log['timestamp'])
        ->td($log['pair'])
        ->td($log['message']);

    }

    echo $table->getTable();
}

if($action == 'load_debuglog') {

    $log_data = $dataReader->get_debuglog();

    
    echo '<h2> Debug log </h2>';

    $table = new STable();
    $table->class = 'table table-hover table-striped table-bordered';
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
?> 
