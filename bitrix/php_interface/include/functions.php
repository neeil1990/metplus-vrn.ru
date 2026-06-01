<?php

function getPropVal($ID_BLOCK, $ID, $CODE)
{
    if (CModule::IncludeModule('iblock')) {
        $db_props = CIBlockElement::GetProperty($ID_BLOCK, $ID, array("sort" => "asc"), array("CODE" => $CODE));
        if ($ar_props = $db_props->Fetch()) {
            return $ar_props['VALUE'];
        }
    }

    return null;
}

function getCoefficientProduct($ID_BLOCK, $ID)
{
    return floatval(getPropVal($ID_BLOCK, $ID, 'KOEFFITSENT_RASCHET'));
}

function getLengthProduct($ID_BLOCK, $ID)
{
    return intval(getPropVal($ID_BLOCK, $ID, 'DLINA_RASCHET'));
}

function isCustomPrice($ID_BLOCK, $ID)
{
    return (getCoefficientProduct($ID_BLOCK, $ID) && getLengthProduct($ID_BLOCK, $ID));
}