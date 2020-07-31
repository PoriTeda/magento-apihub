<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Model\Entity;

/**
 * Interface PersisterInterface
 */
interface PersisterInterface
{
    /**
     * @param object $entity
     * @param string $versionId
     * @return bool mixed
     */
    public function saveEntity($entity, $versionId);
}
