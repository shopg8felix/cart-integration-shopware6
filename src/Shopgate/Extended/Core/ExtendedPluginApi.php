<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Shopgate\Extended\Core;

use League\Flysystem\FileNotFoundException;
use League\Flysystem\FilesystemInterface;
use ShopgateLibraryException;
use ShopgatePluginApi;
use ShopgatePluginApiResponseAppJson;

class ExtendedPluginApi extends ShopgatePluginApi
{
    protected FilesystemInterface $privateFileSystem;

    public function setPrivateFileSystem(FilesystemInterface $filesystem): ExtendedPluginApi
    {
        $this->privateFileSystem = $filesystem;

        return $this;
    }

    /**
     * Is needed for XML export calls
     */
    public function setPreventResponse(bool $prevent): ExtendedPluginApi
    {
        $this->preventResponseOutput = $prevent;

        return $this;
    }

    public function handleRequest(array $data = array())
    {
        parent::handleRequest($data);

        if (!$this->preventResponseOutput) {
            return;
        }

        try {
            $this->response = new ExtendedApiResponseXmlExport($this->trace_id);
            $this->response->setData(
                $this->privateFileSystem->readStream($this->responseData)
            );
        } catch (FileNotFoundException $e) {
            $this->response = new ShopgatePluginApiResponseAppJson($this->trace_id);
            $this->response->markError(ShopgateLibraryException::FILE_READ_WRITE_ERROR, $e->getMessage());
        }

        $this->response->send();
    }

    protected function getCategories(): void
    {
        parent::getCategories();
        $this->setPreventResponse(true);
    }

    protected function getItems(): void
    {
        parent::getItems();
        $this->setPreventResponse(true);
    }

    protected function getReviews(): void
    {
        parent::getReviews();
        $this->setPreventResponse(true);
    }
}