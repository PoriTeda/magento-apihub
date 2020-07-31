<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedRule\Model\Condition;

/**
 * Interface \Magento\AdvancedRule\Model\Condition\FilterTextGeneratorInterface
 *
 */
interface FilterTextGeneratorInterface
{
    /**
     * @param \Magento\Framework\DataObject $input
     * @return string[]
     */
    public function generateFilterText(\Magento\Framework\DataObject $input);
}
