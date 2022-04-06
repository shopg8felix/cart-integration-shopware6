<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order\Events;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Contracts\EventDispatcher\Event;

class AfterCheckCartEvent extends Event
{
    private SalesChannelContext $context;
    private array $result;

    /**
     * @param SalesChannelContext $context
     * @param array $result
     */
    public function __construct(SalesChannelContext $context, array $result)
    {
        $this->context = $context;
        $this->result = $result;
    }

    /**
     * @return SalesChannelContext
     */
    public function getContext(): SalesChannelContext
    {
        return $this->context;
    }

    /**
     * @return array
     */
    public function getResult(): array
    {
        return $this->result;
    }

    /**
     * @param array $result
     */
    public function setResult(array $result): void
    {
        $this->result = $result;
    }
}
