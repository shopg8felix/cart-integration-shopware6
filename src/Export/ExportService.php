<?php

namespace Shopgate\Shopware\Export;

use Shopgate\Shopware\Exceptions\MissingContextException;
use Shopgate\Shopware\Export\Catalog\Categories;
use Shopgate\Shopware\Utility\LoggerInterface;
use Shopgate_Model_Catalog_Category;
use ShopgateCustomer;
use ShopgateLibraryException;

class ExportService
{
    /**
     * @var LoggerInterface
     */
    private $log;
    /**
     * @var Categories
     */
    private $categoryHelper;
    /** @var ConfigExport */
    private $configExport;
    /** @var Customer */
    private $customerHelper;

    /**
     * @param LoggerInterface $logger
     * @param Categories $categoryHelper
     * @param ConfigExport $configExport
     * @param Customer $customerHelper
     */
    public function __construct(
        LoggerInterface $logger,
        Categories $categoryHelper,
        ConfigExport $configExport,
        Customer $customerHelper
    ) {
        $this->log = $logger;
        $this->categoryHelper = $categoryHelper;
        $this->configExport = $configExport;
        $this->customerHelper = $customerHelper;
    }

    /**
     * @param null | string $limit
     * @param null | string $offset
     * @param string[] $ids
     * @return Shopgate_Model_Catalog_Category[]
     * @throws MissingContextException
     */
    public function getCategories($limit = null, $offset = null, array $ids = []): array
    {
        $this->log->info('Start Category Export...');

        $export = $this->categoryHelper->buildCategoryTree($ids, $limit, $offset);
        $this->log->info('End Category-Tree Build...');
        $this->log->info('Finished Category Export...');

        return $export;
    }

    /**
     * @param string $user
     * @param string $password
     * @return ShopgateCustomer
     * @throws MissingContextException
     * @throws ShopgateLibraryException
     */
    public function getCustomer(string $user, string $password): ShopgateCustomer
    {
        return $this->customerHelper->getCustomerData($user, $password);
    }

    /**
     * Sets plugin version globally before action handler runs
     */
    public function definePluginVersion(): void
    {
        define('SHOPGATE_PLUGIN_VERSION', $this->configExport->getShopgatePluginVersion());
    }

    /**
     * @return string[]
     */
    public function getInfo(): array
    {
        return [
            'Shopware core version' => $this->configExport->getShopwareVersion()
        ];
    }

    /**
     * @return array[]
     */
    public function getSettings(): array
    {
        return [
            'customer_groups' => $this->configExport->getCustomerGroups(),
            'tax' => $this->configExport->getTaxSettings(),
            'allowed_address_countries' => $this->configExport->getAllowedBillingCountries(),
            'allowed_shipping_countries' => $this->configExport->getAllowedShippingCountries(),
            'payment_methods' => $this->configExport->getAllPaymentMethods(),
        ];
    }
}
