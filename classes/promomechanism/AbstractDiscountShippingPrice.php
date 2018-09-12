<?php namespace Lovata\OrdersShopaholic\Classes\PromoMechanism;

/**
 * Class AbstractDiscountShippingPrice
 * @package Lovata\OrdersShopaholic\Classes\PromoMechanism
 * @author  Andrey Kharanenka, a.khoronenko@lovata.com, LOVATA Group
 */
abstract class AbstractDiscountShippingPrice extends AbstractPromoMechanism implements InterfacePromoMechanism
{
    const TYPE = 'shipping';

    /**
     * @param float $fPrice
     * @return float
     */
    public function calculate($fPrice)
    {
        $this->bApplied = true;
        $fPrice = $this->applyDiscount($fPrice);

        return $fPrice;
    }
}