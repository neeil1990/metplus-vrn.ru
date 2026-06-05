<?php
define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
define("NEED_AUTH", false);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Loader;
use Bitrix\Catalog\Product\Basket;
use Bitrix\Main\Context;

$GLOBALS['APPLICATION']->RestartBuffer();
header('Content-Type: application/json; charset=utf-8');

if (!Loader::includeModule('catalog') || !Loader::includeModule('sale')) {
    echo json_encode(['success' => false, 'error' => 'Модули не подключены']);
    die();
}

$iblockId = isset($_GET['iblock_id']) ? (int)$_GET['iblock_id'] : 0;
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$quantity = isset($_GET['quantity']) ? floatval(str_replace(',', '.', $_GET['quantity'])) : 1.0;

if ($productId <= 0 || $quantity <= 0 || $iblockId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Неверные параметры товара']);
    die();
}

$fields = [
    'PRODUCT_ID' => $productId,
    'QUANTITY'   => $quantity,
    'PROPS' => [],
];

$serviceCode = $_GET['service_code'];

if ($serviceCode) {
    $services = getProp($iblockId, $productId, $serviceCode);

    $fields['PROPS'][] = [
        'NAME' => $services['NAME'],
        'CODE' => 'SERVICE',
        'VALUE' => $services['VALUE']
    ];
}

$result = Basket::addProduct($fields);

if ($result->isSuccess()) {
    echo json_encode([
        'success' => true,
        'message' => 'Товар успешно добавлен в количестве ' . $quantity
    ]);
} else {
    $errors = $result->getErrorMessages();
    echo json_encode([
        'success' => false,
        'error' => implode(', ', $errors)
    ]);
}

die();