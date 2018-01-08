<?php

// This class is designed to run multiple cURL requests in parallel, rather than waiting for each one 
// to finish before starting the next.
//
// First create the tonchoCurl object:
//
// $cURL = new tonchoCurl(10);
//
// The first argument to the constructor is the maximum number of outstanding fetches to allow
// before blocking to wait for one to finish. You can change this later using setMaxConcurrent()
// The second optional argument is an array of cURL options
//
// Second at this point you can call some methods 
// 
// $cURL->setMaxConcurrent($max_requests)      Modify the Max Concurrent Requests
// $cURL->setOptions($options)                 Set Options General to the Requests
// $cURL->setHeaders($headers)                 Set Headers General to the Requests
// $cURL->setCallback($callback)               Define a General Callback to the Requests
// $cURL->setTimeout($timeout)                 Define a General Timeout

// Third add a Request:
//
// function callback($response, $url, $info, $user_data, $time) {
//    echo "URL: {$url} Time: {$time}" . PHP_EOL;
// }
//
// $cURL->addRequest($url);
// 
// Or you could use extra parameters
//
// $cURL->addRequest($url, 'callback', $post_data, $user_data, $options, $headers);
//
// The first argument is the address that should be fetched
// The second argument is the optional callback function that will be run once the request is done
// The third argument is a the optional post data in case that you want a POST
// Finaly the two last arguments are the optional custom options and headers to the request
//

// At last you need to execute the Requests
//
// $cURL->execute();
//
// Then the callback it's executed when the request it's done and take five arguments. 
// The first is a string containing the content found at the URL. 
// The second is the original URL requested. 
// The third is the cURL info curl_getinfo() with a couple extras.
// The fourth is the user data
// The fifth is the time that took the request
//
// By Alton Bell Smythe, freely reusable.

