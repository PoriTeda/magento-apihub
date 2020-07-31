<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCard\Model\Validator;

use Magento\GiftCard\Model\Catalog\Product\Type\Giftcard;
use Magento\Quote\Model\Quote\Item;

/**
 * Class Discount Validator
 * @package Magento\GiftCard\Model\Validator
 */
class Discount implements \Zend_Validate_Interface
{
    /**
     * @var []
     */
    protected $messages;

    /**
     * Define if we can apply discount to current item
     *
     * @param Item $item
     * @return bool
     */
    public function isValid($item)
    {
        if (Giftcard::TYPE_GIFTCARD == $item->getProductType()) {
            return false;
        }
        return true;
    }

    /**
     * @return array
     */
    public function getMessages()
    {
        return [];
    }
}
