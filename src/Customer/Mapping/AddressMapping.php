<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Customer\Mapping;

use Shopgate\Shopware\System\CustomFields\CustomFieldMapping;
use ShopgateAddress;
use ShopgateCustomer;
use ShopgateLibraryException;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressCollection;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;

class AddressMapping
{
    private LocationMapping $locationMapping;
    private SalutationMapping $salutationMapping;
    private CustomFieldMapping $customFieldMapping;

    public function __construct(
        LocationMapping $locationMapping,
        SalutationMapping $salutationMapping,
        CustomFieldMapping $customFieldMapping
    ) {
        $this->locationMapping = $locationMapping;
        $this->salutationMapping = $salutationMapping;
        $this->customFieldMapping = $customFieldMapping;
    }

    public function mapToShopwareAddress(ShopgateAddress $shopgateAddress): RequestDataBag
    {
        $address = [];
        $address['salutationId'] = $this->salutationMapping->getSalutationIdByGender($shopgateAddress->getGender());
        $address['firstName'] = $shopgateAddress->getFirstName();
        $address['lastName'] = $shopgateAddress->getLastName();
        $address['street'] = $shopgateAddress->getStreet1();
        $address['zipcode'] = $shopgateAddress->getZipcode();
        $address['city'] = $shopgateAddress->getCity();
        $address['countryId'] = $this->locationMapping->getCountryIdByIso($shopgateAddress->getCountry());
        $address['countryStateId'] = $this->locationMapping->getStateIdByIso($shopgateAddress->getState());
        $address = array_merge(
            $address,
            $this->customFieldMapping->mapToShopwareCustomFields($shopgateAddress),
            $shopgateAddress->getCompany() ? ['company' => $shopgateAddress->getCompany()] : [],
            $shopgateAddress->getStreet2() ? ['additionalAddressLine1' => $shopgateAddress->getStreet2()] : [],
            $shopgateAddress->getPhone() ? ['phoneNumber' => $shopgateAddress->getPhone()] : [],
            $shopgateAddress->getMobile() ? ['phoneNumber' => $shopgateAddress->getMobile()] : []
        );

        return new RequestDataBag($address);
    }

    /**
     * @throws ShopgateLibraryException
     */
    public function getBillingAddress(ShopgateCustomer $customer): ShopgateAddress
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
    public function getShippingAddress(ShopgateCustomer $customer)
    {
        foreach ($customer->getAddresses() as $shopgateAddress) {
            if ($shopgateAddress->getIsDeliveryAddress()) {
                return $shopgateAddress;
            }
        }
        return false;
    }

    public function getWorkingPhone(CustomerAddressCollection $collection): ?string
    {
        foreach ($collection as $addressEntity) {
            if ($addressEntity->getPhoneNumber()) {
                return $addressEntity->getPhoneNumber();
            }
        }
        return null;
    }

    public function getSelectedAddressId(ShopgateAddress $address, CustomerEntity $customer): ?string
    {
        $addresses = $this->mapFromShopware($customer);
        foreach ($addresses as $shopwareAddress) {
            if ($shopwareAddress->equals($address)) {
                return $shopwareAddress->getId();
            }
        }
        return null;
    }

    /**
     * @return ShopgateAddress[]
     */
    public function mapFromShopware(CustomerEntity $customerEntity): array
    {
        $shopgateAddresses = [];
        foreach ($customerEntity->getAddresses() as $shopwareAddress) {
            $type = $this->mapAddressType($shopwareAddress,
                $customerEntity->getDefaultBillingAddressId(),
                $customerEntity->getDefaultShippingAddressId());
            $address = $this->mapAddress($shopwareAddress, $type);
            $shopgateAddresses[] = $address;
        }
        return $shopgateAddresses;
    }

    /**
     * @param CustomerAddressEntity|OrderAddressEntity $addressEntity
     */
    public function mapAddressType(Entity $addressEntity, string $defaultBillingId, string $defaultShippingId): int
    {
        $isBoth = false;
        if ($defaultBillingId === $defaultShippingId) {
            $isBoth = ShopgateAddress::BOTH;
        }

        switch ($addressEntity->getId()) {
            case $defaultBillingId:
                return $isBoth ?: ShopgateAddress::INVOICE;
            case $defaultShippingId:
                return $isBoth ?: ShopgateAddress::DELIVERY;
            default:
                return ShopgateAddress::BOTH;
        }
    }

    /**
     * @param CustomerAddressEntity|OrderAddressEntity $shopwareAddress
     * @param int $addressType - shopgate address type
     * @return ShopgateAddress
     */
    public function mapAddress(Entity $shopwareAddress, int $addressType): ShopgateAddress
    {
        $shopgateAddress = new ShopgateAddress();
        $shopgateAddress->setId($shopwareAddress->getId());
        $shopgateAddress->setAddressType($addressType);
        $shopgateAddress->setFirstName($shopwareAddress->getFirstName());
        $shopgateAddress->setLastName($shopwareAddress->getLastName());
        $shopgateAddress->setPhone($shopwareAddress->getPhoneNumber());
        $shopgateAddress->setCompany($shopwareAddress->getCompany());
        $shopgateAddress->setStreet1($shopwareAddress->getStreet());
        $street2 = $shopwareAddress->getAdditionalAddressLine1();
        if ($shopwareAddress->getAdditionalAddressLine2()) {
            $street2 .= $street2 ? "\n" : '';
            $street2 .= $shopwareAddress->getAdditionalAddressLine2();
        }
        $shopgateAddress->setStreet2($street2);
        $shopgateAddress->setCity($shopwareAddress->getCity());
        $shopgateAddress->setZipcode($shopwareAddress->getZipcode());
        if ($shopwareAddress->getCountryState()) {
            $shopgateAddress->setState($shopwareAddress->getCountryState()->getShortCode());
        }
        if ($shopwareAddress->getCountry()) {
            $shopgateAddress->setCountry($shopwareAddress->getCountry()->getIso());
        }
        if (method_exists($shopwareAddress, 'getCustomer') && $shopwareAddress->getCustomer()) {
            $shopgateAddress->setMail($shopwareAddress->getCustomer()->getEmail());
        }
        if ($shopwareAddress->getSalutation()) {
            $shopgateAddress->setGender($this->salutationMapping->toShopgateGender($shopwareAddress->getSalutation()));
        }
        $customFields = $this->customFieldMapping->mapToShopgateCustomFields($shopwareAddress);
        $shopgateAddress->setCustomFields($customFields);

        return $shopgateAddress;
    }

}
