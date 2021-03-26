<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Catalog\Product\Sort;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\InvalidArgumentException;
use Shopgate\Shopware\Catalog\Category\CategoryBridge;
use Shopgate\Shopware\Exceptions\MissingContextException;
use Shopgate\Shopware\Storefront\ContextManager;
use Shopgate\Shopware\System\FileCache;
use Shopgate\Shopware\System\Log\LoggerInterface;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Content\Product\SalesChannel\Listing\AbstractProductListingRoute;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Symfony\Component\HttpFoundation\Request;

class SortTree
{
    public const CACHE_KEY = 'shopgate.sort.tree';
    /** @var ContextManager */
    private $contextManager;
    /** @var CategoryBridge */
    private $categoryBridge;
    /** @var AbstractProductListingRoute */
    private $listingRoute;
    /** @var FileCache */
    private $cache;
    /** @var LoggerInterface */
    private $logger;

    /**
     * @param FileCache $cacheObject
     * @param ContextManager $contextManager
     * @param CategoryBridge $categoryBridge
     * @param AbstractProductListingRoute $listingRoute
     * @param LoggerInterface $logger
     */
    public function __construct(
        FileCache $cacheObject,
        ContextManager $contextManager,
        CategoryBridge $categoryBridge,
        AbstractProductListingRoute $listingRoute,
        LoggerInterface $logger
    ) {
        $this->cache = $cacheObject;
        $this->contextManager = $contextManager;
        $this->categoryBridge = $categoryBridge;
        $this->listingRoute = $listingRoute;
        $this->logger = $logger;
    }

    /**
     * @param string|null $rootCategoryId
     * @return array
     * @throws MissingContextException
     * @throws InvalidArgumentException
     */
    public function getSortTree(?string $rootCategoryId = null): array
    {
        /** @var CacheItemInterface $tree */
        $tree = $this->cache->getItem(self::CACHE_KEY);
        if (!$tree->isHit()) {
            $this->logger->debug('Building new sort order cache');
            $build = $this->build($rootCategoryId);
            $tree->set($build);
            $this->cache->save($tree);
        }
        return $tree->get();
    }

    /**
     * @param null|string $rootCategoryId - provide category id to build from
     * @return array - ['categoryId' => ['productId' => sortNumber]]
     * @throws MissingContextException
     */
    private function build(?string $rootCategoryId): array
    {
        $tree = [];
        if (null === $rootCategoryId) {
            $rootCategoryId = $this->contextManager->getSalesContext()->getSalesChannel()->getNavigationCategoryId();
        }
        $categories = $this->categoryBridge->getChildCategories($rootCategoryId);
        foreach ($categories as $category) {
            $request = new Request();

            if ($orderKey = $this->getSortOrderKey($category)) {
                $request->request->set('order', $orderKey);
            }
            /** @noinspection PhpMethodParametersCountMismatchInspection */
            $result = $this->listingRoute
                ->load($category->getId(), $request, $this->contextManager->getSalesContext(), new Criteria())
                ->getResult();
            $products = $result->getEntities();
            $maxProducts = $products->count();
            $i = 0;
            foreach ($products as $product) {
                $tree[$category->getId()][$product->getId()] = $maxProducts - $i++;
            }
        }

        return $tree;
    }

    /**
     * Retrieves the default key to sort the category by
     *
     * @param CategoryEntity $category
     * @return string|null - e.g. price-asc, topseller
     */
    private function getSortOrderKey(CategoryEntity $category): ?string
    {
        if ($slot = (array)$category->getSlotConfig()) {
            $list = array_values($slot);
            if (is_array($list)) {
                $config = array_merge(...$list);
                if (isset($config['defaultSorting']['value'])) {
                    return $config['defaultSorting']['value'];
                }
            }
        }

        return null;
    }
}
