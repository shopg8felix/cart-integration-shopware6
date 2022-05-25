<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order;

use Shopgate\Shopware\Order\Customer\OrderCustomerComposer;
use Shopgate\Shopware\Order\Events\AfterCheckCartEvent;
use Shopgate\Shopware\Order\Events\BeforeCheckCartEvent;
use Shopgate\Shopware\Order\LineItem\LineItemComposer;
use Shopgate\Shopware\Order\Payment\PaymentComposer;
use Shopgate\Shopware\Order\Quote\QuoteBridge;
use Shopgate\Shopware\Order\Shipping\ShippingComposer;
use Shopgate\Shopware\Shopgate\Extended\ExtendedCart;
use Shopgate\Shopware\Storefront\ContextManager;
use ShopgateLibraryException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class CartComposer
{
    private ShippingComposer $shippingComposer;
    private ContextComposer $contextComposer;
    private ContextManager $contextManager;
    private LineItemComposer $lineItemComposer;
    private QuoteBridge $quoteBridge;
    private PaymentComposer $paymentComposer;
    private OrderCustomerComposer $orderCustomerComposer;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        ShippingComposer $shippingComposer,
        ContextManager $contextManager,
        ContextComposer $contextComposer,
        LineItemComposer $lineItemComposer,
        QuoteBridge $quoteBridge,
        PaymentComposer $paymentComposer,
        OrderCustomerComposer $orderCustomerComposer,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->contextManager = $contextManager;
        $this->lineItemComposer = $lineItemComposer;
        $this->quoteBridge = $quoteBridge;
        $this->shippingComposer = $shippingComposer;
        $this->contextComposer = $contextComposer;
        $this->paymentComposer = $paymentComposer;
        $this->orderCustomerComposer = $orderCustomerComposer;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @throws ShopgateLibraryException
     */
    public function checkCart(ExtendedCart $sgCart): array
    {
        $sgCart->invalidateCoupons();
        $customerId = $sgCart->getExternalCustomerId();
        if ($sgCart->isGuest() && $sgCart->getMail()) {
            $customerId = $this->orderCustomerComposer->getOrCreateGuestCustomerByEmail(
                $sgCart->getMail(),
                $sgCart,
                $this->contextManager->getSalesContext()
            )->getId();
        }
        // load desktop cart, duplicate its context, add info to context & create new cart based on it
        $initContext = $this->contextComposer->getContextByCustomerId($customerId ?? '');
        $duplicatedContext = $this->contextManager->duplicateContextWithNewToken($initContext, $customerId ?? null);
        $this->eventDispatcher->dispatch(new BeforeCheckCartEvent($sgCart, $duplicatedContext));
        $cleanCartContext = $this->contextComposer->addCustomerAddress($sgCart, $duplicatedContext);

        // payment
        $paymentId = $this->paymentComposer->mapIncomingPayment($sgCart, $cleanCartContext);
        $context = $this->contextComposer->addActivePayment($paymentId, $cleanCartContext);

        // line items
        $initialCart = $this->quoteBridge->loadCartFromContext($context);
        $lineItems = $this->lineItemComposer->mapIncomingLineItems($sgCart);
        $updatedCart = $this->lineItemComposer->addLineItemsToCart($initialCart, $context, $lineItems);
        $lineItemExport = $this->lineItemComposer->mapOutgoingLineItems($updatedCart, $sgCart);

        // ship
        $deliveries = $this->shippingComposer->getCalculatedDeliveries($updatedCart, $context);

        $result = [
                'currency' => $context->getCurrency()->getIsoCode(),
                'payment_methods' => $this->paymentComposer->mapOutgoingPayments($context),
                'shipping_methods' => $this->shippingComposer->mapOutgoingShipping($deliveries)
            ]
            + $this->orderCustomerComposer->mapOutgoingCartCustomer($context)
            + $lineItemExport;

        $result = $this->eventDispatcher->dispatch(new AfterCheckCartEvent($result, $context))->getResult();

        $this->quoteBridge->deleteCart($context); // delete newly created cart
        $this->contextManager->resetContext($initContext); // revert back to desktop cart

        return $result;
    }
}
