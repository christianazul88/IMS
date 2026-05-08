<?php

session_start();

$audit_id = $_SESSION['audit_id'];
$selected_area = $_SESSION['selected_area'];

$json_file = "../audit_json/" . $audit_id . "-" . $selected_area . ".json";

$barcodes = [];

if(file_exists($json_file)){

    $data = json_decode(file_get_contents($json_file), true);

    if(is_array($data)){
        $barcodes = array_reverse($data);
    }

}

?>

<style>
.receipt-box{
    background:#fff;
    border:1px dashed #999;
    padding:15px;
    font-family:monospace;
    max-height:500px;
    overflow-y:auto;
}

.receipt-title{
    text-align:center;
    font-size:18px;
    font-weight:bold;
}

.receipt-line{
    border-top:1px dashed #999;
    margin:10px 0;
}

.receipt-item{
    display:flex;
    justify-content:space-between;
    margin-bottom:5px;
    font-size:14px;
}

.receipt-footer{
    text-align:center;
    margin-top:10px;
    font-size:12px;
    color:#666;
}
</style>

<div class="receipt-box">

    <div class="receipt-title">
        AUDIT RECEIPT
    </div>

    <div class="text-center mb-2">
        Area: <?php echo htmlspecialchars($selected_area); ?>
    </div>

    <div class="receipt-line"></div>

    <?php if(empty($barcodes)): ?>

        <div class="text-center text-muted">
            No scanned items yet.
        </div>

    <?php else: ?>

        <?php foreach($barcodes as $index => $code): ?>

            <div class="receipt-item">
                <span><?php echo count($barcodes) - $index; ?>.</span>
                <span><?php echo htmlspecialchars($code); ?></span>
            </div>

        <?php endforeach; ?>

    <?php endif; ?>

    <div class="receipt-line"></div>

    <div class="receipt-item">
        <strong>TOTAL</strong>
        <strong><?php echo count($barcodes); ?></strong>
    </div>

    <div class="receipt-footer">
        Live Audit Monitoring
    </div>

</div>