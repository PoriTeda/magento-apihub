<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\GiftWrapping\Block\Cart\Item\Renderer\Actions;

use Magento\Quote\Model\Quote\Item\AbstractItem;
use Magento\GiftMessage\Block\Cart\Item\Renderer\Actions\LayoutProcessorInterface;

class ItemIdProcessor implements LayoutProcessorInterface
{
    /**
     * Adds item ID to giftOptionsCartItem configuration and name
     *
     * @param array $jsLayout
     * @param AbstractItem $item
     * @return array
     */
    public function process($jsLayout, AbstractItem $item)
    {
        if (isset($jsLayout['components']['giftOptionsCartItem-' . $item->getId()]['children']['giftWrapping'])) {
            if (
            !isset($jsLayout['components']['giftOptionsCartItem-' . $item->getId()]
                ['children']['giftWrapping']['config'])
            ) {
                $jsLayout['components']['giftOptionsCartItem-' . $item->getId()]
                ['children']['giftWrapping']['config'] = [];
            }
            $jsLayout['components']['giftOptionsCartItem-' . $item->getId()]
            ['children']['giftWrapping']['config']['itemId'] = $item->getId();
            if($item->getProduct()->getGiftWrapping()){
                $jsLayout['components']['giftOptionsCartItem-' . $item->getId()]
                ['children']['giftWrapping']['config']['availableDesignIds'] = $item->getProduct()->getGiftWrapping();
            }else{
                $jsLayout['components']['giftOptionsCartItem-' . $item->getId()]
                ['children']['giftWrapping']['config']['availableDesignIds'] = [''];
            }

        }

        return $jsLayout;
    }
}
