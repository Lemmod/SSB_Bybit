<?php


function insert_log($pdo , $account_id , $bot_id , $pair , $message) {

    // Try starting insert the log file
    try{
               
        $stmt = $pdo->prepare("INSERT INTO log (account_id , bot_id , pair ,  message) VALUES (:account_id , :bot_id , :pair ,  :message)");
        $stmt->bindParam(':account_id', $account_id);
        $stmt->bindParam(':bot_id', $bot_id);
        $stmt->bindParam(':pair', $pair);
        $stmt->bindParam(':message', $message);
        $stmt->execute();
    }
    catch (PDOExecption $e){
        echo $e->getMessage();
    }   
}

function json_clean_decode($json, $assoc = false, $depth = 512, $options = 0) {

    // search and remove comments like /* */ and //
    $json = preg_replace("#(/\*([^*]|[\r\n]|(\*+([^*/]|[\r\n])))*\*+/)|([\s\t]//.*)|(^//.*)#", '', $json);

    if(version_compare(phpversion(), '5.4.0', '>=')) { 
        return json_decode($json, $assoc, $depth, $options);
    } elseif(version_compare(phpversion(), '5.3.0', '>=')) { 
        return json_decode($json, $assoc, $depth);
    } else {
        return json_decode($json, $assoc);
    }
}

function json_cleaner($json, $assoc = false, $depth = 512, $options = 0) {

    // search and remove comments like /* */ and //
    $json = preg_replace("#(/\*([^*]|[\r\n]|(\*+([^*/]|[\r\n])))*\*+/)|([\s\t]//.*)|(^//.*)#", '', $json);
    
    return $json;

}



// Create a dropdown with an start and end for use with integers
function create_dropdown_number($start , $end , $name , $class , $id , $current_value , $step_size = '1') {

    $html = '<select name="'.$name.'"  class="'.$class.'">';

    

    for($x = $start; $x <= $end; $x += $step_size) {

        $value = floor($x * 100) / 100;
        $selected = ( $value == $current_value) ? 'selected' : '';
        $html.= '<option value="'.$value.'" '.$selected.'>'.$value.'</option>';
    }

    $html.= '</select>';

    return $html;
}

// Create a dropdown with an start and end for use with integers
function create_dropdown_number_with_id($start , $end , $name , $class , $id , $current_value , $step_size = '1') {

    $html = '<select name="'.$name.'" id="'.$id.'" class="'.$class.'">';

    

    for($x = $start; $x <= $end; $x += $step_size) {

        $value = floor($x * 100) / 100;
        $selected = ( $value == $current_value) ? 'selected' : '';
        $html.= '<option value="'.$value.'" '.$selected.'>'.$value.'</option>';
    }

    $html.= '</select>';

    return $html;
}

function create_input_number($name , $class , $id , $current_value) {

    $html = '<input name="'.$name.'"  size="4" class="'.$class.' input_number" value="'.$current_value.'" />';


    return $html;
}

function create_input_float($name , $class , $id , $current_value) {

    $html = '<input name="'.$name.'"  size="4"  class="'.$class.' input_float" value="'.$current_value.'" />';


    return $html;
}

// Create a dropdown with an array for options
function create_dropdown_options($options , $name , $class , $id , $current_value , $use_different_key = false) {

    $html = '<select name="'.$name.'" id="'.$id.'"  class="'.$class.'">';

    foreach($options as $key => $opt) {
        if (!$use_different_key) {
            $selected = ($opt == $current_value) ? 'selected' : '';
            $html.= '<option '.$class.' value="'.$opt.'" '.$selected.'>'.$opt.'</option>';
        } else {
            $selected = ($key == $current_value) ? 'selected' : '';
            $html.= '<option '.$class.' value="'.$key.'" '.$selected.'>'.$opt.'</option>';
        }

    }

    $html.= '</select>';

    return $html;
}

function check_credentials($user_id) {
    // Terminate if the user is nog logged in
    if ($user_id != $_SESSION['user_id']) {
        echo 'ERROR_NOT_LOGGED_IN';
        die;
    }
}

function telegram($telegram_bot_id , $telegram_chat_id , $msg) {
    global $telegrambot,$telegramchatid;

    $url='https://api.telegram.org/bot'.$telegram_bot_id.'/sendMessage';$data=array('chat_id'=>$telegram_chat_id,'text'=>$msg);

    $options=array('http'=>array('method'=>'POST','header'=>"Content-Type:application/x-www-form-urlencoded\r\n",'content'=>http_build_query($data),),);

    $context=stream_context_create($options);

    $result=file_get_contents($url,false,$context);
    
    return $result;
}

function pr($data) {
    echo '<pre>';
    var_dump($data);
    echo '</pre>';
}

// Create an bot card
function create_bot_card($response , $bybit) {

    $wallet_balance = $bybit->wallet_info()['result']['USDT']['wallet_balance'];

    $active_color = $response['active'] == 1 ? 'border-success' : 'border-danger';

    if ($response['mad_direction'] == 'both') {
        $direction = '<span class="green">Long</span> + <span class="red">Short</span>';
    } elseif ($response['mad_direction'] == 'long_only') {
        $direction = '<span class="green">Long</span>';
    } elseif ($response['mad_direction'] == 'short_only') {
        $direction = '<span class="red">Short</span>';
    } 

    $code = '
    <div class="col">
        <div class="card text-white '.$active_color.' bg-dark mb-3">
            <div class="card-header"><strong>'.$response['3c_id'].'</strong></div>
            <div class="card-body">
            <h2>'.$response['internal_name'].'</h2>

            <ul class="list-group list-group-flush bg-dark">
                <li class="list-group-item bg-dark">Direction : '.$direction.'</li>
                <li class="list-group-item bg-dark">Max deals : '.create_dropdown_number_with_id(0 , 20 , 'mad_dropdown' , 'mad_dropdown' , 'account_'.$response['internal_id'] , $response['mad']).'</li>
                <li class="list-group-item bg-dark">IPB : '.create_dropdown_number_with_id(1 , 2000 , 'bo_size' , 'bo_size' , 'account_'.$response['internal_id'] , $response['bo_size'] , 1).' </li>
                <li class="list-group-item bg-dark">
                    <a class="logbook_link" id="account_'.$response['internal_id'].'"><i class="fas fa-book"></i>  Logbook</a> | <a class="advanced_settings_link" id="account_'.$response['internal_id'].'"> <i class="fas fa-cog"></i> Advanced settings</a>
                </li>
            </ul>

            </div>
        </div>
    </div>';

    echo $code;
}
