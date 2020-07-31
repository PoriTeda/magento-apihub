<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\AdvancedSalesRule\Model\Indexer\SalesRule\Action;

/**
 * Class Full
 */
class Full extends \Magento\AdvancedSalesRule\Model\Indexer\SalesRule\AbstractAction
{
    /**
     * Refresh entities index
     *
     * @return $this
     */
    public function execute()
    {
        $this->setActionIds($this->ruleCollection->load()->getAllIds());
        $this->reindex(true);
        return $this;
    }
}
