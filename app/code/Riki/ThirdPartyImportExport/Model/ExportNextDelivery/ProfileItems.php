<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\ThirdPartyImportExport\Model\ExportNextDelivery;

use Riki\ThirdPartyImportExport\Api\ExportNextDelivery\ProfileItemsInterface;

class ProfileItems implements ProfileItemsInterface
{
    /**
     * @var []
     */
    private $items;

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function setItems(array $items)
    {
        $this->items = $items;

        return $this;
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function getItems()
    {
        return $this->items;
    }
}
