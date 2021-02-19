<?php

namespace Shopgate\Shopware\Customer\Mapping;

use Shopgate\Shopware\Exceptions\MissingContextException;
use ShopgateCustomer;
use ShopgateLibraryException;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;

class CustomerMapping
{
    /** @var GroupMapping */
    private $groupMapping;
    /** @var AddressMapping */
    private $addressMapping;
    /** @var SalutationMapping */
    private $salutationMapping;

    /**
     * @param GroupMapping $groupMapping
     * @param AddressMapping $addressMapping
     * @param SalutationMapping $salutationMapping
     */
    public function __construct(
        GroupMapping $groupMapping,
        AddressMapping $addressMapping,
        SalutationMapping $salutationMapping
    ) {
        $this->groupMapping = $groupMapping;
        $this->addressMapping = $addressMapping;
        $this->salutationMapping = $salutationMapping;
    }

    public function mapToShopgateEntity(CustomerEntity $detailedCustomer): ShopgateCustomer
    {
        $shopgateCustomer = new ShopgateCustomer();
        $shopgateCustomer->setRegistrationDate(
            $detailedCustomer->getCreatedAt() ? $detailedCustomer->getCreatedAt()->format('Y-m-d') : null
        );
        $shopgateCustomer->setNewsletterSubscription((int)$detailedCustomer->getNewsletter());
        $shopgateCustomer->setCustomerId($detailedCustomer->getId());
        $shopgateCustomer->setCustomerNumber($detailedCustomer->getCustomerNumber());
        $shopgateCustomer->setMail($detailedCustomer->getEmail());
        $shopgateCustomer->setFirstName($detailedCustomer->getFirstName());
        $shopgateCustomer->setLastName($detailedCustomer->getLastName());
        $shopgateCustomer->setBirthday(
            $detailedCustomer->getBirthday() ? $detailedCustomer->getBirthday()->format('Y-m-d') : null
        );

        // Phone
        $shopgateCustomer->setPhone($detailedCustomer->getDefaultShippingAddress()
            ? $detailedCustomer->getDefaultShippingAddress()->getPhoneNumber()
            : $this->addressMapping->getWorkingPhone($detailedCustomer->getAddresses()));
        // Gender
        if ($salutation = $detailedCustomer->getSalutation()) {
            $shopgateCustomer->setGender($this->salutationMapping->toShopgateGender($salutation));
        }
        // Groups
        if ($group = $detailedCustomer->getGroup()) {
            $shopgateCustomer->setCustomerGroups([$this->groupMapping->toShopgateGroup($group)]);
        }
        // Addresses
        $shopgateCustomer->setAddresses($this->addressMapping->mapFromShopware($detailedCustomer));
        $shopgateCustomer->setTaxClassId('1');
        $shopgateCustomer->setTaxClassKey('default');
        return $shopgateCustomer;
    }

    /**
     * @param ShopgateCustomer $customer
     * @param string|null $password - set to null for guest
     * @return RequestDataBag
     * @throws ShopgateLibraryException
     * @throws MissingContextException
     */
    public function mapToShopwareEntity(ShopgateCustomer $customer, ?string $password): RequestDataBag
    {
        $data = null === $password ? ['guest' => true]: ['password' => $password];
        $data['email'] = $customer->getMail();
        $data['salutationId'] = $this->salutationMapping->getSalutationIdByGender($customer->getGender());
        $data['firstName'] = $customer->getFirstName();
        $data['lastName'] = $customer->getLastName();
        $shopgateBillingAddress = $this->addressMapping->getBillingAddress($customer);
        $shopgateShippingAddress = $this->addressMapping->getShippingAddress($customer);
        $data['billingAddress'] = $this->addressMapping->mapAddressData($shopgateBillingAddress);
        if ($shopgateShippingAddress !== false) {
            $data['shippingAddress'] = $this->addressMapping->mapAddressData($shopgateShippingAddress);
        }
        return new RequestDataBag($data);
    }
}