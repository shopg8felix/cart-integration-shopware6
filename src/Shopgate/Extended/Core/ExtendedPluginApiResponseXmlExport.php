<?php

namespace Shopgate\Shopware\Shopgate\Extended\Core;

use ShopgatePluginApiResponseExport;

class ExtendedPluginApiResponseXmlExport extends ShopgatePluginApiResponseExport
{
    protected array $meta;

    public function setMeta(array $meta): void
    {
        $this->meta = $meta;
    }

    /**
     * @inheritDoc
     */
    protected function getHeaders(): array
    {
        $fileName = str_replace('export/', '', $this->meta['path'] ?? '');
        return [
            'Content-Type: application/xml',
            'Content-Length: ' . $this->meta['size'] ?? '0',
            'Content-Disposition: attachment; filename="' . basename($fileName) . '"'
        ];
    }
}
