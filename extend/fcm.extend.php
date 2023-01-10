<?php

if (!defined('_GNUBOARD_')) {
    exit;
}

// FCM API Key
if(is_file(G5_PATH . '/fcmconfig.php')) {
    include(G5_PATH . '/fcmconfig.php');
} else {
    define('API_ACCESS_KEY', '');
}

function set_notification_data($token, $title, $body)
{
    $notification = array(
        'title' => $title,
        'body' => $body
    );

    $fcm_notification = array(
        'to' => $token,
        'notification' => $notification
    );

    return $fcm_notification;
}

function send_fcm($fcm_data)
{
    $headers = array(
        'Authorization: key=' . API_ACCESS_KEY,
        'Content-Type: application/json'
    );

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fcm_data));
    $result = curl_exec($ch);
    curl_close($ch);

    return $result;
}