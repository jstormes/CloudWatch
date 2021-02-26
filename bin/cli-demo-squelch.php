#!/usr/bin/env php
<?php

require_once dirname(__FILE__)."/../vendor/autoload.php";

use JStormes\AWSwrapper\CloudWatch;
use JStormes\AWSwrapper\Logs;

/**********************************************************************************
 * Lazy Load Global object example, for use in legacy PHP applications.
 */
$LazyLoad = new CloudWatch([
    'profile' => 'default',
    'region' => 'us-west-2',
    'version' => 'latest',
    'logGroup' => "testGroup3",
    'logStreamPrefix' => "testStream3",
    'system' => 'system',
    'application' => str_replace('.php','',basename(__FILE__))
]);

$LazyLoad->log()->debug("This is an LazyLoad debug message");
$LazyLoad->log()->info("This is an LazyLoad info message");
$LazyLoad->log()->monitor("This is an LazyLoad monitor message");
$LazyLoad->log()->warning("This is an LazyLoad warning message");
$LazyLoad->log()->error("This is an LazyLoad error message");
$LazyLoad->log()->critical("This is an LazyLoad critical message");


/**********************************************************************************
 * Pre Load example for use in frameworks where the DI systems Lazy Loads
 */
$PreLoad = new Logs([
    'profile' => 'default',
    'region' => 'us-west-2',
    'version' => 'latest',
    'logGroup' => "testGroup3",
    'logStreamPrefix' => "testStream3",
    'system' => 'system',
    'application' => str_replace('.php','',basename(__FILE__))
]);

$PreLoad->debug("This is an PreLoad debug message");
$PreLoad->info("This is an PreLoad info message");
$PreLoad->monitor("This is an PreLoad monitor message");
$PreLoad->warning("This is an PreLoad  warning message");
$PreLoad->error("This is an PreLoad error message");
$PreLoad->critical("This is an PreLoad critical message");

// Dynamic severity demo.
$PreLoad->test("this is a test");