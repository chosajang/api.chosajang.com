<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$config['mailtype'] = 'html';
$config['protocol'] = 'smtp';

$config['smtp_host'] = 'smtp.gmail.com';
$config['smtp_user'] = 'cgwork2019@gmail.com';
$config['smtp_pass'] = 'C@coaDev0317';
$config['smtp_port'] = 465;
$config['smtp_timeout'] = 10;
$config['smtp_crypto'] = 'ssl';

// $config['mailpath'] = '/usr/sbin/sendmail';
$config['charset'] = 'UTF-8';
$config['wordwrap'] = TRUE;
$config['validate'] = TRUE;
