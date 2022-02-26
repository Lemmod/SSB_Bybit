<?php

class BybitConnector
{

    protected $api_key = '';
    protected $api_secret = '';
    protected $base_url = 'https://api.bybit.com/'; // Endpoint for binance futures , differs from SPOT API URL

    public function __construct($api_key , $api_secret) {

        if(empty($api_key)) {
            throw new \Exception("API Key not set");
        }

        if(empty($api_secret)) {
            throw new \Exception("API Secret not set");
        }

        $this->api_key = $api_key;
        $this->api_secret = $api_secret;
    }

    protected function request_info($url , $params = [] , $method = "GET") {


        $timestamp = (microtime(true) * 1000);
        $params['api_key'] = $this->api_key;
        $params['timestamp'] = number_format($timestamp, 0, '.', '');

        ksort($params); // Paramaters need to be sorted in alphabetical order

        $query = http_build_query($params, '', '&');
        $signature = hash_hmac('sha256', urldecode(http_build_query($params)) , $this->api_secret);

        $endpoint = $this->base_url . $url . '?' . $query . '&sign=' . $signature;

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $endpoint);
        if($method == "GET") {
            curl_setopt($curl, CURLOPT_POST, false);
        }
        if($method == "POST") {
            curl_setopt($curl, CURLOPT_POST, true);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);

        $output = curl_exec($curl);

        //$header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        //$output = substr($output, $header_size);
        
        curl_close($curl);
        
        $json = json_decode($output, true);
    

        return $json;

    }

    /**
     * Get the account info , contains all the info we need to get the required information
     */
    public function wallet_info() {
        return $this->request_info("v2/private/wallet/balance");
    }

    /**
     * Get the account info , contains all the info we need to get the required information
     */
    public function get_positions() {
        return $this->request_info('private/linear/position/list' , $params);
    }

    /**
     * Set the leverage
     */
    public function set_leverage($params) {
        return $this->request_info('private/linear/position/switch-isolated' , $params , "POST");
    }
    
    /**
     * Set leverage size
     */

    public function set_leverage_size($params) {
        return $this->request_info('private/linear/position/set-leverage' , $params , "POST");
    }
    
    /**
     * Get the lastes price of the symbol
     */
    public function get_symbol_value($symbol) {
        return $this->request_info('public/linear/recent-trading-records' , ['symbol' => $symbol , 'limit' => 1]);
    }
   
    /**
     * Get all the symbols
     */
    public function get_symbols() {
        return $this->request_info('v2/public/symbols');
    }

    /**
     * Create order
     */
    public function create_order($params) {
        return $this->request_info('private/linear/order/create' , $params , "POST");
    }

    /**
     * Update position
     */
    public function update_position($params) {
        return $this->request_info('private/linear/position/trading-stop' , $params , "POST");
    }


    /**
     * Calculate the clean order size to parse to Bybit
     */

     public function calculate_clean_order_size($symbols , $pair , $order_size) {

         foreach($symbols['result'] as $symbol) {
            if ($symbol['name'] == $pair) {

                $min_trading_qty = $symbol['lot_size_filter']['min_trading_qty'];
                $qty_step = $symbol['lot_size_filter']['qty_step'];
                
                $notional = strlen(substr(strrchr($qty_step, "."), 1));

                if ($order_size < $min_trading_qty) {
                    $clean_order_size = $min_trading_qty;                   
                } else {
                    $clean_order_size = round( $order_size , $notional);
                }
            }
         }

         return $clean_order_size;
     }

    /**
     * Calculate the clean order size to parse to Bybit
     */

    public function calculate_clean_close_amount($symbols , $pair , $amount) {

        foreach($symbols['result'] as $symbol) {
           if ($symbol['name'] == $pair) {

               $min_trading_qty = $symbol['lot_size_filter']['min_trading_qty'];
               $qty_step = $symbol['lot_size_filter']['qty_step'];
               
               $notional = strlen(substr(strrchr($qty_step, "."), 1));

               if ($amount < $min_trading_qty) {
                   $clean_amount = $min_trading_qty;                   
               } else {
                   $clean_amount = round( $amount , $notional);
               }
           }
        }

        return $clean_amount;
    }

     /**
     * Create an overview of total open positions
     */
    public function result_open_positions($open_positions) {

        $result = array();
        $i = 0;

        foreach ($open_positions['result'] as $position) {
           
            if ($position['data']['size'] > 0) {

                $result[$i] = array( 
                    'symbol' => $position['data']['symbol'],
                    'side' => $position['data']['side'],
                    'size' => $position['data']['size'],
                    'entry_price' => $position['data']['entry_price']);
                $i++;
            }
        }

        return $result;
    }

    /**
     * Get the current position for the correct side
     */
    public function get_open_position($open_positions , $symbol , $side) {

        $deals = $this->result_open_positions($open_positions);

        foreach ($deals as $deal) {
            if ($deal['side'] == $side && $deal['symbol'] == $symbol) {
                return $deal;
            }
        }      

    }
}
