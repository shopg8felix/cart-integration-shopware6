<?php

namespace Shopgate\Shopware\Shopgate\Order;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class ShopgateOrderEntity extends Entity
{
    use EntityIdTrait;

    private $shopwareOrderId;
    private $salesChannelId;
    private $shopgateOrderNumber;
    private $isSent;
    private $isCancelled;
    private $isPaid;
    private $isTest;
    private $receivedData;

    /**
     * @return string
     */
    public function getShopwareOrderId(): string
    {
        return $this->shopwareOrderId;
    }

    /**
     * @param string $shopwareOrderId
     * @return ShopgateOrderEntity
     */
    public function setShopwareOrderId(string $shopwareOrderId): ShopgateOrderEntity
    {
        $this->shopwareOrderId = $shopwareOrderId;
        return $this;
    }

    /**
     * @return string
     */
    public function getSalesChannelId(): string
    {
        return $this->salesChannelId;
    }

    /**
     * @param string $salesChannelId
     * @return ShopgateOrderEntity
     */
    public function setSalesChannelId(string $salesChannelId): ShopgateOrderEntity
    {
        $this->salesChannelId = $salesChannelId;
        return $this;
    }

    /**
     * @return string
     */
    public function getShopgateOrderNumber(): string
    {
        return $this->shopgateOrderNumber;
    }

    /**
     * @param string $shopgateOrderNumber
     * @return ShopgateOrderEntity
     */
    public function setShopgateOrderNumber(string $shopgateOrderNumber): ShopgateOrderEntity
    {
        $this->shopgateOrderNumber = $shopgateOrderNumber;
        return $this;
    }

    /**
     * @return bool
     */
    public function getIsSent(): bool
    {
        return $this->isSent;
    }

    /**
     * @param bool $isSent
     * @return ShopgateOrderEntity
     */
    public function setIsSent(bool $isSent): ShopgateOrderEntity
    {
        $this->isSent = $isSent;
        return $this;
    }

    /**
     * @return bool
     */
    public function getIsCancelled(): bool
    {
        return $this->isCancelled;
    }

    /**
     * @param bool $isCancelled
     * @return ShopgateOrderEntity
     */
    public function setIsCancelled(bool $isCancelled): ShopgateOrderEntity
    {
        $this->isCancelled = $isCancelled;
        return $this;
    }

    /**
     * @return bool
     */
    public function getIsPaid(): bool
    {
        return $this->isPaid;
    }

    /**
     * @param bool $isPaid
     * @return ShopgateOrderEntity
     */
    public function setIsPaid(bool $isPaid): ShopgateOrderEntity
    {
        $this->isPaid = $isPaid;
        return $this;
    }

    /**
     * @return bool
     */
    public function getIsTest(): bool
    {
        return $this->isTest;
    }

    /**
     * @param bool $isTest
     * @return ShopgateOrderEntity
     */
    public function setIsTest(bool $isTest): ShopgateOrderEntity
    {
        $this->isTest = $isTest;
        return $this;
    }

    /**
     * @return object
     */
    public function getReceivedData(): object
    {
        return $this->receivedData;
    }

    /**
     * @param object $receivedData
     * @return ShopgateOrderEntity
     */
    public function setReceivedData(object $receivedData): ShopgateOrderEntity
    {
        $this->receivedData = $receivedData;
        return $this;
    }
}
