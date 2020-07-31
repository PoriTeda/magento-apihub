<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Prize\Model\Prize;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class IsActive
 */
class IsActive implements OptionSourceInterface
{
    /**
     * @var \Riki\Prize\Model\Prize
     */
    protected $_prize;

    /**
     * IsActive constructor.
     * @param \Riki\Prize\Model\Prize $prize
     */
    public function __construct(\Riki\Prize\Model\Prize $prize)
    {
        $this->_prize = $prize;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $availableOptions = $this->_prize->getAvailableStatuses();
        $options = [];
        foreach ($availableOptions as $key => $value) {
            $options[] = [
                'label' => $value,
                'value' => $key,
            ];
        }
        return $options;
    }

    
}
