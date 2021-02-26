<?php

/**
 * Created by PhpStorm.
 * User: jstormes
 * Date: 2/24/2021
 * Time: 2:57 PM
 */


namespace JStormes\AWSwrapper;


class CloudWatch
{
    private $config;

    function __construct($config) {

        $this->config= $config;

    }

    // LazyLoad Singleton for Logs
    function  log() : Logs {

        if (!isset($this->logs)) {
            $this->logs = new Logs($this->config);
        }

        return $this->logs;
    }

}