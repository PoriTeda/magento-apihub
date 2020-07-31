<?php

namespace Riki\MachineApi\Model\Plugin;

class FreeShipping
{
    protected $_coreRegistry;

    public function __construct(
        \Magento\Framework\Registry $coreRegistry
    )
    {
        $this->_coreRegistry = $coreRegistry;
    }

    /**
     * Machine Maintenance always has free shipping
     *
     * @param $subject
     *
     * @return bool
     */
    public function afterIsFreeShipping(\Magento\OfflineShipping\Model\Quote\Address\FreeShipping $subject, $result)
    {
        if ($this->_coreRegistry->registry('is_machine_api')) {
            return true;
        }

        return $result;
    }
}
