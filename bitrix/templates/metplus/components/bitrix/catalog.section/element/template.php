<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use \Bitrix\Main\Localization\Loc;

/**
 * @global CMain $APPLICATION
 * @var array $arParams
 * @var array $arResult
 * @var CatalogSectionComponent $component
 * @var CBitrixComponentTemplate $this
 * @var string $templateName
 * @var string $componentPath
 *
 *  _________________________________________________________________________
 * |	Attention!
 * |	The following comments are for system use
 * |	and are required for the component to work correctly in ajax mode:
 * |	<!-- items-container -->
 * |	<!-- pagination-container -->
 * |	<!-- component-end -->
 */

$this->setFrameMode(true);

if(count($arResult['ITEMS'])) :
?>

<table class="product-table" id="product-table">
    <thead>
        <tr>
            <th>Наименование товара</th>
            <th>Метры</th>
            <th>Штуки</th>
            <th>Цена</th>
            <th>Итог</th>
            <th>Купить</th>
        </tr>
    </thead>

    <tbody>
        <?php foreach ($arResult['ITEMS'] as $arItem): ?>
        <tr data-price="<?=$arItem['RETAIL_PRICE']?>">
            <td class="product-table_first-cell">
                <span class="product-item_name"><?=$arItem["NAME"];?></span>
                <span class="product-availability">В наличии на складе.</span>
                <div class="product-item_popup">
                    <div class="product-item_popup-close"><span class="glipf-reset"></span></div>
                    <ul class="product-item_popup-list">
                        <li>
                            <strong>Наименование товара</strong>
                            <span class="product-item_name"><?=$arItem["NAME"];?></span>
                        </li>
                    </ul>
                    <a href="javascript:void(0)" class="main-btn product-item_buy-btn">Купить</a>
                </div>
            </td>
            <td>
                <input type="number" class="product-table-input" min="0" step="0.1" placeholder="0.0" name="meters" data-meters-in-one-piece="<?=$arItem["PROPERTIES"]["DLINA_RASCHET"]["VALUE"]?>">
            </td>
            <td>
                <input type="number" class="product-table-input" min="0" step="0.1" placeholder="0.0" name="pieces" data-meters-in-one-piece="<?=$arItem["PROPERTIES"]["DLINA_RASCHET"]["VALUE"]?>">
            </td>
            <td class="product-price"><?=number_format($arItem['RETAIL_PRICE'], 2, '.', ' ')?></td>
            <td class="product-total">0.00</td>
            <td>
                <a href="javascript:void(0)" class="product-item_cart-btn main-btn" id="<?=$arItem['ID']?>"><span class="glipf-cart"></span></a>
            </td>
        </tr>
        <?php endforeach;?>
    </tbody>
</table>

<div class="row">
    <div class="col-md-6">
        <div class="product-availability_text">— Наличие товара на складе</div>
        <div class="product-availability_text yellow">— Количество ограничено, уточняйте у менеджера</div>
    </div>
    <div class="col-md-6">
        <?php if($arParams["DISPLAY_BOTTOM_PAGER"]):?>
            <?=$arResult["NAV_STRING"]?>
        <?php endif;?>
    </div>
</div>

<?php endif; ?>

<?php if($arParams["DEPTH_LEVEL"] == "1"): ?>
	<div class="unified-text-section"><?=$arResult['DESCRIPTION'];?></div>
<?php endif; ?>
