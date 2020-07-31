<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Model\Entity;

/**
 * Interface RemoverInterface
 */
interface RemoverInterface
{
    /**
     * @param object $entity
     * @param string $versionId
     * @return boolean
     */
    public function deleteEntity($entity, $versionId);
}
