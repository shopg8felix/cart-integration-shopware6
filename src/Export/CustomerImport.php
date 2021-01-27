<?php

namespace Shopgate\Shopware\Export;

use Shopgate\Shopware\Exceptions\MissingContextException;
use Shopgate\Shopware\Storefront\ContextManager;
use ShopgateAddress;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use ShopgateCustomer;
use Shopware\Core\Checkout\Customer\SalesChannel\RegisterRoute;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use ShopgateLibraryException;

class CustomerImport
{
    /** @var RegisterRoute */
    private $registerRoute;
    /** @var LocationHelper */
    private $locationHelper;
    /** @var CustomerExport */
    private $customerExport;
    /** @var ContextManager */
    private $contextManager;

    /**
     * @param RegisterRoute $registerRoute
     * @param ContextManager $contextManager
     */
    public function __construct(
        RegisterRoute $registerRoute,
        LocationHelper $locationHelper,
        CustomerExport $customerExport,
        ContextManager $contextManager
    ) {
        $this->registerRoute = $registerRoute;
        $this->locationHelper = $locationHelper;
        $this->customerExport = $customerExport;
        $this->contextManager = $contextManager;
    }

    /**
     * @param string $user
     * @param string $password
     * @param ShopgateCustomer $customer
     * @throws MissingContextException
     * @throws ShopgateLibraryException
     */
    public function registerCustomer(string $user, string $password, ShopgateCustomer $customer): void
    {
        $data = [];
        $data['email'] = $user;
        $data['password'] = $password;
        $data['salutationId'] = $this->getSalutationIdByGender($customer->getGender());
        $data['firstName'] = $customer->getFirstName();
        $data['lastName'] = $customer->getLastName();
        $shopgateBillingAddress = $this->getBillingAddress($customer);
        $shopgateShippingAddress = $this->getShippingAddress($customer);
        $data['billingAddress'] = $this->mapAddressData($shopgateBillingAddress);
        if ($shopgateShippingAddress !== false) {
            $data['shippingAddress'] = $this->mapAddressData($shopgateShippingAddress);
        }
        $dataBag = new RequestDataBag($data);
        $this->registerRoute->register($dataBag, $this->contextManager->getSalesContext(), false);
    }

    /**
     * @param ShopgateAddress $shopgateAddress
     * @return RequestDataBag
     * @throws MissingContextException
     */
    protected function mapAddressData(ShopgateAddress $shopgateAddress): RequestDataBag
    {
        $address = [];
        $address['salutationId'] = $this->getSalutationIdByGender($shopgateAddress->getGender());
        $address['firstName'] = $shopgateAddress->getFirstName();
        $address['lastName'] = $shopgateAddress->getLastName();
        $address['street'] = $shopgateAddress->getStreet1();
        $address['zipcode'] = $shopgateAddress->getZipcode();
        $address['city'] = $shopgateAddress->getCity();
        $address['countryId'] = $this->locationHelper->getCountryIdByIso($shopgateAddress->getCountry());
        $address['countryStateId'] = $this->locationHelper->getStateIdByIso($shopgateAddress->getState());

        return new RequestDataBag($address);
    }

    /**
     * @param ShopgateCustomer $customer
     * @return ShopgateAddress
     * @throws ShopgateLibraryException
     */
    protected function getBillingAddress(ShopgateCustomer $customer): ShopgateAddress
    {
        $anyAddress = null;
        foreach ($customer->getAddresses() as $shopgateAddress) {
            if ($shopgateAddress->getIsInvoiceAddress()) {
                return $shopgateAddress;
            }
            $anyAddress = $shopgateAddress;
        }
        if ($anyAddress !== null) {
            return $anyAddress;
        }

        throw new ShopgateLibraryException(
            ShopgateLibraryException::PLUGIN_NO_ADDRESSES_FOUND,
            null,
            false,
            false
        );
    }

    /**
     * @param ShopgateCustomer $customer
     * @return false|ShopgateAddress
     */
    protected function getShippingAddress(ShopgateCustomer $customer)
    {
        foreach ($customer->getAddresses() as $shopgateAddress) {
            if ($shopgateAddress->getIsDeliveryAddress()) {
                return $shopgateAddress;
            }
        }
        return false;
    }

    /**
     * @param string $gender
     * @return string
     * @throws MissingContextException
     */
    protected function getSalutationIdByGender(string $gender): string
    {

        switch ($gender) {
            case 'm':
                return $this->customerExport->getMaleSalutationId();
            case 'f':
                return $this->customerExport->getFemaleSalutationId();
            default:
                return $this->customerExport->getUnspecifiedSalutationId();
        }
    }
}
