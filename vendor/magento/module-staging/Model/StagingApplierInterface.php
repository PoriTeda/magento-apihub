<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Staging\Model;

/**
 * Interface StagingApplierInterface
 */
interface StagingApplierInterface
{
    /**
     * Runs applying version to entity
     *
     * @param array $entityIds
     * @return void
     */
    public function execute(array $entityIds);
}
