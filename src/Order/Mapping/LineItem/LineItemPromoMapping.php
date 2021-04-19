<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order\Mapping\LineItem;

use ShopgateExternalCoupon;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;

class LineItemPromoMapping
{
    /**
     * Identifier placed inside the coupon internal field to identify
     * cart rules from actual coupons
     */
    public const RULE_ID = 'cartRule';

    /**
     * @param ShopgateExternalCoupon[] $promos
     * @return array
     */
    public function mapIncomingPromos(array $promos): array
    {
        $lineItems = [];
        foreach ($promos as $coupon) {
            // skip adding line item as cart rules are applied automatically
            if ($coupon->getInternalInfo() === self::RULE_ID) {
                continue;
            }
            $lineItems[] = [
                'referencedId' => $coupon->getCode(),
                'type' => LineItem::PROMOTION_LINE_ITEM_TYPE
            ];
        }

        return $lineItems;
    }

    /**
     * @param LineItem $lineItem
     * @param ShopgateExternalCoupon $coupon
     * @return ShopgateExternalCoupon
     */
    public function mapValidCoupon(LineItem $lineItem, ShopgateExternalCoupon $coupon): ShopgateExternalCoupon
    {
        $coupon->setIsValid(true);
        $coupon->setName($lineItem->getLabel());
        $coupon->setIsFreeShipping(false);
        if ($lineItem->getPrice()) {
            $coupon->setAmountGross(-($lineItem->getPrice()->getTotalPrice()));
        }
        return $coupon;
    }
}
