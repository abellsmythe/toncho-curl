#!/usr/bin/php
<?php

require_once('src/tonchoCurl.php');

$cURL = new tonchoCurl(10);

$urls 		= [
	'https://google.com',
  'https://youtube.com',
	'https://gmail.com',
  'https://wesbos.com',
  'https://facebook.com',
  'https://twitter.com',
  'https://instagram.com',
  'https://amazon.com',
  'https://ebay.com',
  'https://reddit.com',
];

function callback($response, $url, $info, $user_data, $time) {
	echo "URL: {$url} Time: {$time}" . PHP_EOL;
}

$cURL->setCallback('callback');

foreach ($urls as $url) {
  $cURL->addRequest($url);
}

$cURL->execute();

?>