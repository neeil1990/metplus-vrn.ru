<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

/**
 * @var CBitrixComponentTemplate $this
 * @var CatalogSectionComponent $component
 * @var array $arResult
 */

$component = $this->getComponent();
$arParams = $component->applyTemplateModifications();

foreach ($arResult['ITEMS'] as &$arItem) {
    $arItem["NAME"] = ($arItem['PROPERTIES']['SEO_NAME']['VALUE']) ? $arItem['PROPERTIES']['SEO_NAME']['VALUE'] : htmlspecialchars_decode(preg_replace(array('|[\s]+|s','/\(|\)/'), array(' ', '"'), trim($arItem['NAME'])));

    // Получение розничной цены
    if (!empty($arItem['ITEM_PRICES'])) {
        foreach ($arItem['ITEM_PRICES'] as $priceInfo) {
            // Проверяем тип цены "Розничная цена"
            if ($priceInfo['PRICE_TYPE_ID'] == 1) {  // Обычно ID 1 = Розничная цена, но может быть и другой
                $arItem['RETAIL_PRICE'] = $priceInfo['PRICE'];
                break;
            }
        }
    }

    // Если не найдена по типу, берем первую доступную цену
    if (empty($arItem['RETAIL_PRICE']) && !empty($arItem['ITEM_PRICES'])) {
        $arItem['RETAIL_PRICE'] = $arItem['ITEM_PRICES'][0]['PRICE'];
    }

    // Если нет ITEM_PRICES, берем встроенную цену
    if (empty($arItem['RETAIL_PRICE'])) {
        $arItem['RETAIL_PRICE'] = $arItem['PRICE'] ?? 0;
    }
}
