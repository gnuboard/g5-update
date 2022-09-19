<?php
include_once './_common.php';
include_once '../settle_toss.inc.php';

$receipt_url = '';
$od_tno = isset($_GET['od_tno']) ? clean_xss_tags($_GET['od_tno']) : "";

$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => "https://api.tosspayments.com/v1/payments/" . $od_tno,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "GET",
  CURLOPT_HTTPHEADER => array(
    "Authorization: Basic " . $credential
  ),
));

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);


if ($err) {
    alert($err);
} else {
    $responseJson = json_decode($response);

    if (isset($responseJson->code)) {
        alert($responseJson->message);
    }

    $receipt_url = $responseJson->receipt->url;
}
?>
<script>
location.replace('<?php echo $receipt_url; ?>');
</script>