Class tonchoCurl {

    /**
     * cURL Data
     */
    private $_curl_version;
    private $_maxConcurrent = 0;    //max. number of simultaneous connections allowed
    private $_options       = [];   //shared cURL options
    private $_headers       = [];   //shared cURL request headers
    private $_callback      = null; //default callback
    private $_timeout       = 5000; //all requests must be completed by this time
    public  $requests       = [];   //request_queue

    /**
     * cURL Messages
     */
    private $__curl_msgs = array(
        CURLE_OK                            => 'OK',
        CURLE_UNSUPPORTED_PROTOCOL          => 'UNSUPPORTED_PROTOCOL',
        CURLE_FAILED_INIT                   => 'FAILED_INIT',
        CURLE_URL_MALFORMAT                 => 'URL_MALFORMAT',
        CURLE_URL_MALFORMAT_USER            => 'URL_MALFORMAT_USER',
        CURLE_COULDNT_RESOLVE_PROXY         => 'COULDNT_RESOLVE_PROXY',
        CURLE_COULDNT_RESOLVE_HOST          => 'COULDNT_RESOLVE_HOST',
        CURLE_COULDNT_CONNECT               => 'COULDNT_CONNECT',
        CURLE_FTP_WEIRD_SERVER_REPLY        => 'FTP_WEIRD_SERVER_REPLY',
        CURLE_FTP_ACCESS_DENIED             => 'FTP_ACCESS_DENIED',
        CURLE_FTP_USER_PASSWORD_INCORRECT   => 'FTP_USER_PASSWORD_INCORRECT',
        CURLE_FTP_WEIRD_PASS_REPLY          => 'FTP_WEIRD_PASS_REPLY',
        CURLE_FTP_WEIRD_USER_REPLY          => 'FTP_WEIRD_USER_REPLY',
        CURLE_FTP_WEIRD_PASV_REPLY          => 'FTP_WEIRD_PASV_REPLY',
        CURLE_FTP_WEIRD_227_FORMAT          => 'FTP_WEIRD_227_FORMAT',
        CURLE_FTP_CANT_GET_HOST             => 'FTP_CANT_GET_HOST',
        CURLE_FTP_CANT_RECONNECT            => 'FTP_CANT_RECONNECT',
        CURLE_FTP_COULDNT_SET_BINARY        => 'FTP_COULDNT_SET_BINARY',
        CURLE_PARTIAL_FILE                  => 'PARTIAL_FILE',
        CURLE_FTP_COULDNT_RETR_FILE         => 'FTP_COULDNT_RETR_FILE',
        CURLE_FTP_WRITE_ERROR               => 'FTP_WRITE_ERROR',
        CURLE_FTP_QUOTE_ERROR               => 'FTP_QUOTE_ERROR',
        CURLE_HTTP_NOT_FOUND                => 'HTTP_NOT_FOUND',
        CURLE_WRITE_ERROR                   => 'WRITE_ERROR',
        CURLE_MALFORMAT_USER                => 'MALFORMAT_USER',
        CURLE_FTP_COULDNT_STOR_FILE         => 'FTP_COULDNT_STOR_FILE',
        CURLE_READ_ERROR                    => 'READ_ERROR',
        CURLE_OUT_OF_MEMORY                 => 'OUT_OF_MEMORY',
        CURLE_OPERATION_TIMEOUTED           => 'OPERATION_TIMEOUTED',
        CURLE_FTP_COULDNT_SET_ASCII         => 'FTP_COULDNT_SET_ASCII',
        CURLE_FTP_PORT_FAILED               => 'FTP_PORT_FAILED',
        CURLE_FTP_COULDNT_USE_REST          => 'FTP_COULDNT_USE_REST',
        CURLE_FTP_COULDNT_GET_SIZE          => 'FTP_COULDNT_GET_SIZE',
        CURLE_HTTP_RANGE_ERROR              => 'HTTP_RANGE_ERROR',
        CURLE_HTTP_POST_ERROR               => 'HTTP_POST_ERROR',
        CURLE_SSL_CONNECT_ERROR             => 'SSL_CONNECT_ERROR',
        CURLE_FTP_BAD_DOWNLOAD_RESUME       => 'FTP_BAD_DOWNLOAD_RESUME',
        CURLE_FILE_COULDNT_READ_FILE        => 'FILE_COULDNT_READ_FILE',
        CURLE_LDAP_CANNOT_BIND              => 'LDAP_CANNOT_BIND',
        CURLE_LDAP_SEARCH_FAILED            => 'LDAP_SEARCH_FAILED',
        CURLE_LIBRARY_NOT_FOUND             => 'LIBRARY_NOT_FOUND',
        CURLE_FUNCTION_NOT_FOUND            => 'FUNCTION_NOT_FOUND',
        CURLE_ABORTED_BY_CALLBACK           => 'ABORTED_BY_CALLBACK',
        CURLE_BAD_FUNCTION_ARGUMENT         => 'BAD_FUNCTION_ARGUMENT',
        CURLE_BAD_CALLING_ORDER             => 'BAD_CALLING_ORDER',
        CURLE_HTTP_PORT_FAILED              => 'HTTP_PORT_FAILED',
        CURLE_BAD_PASSWORD_ENTERED          => 'BAD_PASSWORD_ENTERED',
        CURLE_TOO_MANY_REDIRECTS            => 'TOO_MANY_REDIRECTS',
        CURLE_UNKNOWN_TELNET_OPTION         => 'UNKNOWN_TELNET_OPTION',
        CURLE_TELNET_OPTION_SYNTAX          => 'TELNET_OPTION_SYNTAX',
        CURLE_OBSOLETE                      => 'OBSOLETE',
        CURLE_SSL_PEER_CERTIFICATE          => 'SSL_PEER_CERTIFICATE',
        CURLE_GOT_NOTHING                   => 'GOT_NOTHING',
        CURLE_SSL_ENGINE_NOTFOUND           => 'SSL_ENGINE_NOTFOUND',
        CURLE_SSL_ENGINE_SETFAILED          => 'SSL_ENGINE_SETFAILED',
        CURLE_SEND_ERROR                    => 'SEND_ERROR',
        CURLE_RECV_ERROR                    => 'RECV_ERROR',
        CURLE_SHARE_IN_USE                  => 'SHARE_IN_USE',
        CURLE_SSL_CERTPROBLEM               => 'SSL_CERTPROBLEM',
        CURLE_SSL_CIPHER                    => 'SSL_CIPHER',
        CURLE_SSL_CACERT                    => 'SSL_CACERT',
        CURLE_BAD_CONTENT_ENCODING          => 'BAD_CONTENT_ENCODING',
        CURLE_LDAP_INVALID_URL              => 'LDAP_INVALID_URL',
        CURLE_FILESIZE_EXCEEDED             => 'FILESIZE_EXCEEDED',
        CURLE_FTP_SSL_FAILED                => 'FTP_SSL_FAILED',
        CURLE_SSH                           => 'SSH',
    );

    /**
     * cURL Errors
     */
    private   $__error = 'cURL Error: Could not execute the cURL, verify if is correctly configured';

    /**
     * cURL Constructor
     *
     * @param   integer   $max_concurrent
     *
     * @return  void
     */
    public function __construct(int $max_concurrent = 10, array $options = []) {
        $this->setMaxConcurrent($max_concurrent);
        $this->setOptions($options);
        $this->_curl_version = curl_version()['version'];
    }

    /**
     * cURL Destructor
     *
     * @return void
     */
    public function __destruct()
    {
        // Destruct tonchoCurl Instance
    }

    /**
     * Verify PHP Version
     *
     * @return boolean 
     */
    private function verifyPHPVersion($version = '7.1.0', $match = '>=')
    {
        return version_compare(phpversion(), $version, $match) ? true : false;
    }

    /**
     * Set Max Concurrent cURL Requests
     *
     * @param    integer     $max_requests
     *
     * @return   void
     */
    public function setMaxConcurrent(int $max_requests) {
        if($max_requests > 0) {
            $this->_maxConcurrent = $max_requests;
        }
    }

    /**
     * Set Options to cURL Requests
     *
     * @param    array     $options
     *
     * @return   void
     */
    public function setOptions(array $options) {
        $this->_options = $options;
    }

    /**
     * Set Headers to cURL Requests
     *
     * @param    array     $headers
     *
     * @return   void
     */
    public function setHeaders(array $headers) {
        if(is_array($headers) && count($headers)) {
            $this->_headers = $headers;
        }
    }

    /**
     * Set Call Back to cURL request
     *
     * @param    callable     $callback
     *
     * @return   void
     */
    public function setCallback(callable $callback) {
        $this->_callback = $callback;
    }

    /**
     * Set Timeout to cURL request in milliseconds
     *
     * @param    integer     $timeout
     *
     * @return   void
     */
    public function setTimeout(int $timeout) {
        if($timeout > 0) {
            $this->_timeout = $timeout;
        }
    }

    /**
     * Get cURL Multi Handler Errors
     *
     * @param    object  $multi_handle
     *
     * @return   void
     */
    private function multiErrors($multi_handle) {
        // Verify Version
        if($this->verifyPHPVersion()){
            // cURL Error Number
            if($errno = curl_multi_errno($multi_handle)) {
                // cURL Error Message
                $error_message = curl_multi_strerror($errno);
                throw new Exception($this->__error . PHP_EOL . "cURL Error ({$errno}): {$error_message}");
            }
        }
    }

    /**
     * Get cURL Handler Errors
     *
     * @param    object  $handle
     *
     * @return   void
     */
    private function singleErrors($handle) {
        // cURL Error Number
        if($errno = curl_errno($handle)) {
            // cURL Error Message
            $error_message = curl_strerror($errno);
            throw new Exception($this->__error . PHP_EOL . "cURL Error ({$errno}): {$error_message}");
        }
    }

    /**
     * Add a cURL request to the request queue
     *
     * @param    string     $url
     * @param    callable   $callback
     * @param    array      $post_data
     * @param    array      $user_data
     * @param    array      $options
     * @param    array      $headers
     *
     * @return   integer    $request_index
     */
    public function addRequest($url, callable $callback = null, array $post_data = null, array $user_data = null, array $options = null, array $headers = null) {
	     //Add to request queue
        $this->requests[] = [
            'url'       => $url,
            'post_data' => ($post_data) ? $post_data : null,
            'callback'  => ($callback) ? $callback : $this->_callback,
            'user_data' => ($user_data) ? $user_data : null,
            'options'   => ($options) ? $options : null,
            'headers'   => ($headers) ? $headers : null
        ];
        $request_index = count($this->requests) - 1;
        // Return
        return $request_index;
    }

    /**
     * Reset cURL request queue
     *
     * @return   void
     */
    public function reset() {
        $this->requests = [];
    }

    /**
     * Normalize the Headers
     *
     * @param   array   $headers
     *
     * @return   void
     */
    private function normalize_headers(array $headers) {
        $normalized = [];
        foreach($headers as $key => $header) {
            if(is_string($key)) {
                $normal = "$key: $header";
            } else {
                $header;
            }
            $normalized = [];
        }
    }

    /**
     * Execute the cURL request queue
     *
     * @return   void
     */
    public function execute() {
        //the request map that maps the request queue to request curl handles
        $requests_map     = [];
        $multi_handle     = curl_multi_init();
        $num_outstanding  = 0;
        //start processing the initial request queue
        $num_initial_requests = min($this->_maxConcurrent, count($this->requests));
        for($i = 0; $i < $num_initial_requests; $i++) {
            $this->init_request($i, $multi_handle, $requests_map);
            $num_outstanding++;
        }
        do{
            do{
                $mh_status = curl_multi_exec($multi_handle, $active);
            } while($mh_status == CURLM_CALL_MULTI_PERFORM);
            if($mh_status != CURLM_OK) {
                break;
            }
            //a request is just completed, find out which one
            while($completed = curl_multi_info_read($multi_handle)) {
                $this->process_request($completed, $multi_handle, $requests_map);
                $num_outstanding--;
                //try to add/start a new requests to the request queue
                while(
                    $num_outstanding < $this->_maxConcurrent && //under the limit
                    $i < count($this->requests) && isset($this->requests[$i]) // requests left
                ) {
                    $this->init_request($i, $multi_handle, $requests_map);
                    $num_outstanding++;
                    $i++;
                }
            }
            usleep(15); //save CPU cycles, prevent continuous checking
        } while ($active || count($requests_map)); //End do-while
        // Handle Errors
        $this->multiErrors($multi_handle);
        // Reset
        $this->reset();
        curl_multi_close($multi_handle);
    }

    //Build individual cURL options for a request
    private function buildOptions(array $request) {
        $url                = $request['url'];
        $post_data          = $request['post_data'];
        $individual_opts    = $request['options'];
        $individual_headers = $request['headers'];

        $options = ($individual_opts) ? $individual_opts + $this->_options : $this->_options; //merge shared and individual request options
        $headers = ($individual_headers) ? $individual_headers + $this->_headers : $this->_headers; //merge shared and individual request headers

        //the below will overide the corresponding default or individual options
        $options[CURLOPT_RETURNTRANSFER]  = true;
        $options[CURLOPT_NOSIGNAL]        = 1;

        if(version_compare($this->_curl_version, '7.16.2') >= 0) {
            $options[CURLOPT_CONNECTTIMEOUT_MS] = $this->_timeout;
            $options[CURLOPT_TIMEOUT_MS]        = $this->_timeout;
            // Clear
            unset($options[CURLOPT_CONNECTTIMEOUT]);
            unset($options[CURLOPT_TIMEOUT]);
        } else {
            $options[CURLOPT_CONNECTTIMEOUT]  = round($this->_timeout / 1000);
            $options[CURLOPT_TIMEOUT]         = round($this->_timeout / 1000);
            // Clear
            unset($options[CURLOPT_CONNECTTIMEOUT_MS]);
            unset($options[CURLOPT_TIMEOUT_MS]);
        }

        if($url) {
            $options[CURLOPT_URL] = $url;
        }

        if($headers) {
            $options[CURLOPT_HTTPHEADER] = $headers;
        }

        // enable POST method and set POST parameters
        if(!is_null($post_data)) {
            $options[CURLOPT_POST]       = 1;
            $options[CURLOPT_POSTFIELDS] = is_array($post_data)? http_build_query($post_data) : $post_data;
        }
        return $options;
    }

    private function init_request($request_num, $multi_handle, &$requests_map) {
        $request =& $this->requests[$request_num];
        $this->addTimer($request);

        $ch                     = curl_init();
        $options                = $this->buildOptions($request);
        //merged options
        $request['options_set'] = $options;
        $opts_set               = curl_setopt_array($ch, $options);
        if(!$opts_set) {
            echo 'options not set' . PHP_EOL;
            exit;
        }
        curl_multi_add_handle($multi_handle, $ch);

        //add curl handle of a new request to the request map
        $ch_hash = (string) $ch;
        $requests_map[$ch_hash] = $request_num;
    }

    private function process_request($completed, $multi_handle, array &$requests_map) {
        $ch      = $completed['handle'];
        $ch_hash = (string) $ch;
        $request =& $this->requests[$requests_map[$ch_hash]]; //map handler to request index to get request info

        $request_info              = curl_getinfo($ch);
        $request_info['curle']     = $completed['result'];
        $request_info['curle_msg'] = isset($this->curle_msgs[$completed['result']]) ? $this->curle_msgs[$completed['result']] : curl_strerror($completed['result']);
        $request_info['handle']    = $ch;
        $request_info['time']      = $time = $this->stopTimer($request); //record request time
        $request_info['url_raw']   = $url = $request['url'];
        $request_info['user_data'] = $user_data = $request['user_data'];

        // Handle Errors
        $this->singleErrors($ch);

        if(curl_errno($ch) !== 0) { //if server responded with http error
            $response = false;
        } else { //sucessful response
            $response = curl_multi_getcontent($ch);
        }

        //get request info
        $callback = $request['callback'];
        $options  = $request['options_set'];

        if($response && !empty($options[CURLOPT_HEADER])) {
            $k = intval($request_info['header_size']);
            $request_info['response_header'] = substr($response, 0, $k);
            $response = substr($response, $k);
        }

        //remove completed request and its curl handle
        unset($requests_map[$ch_hash]);
        curl_multi_remove_handle($multi_handle, $ch);
        curl_close($ch);

        //call the callback function and pass request info and user data to it
        if($callback) {
            call_user_func($callback, $response, $url, $request_info, $user_data, $time);
        }
        //free up memory now just incase response was large
        unset($ch, $ch_hash, $request, $request_info);
    }

    /**
     * Check for Timeout // NOT IN USE
     *
     * @param   object     $mh    // cURL Multi Handler
     *
     * @return  void
     */
    private function check_for_timeouts($mh) {
        $now      = microtime(true);
        $requests = $this->_requests;
        foreach($requests as $request) {
            $timeout    = $request->timeout;
            $start_time = $request->start_time;
            $ch         = $request->handle;
            if($now >=  $start_time + $timeout) {
                curl_multi_remove_handle($mh, $ch);
            }
        }
    }

    /**
     * Add Timer
     *
     * @see     init_request
     *
     * @param   array     $request
     *
     * @return  void
     */
    private function addTimer(array &$request) { //adds timer object to request
        $request['timer'] = microtime(true); //start time
        $request['time']  = false;           //default if not overridden by time later
    }

    /**
     * Stop Timer
     *
     * @see     process_request
     *
     * @param   array     $request
     *
     * @return  integer   $elapsed
     */
    private function stopTimer(array &$request) {
        $elapsed          = abs($request['timer'] - microtime(true));
        $request['time']  = $elapsed;
        // Clear
        unset($request['timer']);
        // Return
        return $elapsed;
    }
}

?>
