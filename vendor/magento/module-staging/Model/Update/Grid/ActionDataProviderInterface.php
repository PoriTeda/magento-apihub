<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Staging\Model\Update\Grid;

/**
 * Interface ActionDataProviderInterface
 */
interface ActionDataProviderInterface
{
    /**
     * Get Button data for staging entity update grid
     *
     * @param array $item
     * @return array
     */
    public function getActionData($item);
}
