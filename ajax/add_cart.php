<?php
define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
define("NEED_AUTH", false);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Loader;
use Bitrix\Catalog\Product\Basket;
use Bitrix\Main\Context;

if (!Loader::includeModule('catalog') || !Loader::includeModule('sale')) {
    $GLOBALS['APPLICATION']->RestartBuffer();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => 'Модули не подключены']);
    die();
}

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$quantity = isset($_GET['quantity']) ? floatval(str_replace(',', '.', $_GET['quantity'])) : 1.0;

if ($productId <= 0 || $quantity <= 0) {
    $GLOBALS['APPLICATION']->RestartBuffer();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => 'Неверные параметры товара']);
    die();
}

$fields = [
    'PRODUCT_ID' => $productId,
    'QUANTITY'   => $quantity,
];

$result = Basket::addProduct($fields);

if ($result->isSuccess()) {
    $GLOBALS['APPLICATION']->RestartBuffer();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => true,
        'message' => 'Товар успешно добавлен в количестве ' . $quantity
    ]);
} else {
    $errors = $result->getErrorMessages();
    $GLOBALS['APPLICATION']->RestartBuffer();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'error' => implode(', ', $errors)
    ]);
}