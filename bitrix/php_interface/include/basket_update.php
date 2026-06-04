<?php
use Bitrix\Main\Event;
use Bitrix\Sale\Basket;
use Bitrix\Sale;

class BasketServiceManager
{
    private $basket = null;
    private $productInCart = null;
    private $propertyCollectionProduct = null;
    protected const SERVICE_CODE = 'CHILD_BASKET_ITEM_ID';

    public function __construct($productId)
    {
        $this->productId = $productId;
        $this->init();
    }

    public function getPropertyProduct($code)
    {
        foreach ($this->propertyCollectionProduct as $item) {
            if ($item->getField('CODE') == $code) {
                return $this->propertyCollectionProduct->getItemById($item->getField('ID'));
            }
        }

        return null;
    }

    public function deletePropertyProduct($code)
    {
        $prop = $this->getPropertyProduct($code);

        if (!$prop) {
            return false;
        }

        $prop->delete();
        $this->propertyCollectionProduct->save();

        return true;
    }

    public function getPropertyValue($code)
    {
        $prop = $this->getPropertyProduct($code);

        if (!$prop) {
            return null;
        }

        return $prop->getField('VALUE');
    }

    public function removeItemFromCart($cartId)
    {
        $item = $this->basket->getItemById($cartId);

        if (!$item) {
            return false;
        }

        $item->delete();

        return $this->basket->save();
    }

    public function createPropertyProduct($name, $code, $value)
    {
        $item = $this->propertyCollectionProduct->createItem();

        $item->setFields(
            [
                'NAME' => $name,
                'CODE' => $code,
                'VALUE' => $value,
            ]
        );

        $this->propertyCollectionProduct->save();
    }

    private function init()
    {
        $this->basket = Sale\Basket::loadItemsForFUser(
            Sale\Fuser::getId(),
            Bitrix\Main\Context::getCurrent()->getSite()
        );

        foreach ($this->basket as $item) {
            if ($item->getProductId() == $this->productId) {
                $this->productInCart = $item;
                $this->propertyCollectionProduct = $item->getPropertyCollection();
                break;
            }
        }
    }
}

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

        if (!isCustomPrice($ID_BLOCK, $productId)) {
            return true;
        }

        $length = getLengthProduct($ID_BLOCK, $productId);
        $quantity = $basketItem->getQuantity();
        $needCut = fmod($quantity, $length);

        if ($needCut > 0) {
            $item = $basket->createItem('', 0);

            $item->setFields([
                'PRODUCT_ID' => 0, // У виртуального товара нет ID в каталоге
                'NAME' => 'Резка газом', // Название берем из свойства
                'PRICE' => 10, // Цену берем из свойства
                'CUSTOM_PRICE' => 'Y', // Блокируем пересчет цены Битриксом
                'QUANTITY' => 1, // Количество равно количеству основного товара
                'CURRENCY' => \Bitrix\Currency\CurrencyManager::getBaseCurrency(),
                'LID' => \Bitrix\Main\Context::getCurrent()->getSite(),
                'PRODUCT_PROVIDER_CLASS' => '',
                'NOTES' => 'заметка'
            ]);
        }
    }
}
