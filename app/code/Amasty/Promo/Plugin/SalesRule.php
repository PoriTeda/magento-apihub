<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2016 Amasty (https://www.amasty.com)
 * @package Amasty_Promo
 */


namespace Amasty\Promo\Plugin;

class SalesRule
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;


    public function __construct(
        \Magento\Framework\Registry $registry
    ) {
        $this->_coreRegistry = $registry;
    }


    public function afterSave(\Magento\SalesRule\Model\Rule $subject, $result)
    {
        $this->_coreRegistry->register('ampromo_current_salesrule', $subject, true);

        return $result;
    }
}
