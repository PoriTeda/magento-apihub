<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Framework\ForeignKey;

/**
 * Interface \Magento\Framework\ForeignKey\ConfigInterface
 *
 */
interface ConfigInterface
{
    /**
     * Get constraints by reference table name
     *
     * @param string $referenceTableName
     * @return ConstraintInterface[]
     */
    public function getConstraintsByReferenceTableName($referenceTableName);

    /**
     * Get constraints by table name
     *
     * @param string $tableName
     * @return ConstraintInterface[]
     */
    public function getConstraintsByTableName($tableName);
}
