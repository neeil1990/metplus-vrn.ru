<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");


if (\Bitrix\Main\Loader::includeModule('catalog')) {

    $productId = 4615823;
    $measureId = 1;
    $ratioValue = 0.1;

    // 3. Обновляем поле в базе данных
    $result = \Bitrix\Catalog\ProductTable::update($productId, [
        'MEASURE' => $measureId
    ]);

    if ($result->isSuccess()) {

        $ratioRow = \Bitrix\Catalog\MeasureRatioTable::getList([
            'select' => ['ID'],
            'filter' => ['=PRODUCT_ID' => $productId],
            'limit' => 1
        ])->fetch();

        if ($ratioRow) {
            $ratioResult = \Bitrix\Catalog\MeasureRatioTable::update($ratioRow['ID'], [
                'RATIO' => $ratioValue
            ]);
        } else {
            $ratioResult = \Bitrix\Catalog\MeasureRatioTable::add([
                'PRODUCT_ID' => $productId,
                'RATIO' => $ratioValue,
                'IS_DEFAULT' => 'Y'
            ]);
        }
    }

}
?>

<?php require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>