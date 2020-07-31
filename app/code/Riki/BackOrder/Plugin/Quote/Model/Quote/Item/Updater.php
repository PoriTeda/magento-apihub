<?php

namespace Riki\BackOrder\Plugin\Quote\Model\Quote\Item;

use \Magento\Framework\Exception\LocalizedException;

class Updater
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
     * @param \Magento\Quote\Model\Quote\Item\Updater $subject
     * @param \Magento\Quote\Model\Quote\Item $item
     * @param array $info
     * @return array
     * @throws LocalizedException
     */
    public function beforeUpdate(
        \Magento\Quote\Model\Quote\Item\Updater $subject,
        \Magento\Quote\Model\Quote\Item $item,
        array $info
    )
    {
        if(!isset($info['action']) || $info['action'] != 'remove'){
            if (!isset($info['qty'])) {
                throw new LocalizedException(__('The qty value is required to update quote item.'));
            }

            $validateResult = $this->_adminHelper->validateItem($item, $info['qty']);

            if(is_string($validateResult)){
                throw new LocalizedException(__($validateResult));
            }
        }
        return [$item, $info];
    }
}