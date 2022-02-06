<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order\Payment;

use Shopgate\Shopware\System\Log\LoggerInterface;
use ShopgateCartBase;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;

class PaymentComposer
{
    private PaymentBridge $paymentBridge;
    private PaymentMapping $paymentMapping;
    private LoggerInterface $logger;

    public function __construct(PaymentBridge $paymentBridge, PaymentMapping $paymentMapping, LoggerInterface $logger)
    {
        $this->paymentBridge = $paymentBridge;
        $this->paymentMapping = $paymentMapping;
        $this->logger = $logger;
    }

    public function mapIncomingPayment(ShopgateCartBase $sgCart, SalesChannelContext $context): string
    {
        $methods = $this->paymentBridge->getAvailableMethods($context);
        $this->logger->debug('Payment methods available to this cart:');
        $this->logger->debug(print_r(array_map(static function (PaymentMethodEntity $entity) {
            return [
                'id' => $entity->getId(),
                'name' => $entity->getTranslation('name') ?: $entity->getName(),
                'handler' => $entity->getHandlerIdentifier()
            ];
        }, $methods->getElements()), true));

        return $this->paymentMapping->mapPayment($sgCart, $methods);
    }

    public function isPaid(?OrderTransactionCollection $transactions): bool
    {
        return $transactions && $transactions->filterByState(OrderTransactionStates::STATE_PAID)->count() > 0;
    }

    public function setToPaid(
        ?OrderTransactionCollection $transactions,
        SalesChannelContext $context
    ): ?StateMachineStateEntity {
        $transaction = $this->getActualTransaction($transactions);

        return $transaction ? $this->paymentBridge->setOrderToPaid($transaction->getId(), $context) : null;
    }

    private function getActualTransaction(?OrderTransactionCollection $transactions): ?OrderTransactionEntity
    {
        return $transactions ? $transactions->last() : null;
    }
}
