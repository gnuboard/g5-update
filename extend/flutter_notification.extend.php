<?php
add_event('write_update_after', 'notificate', 10, 5);

function notificate($board, $wr_id, $w, $qstr, $redirect_url)
{
    global $g5;

    $write_board = get_write($g5['write_prefix'] . $board['bo_table'], $wr_id);

    $data = array();
    $data['bo_subject'] = $board['bo_subject'];
    $data['wr_id'] = $wr_id;
    $data['wr_subject'] = $write_board['wr_subject'];

    // echo "<script>
    // console.log('test11');
    // if (typeof notification !== 'undefined') {
    //     console.log('test22');
    //     notification.postMessage('".json_encode($data)."');
    // }
    // </script>";
    
    // $headerData    = array("Content-Type: application/json", "charset=utf-8");

    // // API REQ
    // $ch = curl_init();
    // curl_setopt($ch, CURLOPT_URL, G5_BBS_URL . "/_flutter.php");
    // curl_setopt($ch, CURLOPT_HTTPHEADER, $headerData);
    // curl_setopt($ch, CURLOPT_POST, 1);
    // curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    // curl_setopt($ch, CURLOPT_TIMEOUT, 1);
    // curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
    // curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    // curl_setopt($ch, CURLOPT_POSTFIELDS, $data);       //POST data

    // // API RES
    // curl_exec($ch);
    // if(defined(CURLINFO_HTTP_CODE)){
    //     print_r(curl_getinfo($ch, CURLINFO_HTTP_CODE));
    // }
    // echo curl_error($ch);
    // curl_close($ch);
    // exit;
}

