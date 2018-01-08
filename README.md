# TonchoCurl
----

This module provides an easy-to-use interface to allow you to run multiple CURL url fetches in parallel in PHP.
Is needed PHP 7 >= 7.1.0 for handle errors with curl_multi_errno

## Testing
To test it, go to the command line, cd to this folder and run

`php test.php`

This should run 10 request through some urls, showing the url and time. To see what sort of performance difference running parallel requests gets you, try altering the default of 10 requests and timing how long each takes.

The class is designed to make it easy to run multiple cURL requests in parallel, rather than waiting for each one to finish before starting the next.

## Usage

To use it, first copy `tonchoCurl.php` and include it, then create the `tonchoCurl` object:

```php 
$cURL = new tonchoCurl(10);
```

The first argument to the constructor is the maximum number of outstanding fetches to allow
before blocking to wait for one to finish. You can change this later using setMaxConcurrent()
The second optional argument is an array of cURL options
Second at this point you can call some methods 

```php
$cURL->setMaxConcurrent($max_requests)      // Modify the Max Concurrent Requests
$cURL->setOptions($options)                 // Set Options General to the Requests
$cURL->setHeaders($headers)                 // Set Headers General to the Requests
$cURL->setCallback($callback)               // Define a General Callback to the Requests
$cURL->setTimeout($timeout)                 // Define a General Timeout
```

Third add a Request:

You may define a callback exg.
```php
function callback($response, $url, $info, $user_data, $time) {
   echo "URL: {$url} Time: {$time}" . PHP_EOL;
}
```

```php
$cURL->addRequest($url);
```

Or you could use extra parameters

```php
$cURL->addRequest($url, 'callback', $post_data, $user_data, $options, $headers);
```

The first argument is the address that should be fetched
The second argument is the optional callback function that will be run once the request is done
The third argument is a the optional post data in case that you want a POST
Finaly the two last arguments are the optional custom options and headers to the request

At last you need to execute the Requests

```php
$cURL->execute();
```

Then the callback it's executed when the request it's done and take five arguments. 
The first is a string containing the content found at the URL. 
The second is the original URL requested. 
The third is the cURL info curl_getinfo() with a couple extras.
The fourth is the user data
The fifth is the time that took the request

## Credits

By Alton Bell Smythe, freely reusable.