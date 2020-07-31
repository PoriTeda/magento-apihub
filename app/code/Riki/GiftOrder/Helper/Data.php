<?php

namespace Riki\GiftOrder\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const CONFIG_GIFT_ORDER_ENABLE = "giftorder/general/enable";
    const CONFIG_GIFT_ORDER_GIFT_OPTION = "giftorder/general/gift_options";

    protected $_giftMessageCollectionFactory;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\GiftMessage\Model\ResourceModel\Message\CollectionFactory $giftMessageCollectionFactory
    ) {
        $this->_giftMessageCollectionFactory = $giftMessageCollectionFactory;
        parent::__construct($context);
    }

    public function getGiftMessageOption()
    {
        return $this->_giftMessageCollectionFactory->create();
    }

    public function getConfig($path)
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $config = $this->scopeConfig->getValue($path, $storeScope);
        return $config;
    }

}