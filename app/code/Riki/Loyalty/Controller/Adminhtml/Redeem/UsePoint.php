<?php

namespace Riki\Loyalty\Controller\Adminhtml\Redeem;

use Riki\Loyalty\Model\RewardQuote;
use Magento\Framework\Controller\ResultFactory;

class UsePoint extends \Riki\Loyalty\Controller\Adminhtml\Redeem
{
    public function execute()
    {
        $cartId = (int) $this->_request->getParam('cart_id');
        $usePointAmount = $this->_request->getParam('used_points');
        switch ((int) $this->_request->getParam('option')) {
            case RewardQuote::USER_DO_NOT_USE_POINT:
                $point = $this->_checkoutRewardPoint->removeRewardPoint($cartId);
                break;
            case RewardQuote::USER_USE_ALL_POINT:
                $point = $this->_checkoutRewardPoint->useAllPoint($cartId);
                break;
            case RewardQuote::USER_USE_SPECIFIED_POINT:
                $point = $this->_checkoutRewardPoint->usePoint($cartId, $usePointAmount);
                break;
            default:
                $point = 0;
                break;
        }
        $response = ['point' => $point, 'error' => false];
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $result->setData($response);
        return $result;
    }
}