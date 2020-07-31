<?php

namespace Riki\BackOrder\Plugin\Quote\Model;

use \Magento\Framework\Exception\LocalizedException;

class Quote
{

    protected $_adminHelper;

    /**
     * @param \Riki\BackOrder\Helper\Admin $adminHelper
     */
    public function __construct(
        \Riki\BackOrder\Helper\Admin $adminHelper
    ){
        $this->_adminHelper = $adminHelper;
    }

    /**
     * validate quote item update with back-order rule
     *
     * @param \Magento\Quote\Model\Quote $subject
     * @param $itemId
     * @param $buyRequest
     * @param null $params
     * @return array
     * @throws LocalizedException
     */
    public function beforeUpdateItem(
        \Magento\Quote\Model\Quote $subject,
        $itemId,
        $buyRequest,
        $params = null
    )
    {
        $item = $subject->getItemById($itemId);
        if (!$item) {
            throw new LocalizedException(
                __('This is the wrong quote item id to update configuration.')
            );
        }

        $validateResult = $this->_adminHelper->validateItemUpdate($item, $buyRequest->getQty());

        if(is_string($validateResult)){
            throw new LocalizedException(__($validateResult));
        }

        return [$itemId, $buyRequest, $params];
    }
}