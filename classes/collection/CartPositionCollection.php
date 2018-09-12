<?php namespace Lovata\OrdersShopaholic\Classes\Collection;

use Lovata\Toolbox\Classes\Collection\ElementCollection;

use Lovata\Shopaholic\Models\Settings;
use Lovata\OrdersShopaholic\Classes\Processor\CartProcessor;
use Lovata\OrdersShopaholic\Classes\Item\CartPositionItem;

/**
 * Class CartPositionCollection
 * @package Lovata\OrdersShopaholic\Classes\Collection
 * @author  Andrey Kharanenka, a.khoronenko@lovata.com, LOVATA Group
 */
class CartPositionCollection extends ElementCollection
{
    const ITEM_CLASS = CartPositionItem::class;

    /**
     * Cart item has product
     * @param int $iProductID
     * @return bool
     */
    public function hasProduct($iProductID)
    {
        if ($this->isEmpty() || empty($iProductID)) {
            return false;
        }

        $arPositionList = $this->all();
        /** @var CartPositionItem $obPositionItem */
        foreach ($arPositionList as $obPositionItem) {

            $obOfferItem = $obPositionItem->offer;
            if ($obOfferItem->isEmpty()) {
                continue;
            }

            if ($obOfferItem->product_id == $iProductID) {
                return true;
            }
        }

        return false;
    }

    /**
     * Cart item has offer
     * @param int $iOfferID
     * @return bool
     */
    public function hasOffer($iOfferID)
    {
        if ($this->isEmpty() || empty($iOfferID)) {
            return false;
        }

        $arPositionList = $this->all();
        /** @var CartPositionItem $obPositionItem */
        foreach ($arPositionList as $obPositionItem) {
            if ($obPositionItem->offer->id == $iOfferID) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get total price string
     * @return string
     */
    public function getTotalPrice()
    {
        $obPriceData = $this->getTotalPriceData();

        return $obPriceData->price;
    }

    /**
     * Get total price value
     * @return float
     */
    public function getTotalPriceValue()
    {
        $obPriceData = $this->getTotalPriceData();

        return $obPriceData->price_value;
    }

    /**
     * Get total position price data
     * @return \Lovata\OrdersShopaholic\Classes\PromoMechanism\PriceContainer
     */
    public function getTotalPriceData()
    {
        $obPriceData = CartProcessor::instance()->getCartPositionTotalPriceData();

        return $obPriceData;
    }

    /**
     * Get currency value
     * @return null|string
     */
    public function getCurrency()
    {
        return Settings::getValue('currency');
    }
}
