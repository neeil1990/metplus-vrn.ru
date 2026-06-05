<?php
use Bitrix\Main\Event;
use Bitrix\Sale\Basket;

function OnSaleBasketBeforeSavedHandler(Event $event)
{
    $ID_BLOCK = 36;

    /** @var Basket $basket */
    $basket = $event->getParameter('ENTITY');

    foreach ($basket as $basketItem) {
        $productId = $basketItem->getProductId();
        if ($productId === 0) {
            $basketItem->delete();
        }
    }

    foreach ($basket as $basketItem) {
        $productId = $basketItem->getProductId();

        $service = null;
        foreach ($basketItem->getPropertyCollection() as $property) {
            if ($property->getField('CODE') == 'SERVICE') {
                $service = [
                    'NAME' => $property->getField('NAME'),
                    'VALUE' => $property->getField('VALUE'),
                ];

                break;
            }
        }

        if ($service) {
            $length = getLengthProduct($ID_BLOCK, $productId);
            $quantity = $basketItem->getQuantity();
            $needCut = fmod($quantity, $length);

            if ($needCut > 0) {
                $item = $basket->createItem('', 0);

                $item->setFields([
                    'PRODUCT_ID' => 0, // У виртуального товара нет ID в каталоге
                    'NAME' => $service['NAME'], // Название берем из свойства
                    'PRICE' => $service['VALUE'], // Цену берем из свойства
                    'CUSTOM_PRICE' => 'Y', // Блокируем пересчет цены Битриксом
                    'QUANTITY' => 1, // Количество равно количеству основного товара
                    'CURRENCY' => \Bitrix\Currency\CurrencyManager::getBaseCurrency(),
                    'LID' => \Bitrix\Main\Context::getCurrent()->getSite(),
                    'PRODUCT_PROVIDER_CLASS' => '',
                    'NOTES' => ''
                ]);
            }
        }
    }
}

