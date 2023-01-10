<?php
include_once('./_common.php');
?>
<script>
console.log('test11');
if (typeof notification !== 'undefined') {
    console.log('test22');
    notification.postMessage('<?php echo json_encode($_POST) ?>');
}
</script>