<?php
/**
 * @var array $mailConfig
 * @var array $allowedKeys
 */
// load config.php
if(!file_exists(__DIR__.'/config.php')){
    die('config.php not found. Please create from config.sample.php');
}
require_once __DIR__.'/config.php';


require_once __DIR__.'/app/Post2Email.php';
$p2e = new Post2Email($mailConfig, $allowedKeys);