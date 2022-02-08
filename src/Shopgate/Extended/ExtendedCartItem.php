<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Shopgate\Extended;

use ShopgateCartItem;
use ShopgateOrderItem;

class ExtendedCartItem extends ShopgateCartItem
{
    use CloningTrait;

    public function transformFromOrderItem(ShopgateOrderItem $orderItem): ExtendedCartItem
    {
        return $this->dataToEntity($orderItem->toArray());
    }

    public function setStockQuantity($value): void
    {
        parent::setStockQuantity((int)$value);
    }

    public function __serialize(): array
    {
        return $this->toArray();
    }
}
