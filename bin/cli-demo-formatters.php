#!/usr/bin/env php
<?php

require_once dirname(__FILE__)."/../vendor/autoload.php";

use JStormes\AWSwrapper\CloudWatch;
use JStormes\AWSwrapper\Logs;

$addressFormatter = new \JStormes\AWSwrapper\AddressFormatterExample();
$loginFormatter = new \JStormes\AWSwrapper\LoginFormatExample();

$Log = new Logs([
    'profile' => 'default',
    'region' => 'us-west-2',
    'version' => 'latest',
    'logGroup' => "testGroup3",
    'logStreamPrefix' => "testStream3",
    'system' => 'system',
    'application' => str_replace('.php','',basename(__FILE__)),
    'formatters' => [$addressFormatter, $loginFormatter]
]);


$Log->debug('This is a debug msg');


$badAddress = [
    'address' => '123 Any Street',
    'city' => 'Paris',
    'state' => 'TX',
    'zip' => '12345'
];
$Log->info("Bad zip code", $badAddress);


$Log->info('This is a generic info message.');


$badLogin = [
    'user' => 'someUser',
    'ip_address' => '127.0.0.1'
];
$Log->login("Bad Login attempt", $badLogin);
