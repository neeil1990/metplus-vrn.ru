<?php

// Обработчик для обновления цены при обмене с 1С

class PriceUpdater {

    private static $iblockId = "35";
    private static $priceTypeId = 16;
    private static $pricePerMeterId = 17;
    private static $pricePerMeterPlus20Id = 18;

    public static function onAfterIBlockElementUpdate(&$arFields)
    {
        if (!$arFields["RESULT"]) return;

        if (!CModule::IncludeModule("catalog") || !CModule::IncludeModule("iblock")) return;

        $elementId = $arFields["ID"];
        $iblockId = $arFields["IBLOCK_ID"];

        if ($arFields["IBLOCK_ID"] != self::$iblockId) return;

        $propValues = self::getPropertyValues($elementId, $iblockId);

        self::updatePrice($elementId, self::$pricePerMeterId, self::calculatePerMeterPrice($arFields, $propValues));
        self::updatePrice($elementId, self::$pricePerMeterPlus20Id, self::calculatePerMeterPlus20Price($arFields, $propValues));
    }

    private static function calculatePerMeterPrice($arFields, $propValues)
    {
        $basePrice = self::getPrice($arFields["ID"], self::$priceTypeId);
        $coefficient = (float) ($propValues["KOEFFITSENT_RASCHET"] ?? 0);

        if (!$basePrice) {
            return 0;
        }

        $price = (float) $basePrice["PRICE"];

        return $price * $coefficient;
    }

    private static function calculatePerMeterPlus20Price($arFields, $propValues)
    {
        return 1.2 * self::calculatePerMeterPrice($arFields, $propValues);
    }

    private static function getPropertyValues($elementId, $iblockId)
    {
        $propValues = [];
        $properties = CIBlockElement::GetProperty($iblockId, $elementId, [], []);

        while ($prop = $properties->Fetch()) {
            $propValues[$prop["CODE"]] = $prop["VALUE"];
        }

        return $propValues;
    }

    private static function getPrice($elementId, $priceId)
    {
        return CPrice::GetList([], [
            "PRODUCT_ID" => $elementId,
            "CATALOG_GROUP_ID" => $priceId
        ])->Fetch();
    }

    private static function updatePrice($elementId, $priceId, $newPrice)
    {
        $arFields = Array(
            "PRODUCT_ID" => $elementId,
            "CATALOG_GROUP_ID" => $priceId,
            "PRICE" => $newPrice,
            "CURRENCY" => "RUB",
        );

        if ($arr = self::getPrice($elementId, $priceId))
        {
            CPrice::Update($arr["ID"], $arFields);
        }
        else
        {
            CPrice::Add($arFields);
        }
    }
}
