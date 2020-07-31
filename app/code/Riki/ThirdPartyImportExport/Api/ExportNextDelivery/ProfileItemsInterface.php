<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\ThirdPartyImportExport\Api\ExportNextDelivery;

/**
 * Interface ProfileItemsInterface
 */
interface ProfileItemsInterface
{
    /**
     * @param \Riki\ThirdPartyImportExport\Api\ExportNextDelivery\ProfileItemInterface[] $items
     * @return $this
     */
    public function setItems(array $items);

    /**
     * @return \Riki\ThirdPartyImportExport\Api\ExportNextDelivery\ProfileItemInterface[]
     */
    public function getItems();
}
