<?php

use Gnuboard\Plugin\AwsS3\S3Admin;
use Gnuboard\Plugin\AwsS3\S3Service;

if (!defined('_GNUBOARD_')) {
    exit;
}

require_once (G5_PLUGIN_PATH . '/AwsS3/S3Service.php');
require_once (G5_PLUGIN_PATH .  '/AwsS3/S3Admin.php');
new S3Admin(S3Service::getInstance());