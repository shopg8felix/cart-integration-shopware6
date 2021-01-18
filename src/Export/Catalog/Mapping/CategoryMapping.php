<?php

namespace Shopgate\Shopware\Export\Catalog\Mapping;

use Shopgate\Shopware\Exceptions\MissingContextException;
use Shopgate\Shopware\Storefront\ContextManager;
use Shopgate_Model_Catalog_Category;
use Shopgate_Model_Media_Image;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\System\SalesChannel\Aggregate\SalesChannelDomain\SalesChannelDomainEntity;

class CategoryMapping extends Shopgate_Model_Catalog_Category
{
    /** @var CategoryEntity */
    protected $item;
    /** @var null */
    private $parentId;
    /** @var null | int */
    private $maxPosition;
    /** @var ContextManager */
    private $contextManager;

    /**
     * @param ContextManager $contextManager
     */
    public function __construct(ContextManager $contextManager)
    {
        $this->contextManager = $contextManager;
        parent::__construct();
    }

    /**
     * @param int $position - position of the max category element
     *
     * @return $this
     */
    public function setMaximumPosition(int $position): CategoryMapping
    {
        $this->maxPosition = $position;

        return $this;
    }

    /**
     * Generate data dom object by firing a method array
     *
     * @return $this
     */
    public function generateData(): CategoryMapping
    {
        foreach ($this->fireMethods as $method) {
            $this->{$method}($this->item);
        }

        return $this;
    }

    /**
     * Set category id
     */
    public function setUid(): void
    {
        parent::setUid($this->item->getId());
    }

    /**
     * Set category sort order
     */
    public function setSortOrder(): void
    {
        parent::setSortOrder($this->getMaximumPosition() - $this->item->getAutoIncrement());
    }

    /**
     * @return null | int
     */
    public function getMaximumPosition(): ?int
    {
        return $this->maxPosition;
    }

    /**
     * Set category name
     */
    public function setName(): void
    {
        parent::setName($this->item->getName());
    }

    /**
     * Set parent category id
     */
    public function setParentUid(): void
    {
        parent::setParentUid($this->item->getId() !== $this->parentId ? $this->item->getParentId() : null);
    }

    /**
     * Category link in shop
     * @throws MissingContextException
     */
    public function setDeeplink(): void
    {
        parent::setDeeplink($this->getDeepLinkUrl($this->item));
    }

    /**
     * @param CategoryEntity $category
     * @return string
     * @throws MissingContextException
     */
    private function getDeepLinkUrl(CategoryEntity $category): string
    {
        $channel = $this->contextManager->getSalesContext()->getSalesChannel();
        $entityList = $category->getSeoUrls()
            ? $category->getSeoUrls()->filterBySalesChannelId($channel->getId())
            : null;
        if ($entityList && $entity = $entityList->first()) {
            // intentional use of get, URL can be null which throws Shopware exception
            if ($entity->get('url')) {
                return $entity->getUrl();
            }
            if (null !== $channel->getDomains()) {
                $domainCollection = $channel->getDomains()->filterByProperty('salesChannelId', $channel->getId());
                /** @var null|SalesChannelDomainEntity $domain */
                $domain = $domainCollection->first();
                return $domain ? $domain->getUrl() . $entity->getPathInfo() : '';
            }
        }
        return '';
    }

    /**
     * Seems like SW6 shows sub-cat products
     * by default, don't see a setting for this
     */
    public function setIsAnchor(): void
    {
        parent::setIsAnchor(true);
    }

    /**
     * @param string $parentId
     *
     * @return $this
     */
    public function setParentId(string $parentId): CategoryMapping
    {
        $this->parentId = $parentId;

        return $this;
    }

    /**
     * Set category image
     */
    public function setImage(): void
    {
        if ($this->item->getMediaId()) {
            $imageItem = new Shopgate_Model_Media_Image();
            $media = $this->item->getMedia();
            $imageItem->setUid($media ? $media->getId() : 1);
            $imageItem->setSortOrder(1);
            $imageItem->setUrl($media ? $media->getUrl() : '');
            $imageItem->setTitle($media && $media->getTitle() ? $media->getTitle() : $this->item->getName());
            $imageItem->setAlt($media && $media->getAlt() ? $media->getAlt() : '');

            parent::setImage($imageItem);
        }
    }

    /**
     * Set active state
     */
    public function setIsActive(): void
    {
        parent::setIsActive($this->item->getActive());
    }
}
