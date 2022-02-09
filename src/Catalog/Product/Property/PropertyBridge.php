<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Catalog\Product\Property;

use Shopgate\Shopware\Storefront\ContextManager;
use Shopware\Core\Content\Property\PropertyGroupCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

class PropertyBridge
{
    private EntityRepositoryInterface $propertyGroupOptionRepo;
    private ContextManager $contextManager;

    public function __construct(EntityRepositoryInterface $propertyGroupOptionRepo, ContextManager $contextManager)
    {
        $this->propertyGroupOptionRepo = $propertyGroupOptionRepo;
        $this->contextManager = $contextManager;
    }

    /**
     * @param string[] $uids
     */
    public function getGroupOptions(array $uids = []): ?PropertyGroupCollection
    {
        $criteria = new Criteria(!empty($uids) ? $uids : null);
        $criteria->setTitle('shopgate::property-group-option::ids');
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->propertyGroupOptionRepo->search(
            $criteria,
            $this->contextManager->getSalesContext()->getContext()
        )->getEntities();
    }
}
