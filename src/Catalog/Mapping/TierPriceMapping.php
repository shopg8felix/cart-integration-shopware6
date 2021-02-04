<?php

namespace Shopgate\Shopware\Catalog\Mapping;

use ReflectionClass;
use ReflectionException;
use Shopgate\Shopware\Customer\CustomerBridge;
use Shopgate\Shopware\Exceptions\MissingContextException;
use Shopgate\Shopware\Storefront\ContextManager;
use Shopgate_Model_Catalog_TierPrice;
use Shopware\Core\Checkout\Cart\Rule\AlwaysValidRule;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupCollection;
use Shopware\Core\Checkout\Customer\Rule\CustomerGroupRule;
use Shopware\Core\Content\Product\Aggregate\ProductPrice\ProductPriceCollection;
use Shopware\Core\Content\Product\Aggregate\ProductPrice\ProductPriceEntity;
use Shopware\Core\Content\Rule\RuleEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Pricing\Price;
use Shopware\Core\Framework\Rule\Container\AndRule;
use Shopware\Core\Framework\Rule\Container\OrRule;
use Shopware\Core\Framework\Rule\Rule;

class TierPriceMapping
{
    /** @var CustomerBridge */
    private $customerBridge;
    /** @var ContextManager */
    private $contextManager;

    /**
     * @param ContextManager $contextManager
     * @param CustomerBridge $customerBridge
     */
    public function __construct(ContextManager $contextManager, CustomerBridge $customerBridge)
    {
        $this->contextManager = $contextManager;
        $this->customerBridge = $customerBridge;
    }

    /**
     * @param ProductPriceCollection $priceCollection
     * @param Price $mainPrice
     * @return Shopgate_Model_Catalog_TierPrice[]
     * @throws MissingContextException
     * @throws ReflectionException
     */
    public function mapTierPrices(ProductPriceCollection $priceCollection, Price $mainPrice): array
    {
        $groups = $this->customerBridge->getGroups();
        $list = [];
        foreach ($this->getValidTiers($priceCollection) as $swTier) {
            if (null === ($tierPrice = $this->mapProductTier($swTier, $mainPrice))) {
                continue;
            }
            /** @var AndRule|OrRule $payload */
            /** @noinspection NullPointerExceptionInspection */
            $payload = $swTier->getRule()->getPayload();
            $validGroupIds = $this->getConditionalCustomerGroups($payload, $groups);
            if ($validGroupIds) {
                foreach ($validGroupIds as $groupId) {
                    $newTierPrice = clone $tierPrice;
                    $newTierPrice->setCustomerGroupUid($groupId);
                    $list[] = $newTierPrice;
                }
            } else {
                $list[] = $tierPrice;
            }
        }

        return $list;
    }

    /**
     * Only returns tier price with valid rules
     *
     * @param ProductPriceCollection $priceCollection
     * @return ProductPriceEntity[]
     */
    private function getValidTiers(ProductPriceCollection $priceCollection): array
    {
        $validRules = [];
        /** @var ProductPriceEntity $swTier */
        foreach ($priceCollection as $swTier) {
            $rule = $swTier->getRule();
            if ($rule && $this->validateRule($rule)) {
                $validRules[] = $swTier;
            }
        }
        return $validRules;
    }

    /**
     * @param RuleEntity $rule
     * @return bool
     */
    private function validateRule(RuleEntity $rule): bool
    {
        $payload = $rule->getPayload();
        if (!$payload instanceof Rule) {
            return false;
        }

        return $this->isValidRule(true, $payload);
    }

    /**
     * Check if rule is valid for Shopgate export.
     * We consider valid if:
     * In AND condition only AlwaysValid or Group rule is present
     * In OR condition if one of AlwaysValid or Group rules are present
     *
     * @param bool $carry - recursive memory
     * @param AndRule|OrRule|Rule $rule
     * @return bool
     */
    private function isValidRule(bool $carry, Rule $rule): bool
    {
        if ($rule instanceof OrRule || $rule instanceof AndRule) {
            return array_reduce($rule->getRules(), function ($carry, Rule $rule) {
                return $this->isValidRule($carry, $rule);
            }, $carry);
        }

        return $rule instanceof AlwaysValidRule || $rule instanceof CustomerGroupRule;
    }

