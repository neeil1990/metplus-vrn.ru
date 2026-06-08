<?php
define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
define("NEED_AUTH", false);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

$service_key = 'service_code';
$iblockId = isset($_GET['iblock_id']) ? (int)$_GET['iblock_id'] : 0;
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$options = getProductCuttingServices($iblockId, $productId);
?>

<div class="message">
    <h2>Выберите услугу</h2>
    <select id="<?=$service_key?>" style="width: 100%;padding: 5px 10px;margin-bottom:15px;">
        <?php foreach ($options as $option): ?>
            <option value="<?=$option['CODE']?>">
                <?=$option['NAME']?> <?=$option['VALUE'] ? ' - ' . $option['VALUE'] : ''?>
            </option>
        <?php endforeach; ?>
    </select>
    <a href="javascript:void(0)" class="product-item_cart-btn main-btn">
        <span class="glipf-cart"></span> Добавить в корзину
    </a>
</div>
