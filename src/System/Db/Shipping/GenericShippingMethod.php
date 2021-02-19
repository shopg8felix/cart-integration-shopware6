<?php

namespace Shopgate\Shopware\System\Db\Shipping;

use Shopgate\Shopware\System\Db\ClassCastInterface;
use Shopgate\Shopware\System\Db\Rule\IsShopgateRuleGroup;
use Shopware\Core\Checkout\Shipping\ShippingMethodEntity;

class GenericShippingMethod extends ShippingMethodEntity implements ClassCastInterface
{
    public const UUID = '368e891dbec442c2892f82edd6f4a7dc';
    protected $id = self::UUID;
    protected $deliveryTimeId = GenericDeliveryTime::UUID;
    protected $name = 'Shopgate Shipping';
    protected $description = 'Used for Shopgate imports';
    protected $availabilityRuleId = IsShopgateRuleGroup::UUID;
    protected $active = false;

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'deliveryTimeId' => $this->deliveryTimeId,
            'name' => $this->name,
            'description' => $this->description,
            'active' => $this->active,
            'availabilityRuleId' => $this->availabilityRuleId
        ];
    }
}