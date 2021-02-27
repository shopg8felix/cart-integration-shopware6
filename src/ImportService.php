<?php

declare(strict_types=1);

namespace Shopgate\Shopware;

use Shopgate\Shopware\Customer\CustomerComposer;
use Shopgate\Shopware\Exceptions\MissingContextException;
use Shopgate\Shopware\Order\OrderComposer;
use ShopgateCustomer;
use ShopgateLibraryException;
use ShopgateOrder;

class ImportService
{
    /** @var CustomerComposer */
    private $customerComposer;
    /** @var OrderComposer */
    private $orderComposer;

    /**
     * @param CustomerComposer $customerImport
     * @param OrderComposer $orderComposer
     */
    public function __construct(CustomerComposer $customerImport, OrderComposer $orderComposer)
    {
        $this->customerComposer = $customerImport;
        $this->orderComposer = $orderComposer;
    }

    /**
     * @param string $user
     * @param string $password
     * @param ShopgateCustomer $customer
     * @throws MissingContextException
     * @throws ShopgateLibraryException
     * @noinspection PhpUnusedParameterInspection
     */
    public function registerCustomer(string $user, string $password, ShopgateCustomer $customer): void
    {
        $this->customerComposer->registerCustomer($password, $customer);
    }

    /**
     * @param ShopgateOrder $order
     * @return array
     * @throws MissingContextException
     * @throws ShopgateLibraryException
     */
    public function addOrder(ShopgateOrder $order):array
    {
        return $this->orderComposer->addOrder($order);
    }
}
