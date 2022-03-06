<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order;

use Shopgate\Shopware\Order\Customer\AddressComposer;
use Shopgate\Shopware\Order\Quote\QuoteErrorMapping;
use Shopgate\Shopware\Storefront\ContextManager;
use ShopgateCartBase;
use ShopgateLibraryException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\Framework\Validation\Exception\ConstraintViolationException;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Throwable;

class ContextComposer
{
    private ContextManager $contextManager;
    private AddressComposer $addressComposer;
    private QuoteErrorMapping $errorMapping;

    public function __construct(
        ContextManager $contextManager,
        AddressComposer $addressComposer,
        QuoteErrorMapping $errorMapping
    ) {
        $this->contextManager = $contextManager;
        $this->addressComposer = $addressComposer;
        $this->errorMapping = $errorMapping;
    }

    public function getContextByCustomerId(string $customerId): SalesChannelContext
    {
        try {
            return $this->contextManager->loadByCustomerId($customerId);
        } catch (Throwable $e) {
            return $this->contextManager->getSalesContext();
        }
    }

    /**
     * Will not do anything if cart is missing customer external ID
     *
     * @throws ShopgateLibraryException
     */
    public function addCustomerAddress(ShopgateCartBase $base, SalesChannelContext $channel): SalesChannelContext
    {
        $addressBag = $this->addressComposer->createAddressSwitchData($base, $channel);
        try {
            // making sure that 2 address IDs are different from each other
            if (count(array_unique($addressBag)) === 2) {
                // dirty hack because of some validations bug that causes to keep billing address ID in search criteria
                $this->contextManager->switchContext(
                    new RequestDataBag(
                        [SalesChannelContextService::BILLING_ADDRESS_ID => $addressBag[SalesChannelContextService::BILLING_ADDRESS_ID]]
                    ),
                    $channel
                );
                $newContext = $this->contextManager->switchContext(
                    new RequestDataBag(
                        [SalesChannelContextService::SHIPPING_ADDRESS_ID => $addressBag[SalesChannelContextService::SHIPPING_ADDRESS_ID]]
                    ),
                    $channel
                );
            } else {
                $newContext = $this->contextManager->switchContext(new RequestDataBag($addressBag), $channel);
            }
        } catch (ConstraintViolationException $exception) {
            throw $this->errorMapping->mapConstraintError($exception);
        }

        return $newContext;
    }

    public function addActivePayment(string $uid, SalesChannelContext $context): SalesChannelContext
    {
        $dataBag = [SalesChannelContextService::PAYMENT_METHOD_ID => $uid];

        return $this->contextManager->switchContext(new RequestDataBag($dataBag), $context);
    }

    public function changeLanguage(string $languageId, SalesChannelContext $context): SalesChannelContext
    {
        $dataBag = [SalesChannelContextService::LANGUAGE_ID => $languageId];

        return $this->contextManager->switchContext(new RequestDataBag($dataBag), $context);
    }
}
