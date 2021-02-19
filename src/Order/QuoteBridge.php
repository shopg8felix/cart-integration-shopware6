<?php

namespace Shopgate\Shopware\Order;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\SalesChannel\CartItemAddRoute;
use Shopware\Core\Checkout\Cart\SalesChannel\CartLoadRoute;
use Shopware\Core\Checkout\Cart\SalesChannel\CartOrderRoute;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

class QuoteBridge
{
    /** @var CartOrderRoute */
    private $cartOrderRoute;
    /** @var CartLoadRoute */
    private $cartLoadRoute;
    /** @var CartItemAddRoute */
    private $cartItemAddRoute;

    /**
     * @param CartOrderRoute $cartOrderRoute
     * @param CartLoadRoute $cartLoadRoute
     * @param CartItemAddRoute $cartItemAddRoute
     */
    public function __construct(
        CartOrderRoute $cartOrderRoute,
        CartLoadRoute $cartLoadRoute,
        CartItemAddRoute $cartItemAddRoute
    ) {
        $this->cartOrderRoute = $cartOrderRoute;
        $this->cartLoadRoute = $cartLoadRoute;
        $this->cartItemAddRoute = $cartItemAddRoute;
    }

    /**
     * @param SalesChannelContext $context
     * @return Cart
     */
    public function loadCartFromContext(SalesChannelContext $context): Cart
    {
        return $this->cartLoadRoute->load(new Request(), $context)->getCart();
    }

    /**
     * @param Request $request
     * @param Cart $cart
     * @param SalesChannelContext $context
     * @return Cart
     */
    public function addLineItemToQuote(Request $request, Cart $cart, SalesChannelContext $context): Cart
    {
        return $this->cartItemAddRoute->add($request, $cart, $context, null)->getCart();
    }

    /**
     * @param Cart $cart
     * @param SalesChannelContext $context
     * @param RequestDataBag|null $data
     * @return OrderEntity
     */
    public function createOrder(Cart $cart, SalesChannelContext $context, ?RequestDataBag $data = null): OrderEntity
    {
        return $this->cartOrderRoute->order($cart, $context, $data)->getOrder();
    }
}