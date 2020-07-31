<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2016 Amasty (https://www.amasty.com)
 * @package Amasty_Promo
 */


namespace Amasty\Promo\Helper;

class Item extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->_storeManager = $storeManager;
    }

    public function getRuleId(\Magento\Quote\Model\Quote\Item $item)
    {
        if (!($ruleId = $item->getData('ampromo_rule_id')))
        {
            $buyRequest = $item->getBuyRequest();

            $ruleId = isset($buyRequest['options']['ampromo_rule_id'])
                ? $buyRequest['options']['ampromo_rule_id'] : null;

            $item->setData('ampromo_rule_id', $ruleId);
        }
        return $ruleId;
    }

    public function isPromoItem(\Magento\Quote\Model\Quote\Item $item)
    {
        if ($this->_storeManager->getStore()->getCode() == \Magento\Store\Model\Store::ADMIN_CODE)
            return false;

        return $this->getRuleId($item) !== null;
    }
}
