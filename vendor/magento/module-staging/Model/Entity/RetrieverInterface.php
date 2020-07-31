<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Model\Entity;

use Magento\Framework\DataObject;

/**
 * Interface \Magento\Staging\Model\Entity\RetrieverInterface
 *
 */
interface RetrieverInterface
{
    /**
     * Retrieve entity by entity id
     *
     * @param string $entityId
     * @return DataObject
     */
    public function getEntity($entityId);
}
