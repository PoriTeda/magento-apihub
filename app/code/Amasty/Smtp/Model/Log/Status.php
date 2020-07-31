<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2019 Amasty (https://www.amasty.com)
 * @package Amasty_Smtp
 */


namespace Amasty\Smtp\Model\Log;

use Amasty\Smtp\Model\Log;
use Magento\Framework\Data\OptionSourceInterface;

class Status implements OptionSourceInterface
{
    protected $options = null;

    public function toOptionArray()
    {
        if (null == $this->options) {
            $this->options = [
                ['value' => Log::STATUS_PENDING, 'label' => __('Pending')],
                ['value' => Log::STATUS_SENT, 'label' => __('Successfully Sent')],
                ['value' => Log::STATUS_FAILED, 'label' => __('Failed')],
            ];
        }

        return $this->options;
    }
}