    /**
     * @param ProductPriceEntity $priceEntity
     * @param Price $normalPrice
     * @return null|Shopgate_Model_Catalog_TierPrice
     * @throws MissingContextException
     */
    private function mapProductTier(
        ProductPriceEntity $priceEntity,
        Price $normalPrice
    ): ?Shopgate_Model_Catalog_TierPrice {
        $currencyId = $this->contextManager->getSalesContext()->getSalesChannel()->getCurrencyId();
        $tierPrice = new Shopgate_Model_Catalog_TierPrice();
        $tierPrice->setFromQuantity($priceEntity->getQuantityStart());
        $tierPrice->setToQuantity($priceEntity->getQuantityEnd());
        $tierPrice->setReductionType(Shopgate_Model_Catalog_TierPrice::DEFAULT_TIER_PRICE_TYPE_FIXED);
        if ($priceEntity->getPrice()
            && ($reducedPrice = $priceEntity->getPrice()->getCurrencyPrice($currencyId, true))
        ) {
            $tierPrice->setReduction($normalPrice->getGross() - $reducedPrice->getGross());
            return $tierPrice;
        }

        return null;
    }

    /**
     * Retrieves CustomerGroup ID's that are valid from the passed rule container.
     * Does not handle AND, OR container logic.
     *
     * @param AndRule|OrRule $ruleContainer
     * @param CustomerGroupCollection $groupCollection
     * @return array
     * @throws ReflectionException
     */
    private function getConditionalCustomerGroups($ruleContainer, CustomerGroupCollection $groupCollection): array
    {
        $customerGroupRule = $this->findCustomerGroup(null, $ruleContainer);
        /** @var null|CustomerGroupRule $customerGroupRule */
        if (null !== $customerGroupRule) {
            $reflection = new ReflectionClass(get_class($customerGroupRule));
            $grpProp = $reflection->getProperty('customerGroupIds');
            $opProp = $reflection->getProperty('operator');
            $grpProp->setAccessible(true);
            $opProp->setAccessible(true);
            switch ($opProp->getValue($customerGroupRule)) {
                case '=':
                    return $grpProp->getValue($customerGroupRule);
                case '!=':
                    return array_diff($groupCollection->getIds(), $grpProp->getValue($customerGroupRule));
            }
        }

        return [];
    }

    /**
     * @param null|CustomerGroupRule $carry
     * @param AndRule|OrRule|Rule $rule
     * @return CustomerGroupRule|null
     */
    private function findCustomerGroup(?CustomerGroupRule $carry, Rule $rule): ?CustomerGroupRule
    {
        if ($rule instanceof AndRule || $rule instanceof OrRule) {
            return array_reduce($rule->getRules(), function ($carry, Rule $item) {
                return $this->findCustomerGroup($carry, $item);
            }, $carry);
        }
        if ($rule instanceof CustomerGroupRule) {
            return $rule;
        }

        return $carry;
    }

    /**
     * @param ProductPriceCollection $priceCollection
     * @param Price $basePrice
     * @return Price
     * @throws MissingContextException
     */
    public function getHighestPrice(ProductPriceCollection $priceCollection, Price $basePrice): Price
    {
        $currencyId = $this->contextManager->getSalesContext()->getSalesChannel()->getCurrencyId();
        $reduced = array_reduce(
            $this->getValidTiers($priceCollection),
            static function (Price $carry, ProductPriceEntity $entity) use ($currencyId) {
                $curPrice = $entity->getPrice()->getCurrencyPrice($currencyId);
                return $carry > $curPrice ? $carry : $curPrice;
            },
            $basePrice
        );

        return $reduced;
    }
}