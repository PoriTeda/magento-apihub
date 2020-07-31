<?php

namespace Riki\Loyalty\Controller\Reward;

use Magento\Framework\Controller\ResultFactory;
use Riki\Loyalty\Model\ConsumerDb\CustomerSub;
use Riki\Loyalty\Model\RewardQuote;
use Magento\Framework\Exception\NoSuchEntityException;

class Setting extends \Riki\Loyalty\Controller\Reward
{
    /**
     * Update user reward setting into consumerDB
     *
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function execute()
    {
        if (!$this->getRequest()->isAjax()) {
            $backTo = $this->_redirect->getRefererUrl();
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            return $resultRedirect->setUrl($backTo);
        }
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $response['err'] = true;
        if (!$this->_formKeyValidator->validate($this->getRequest())) {
            $response['msg'] = __('Form key is not valid.');
            return $resultJson->setData($response);
        }
        /** @var \Magento\Customer\Model\Customer $customer */
        $customer = $this->_registry->registry('current_customer');
        $customerCode = $customer->getData('consumer_db_id');
        $updateData = [
            CustomerSub::USE_POINT_TYPE => $this->getRequest()->getParam('reward_user_setting')
        ];
        if ($this->getRequest()->getParam('reward_user_setting') == RewardQuote::USER_USE_SPECIFIED_POINT) {
            $pointRedeem = $this->getRequest()->getParam('reward_user_redeem');
            if (ctype_digit($pointRedeem)) {
                $updateData[CustomerSub::USE_POINT_AMOUNT] = $pointRedeem;
            } else {
                $response['msg'] = __('Please enter the Arabic numeral bigger than 1');
                return $resultJson->setData($response);
            }
        }
        $apiResponse = $this->_customerSub->setCustomerSub($customerCode, $updateData);
        if ($apiResponse['error']) {
            $response['msg'] = __('Error while saving data.');
        } else {
            $response['err'] = false;
            $response['msg'] = __('You saved the settings.');
            //redeem active cart
            try {
                $sharedStoreIds = [$this->_storeManager->getStore()->getId()];
                $quote = $this->_quoteRepository->getActiveForCustomer($customer->getId(), $sharedStoreIds);
                $cartId = $quote->getId();
                $rewardQuote = $this->_rewardQuoteFactory->create()->load($cartId, 'quote_id');
                if (!$rewardQuote->getId()) {
                    return $resultJson->setData($response);
                }
                $userSetting = $this->_rewardManagement->getRewardUserSetting($customerCode);
                $this->_checkoutRewardPoint->applyRewardPoint(
                    $cartId,
                    $userSetting['use_point_amount'],
                    $userSetting['use_point_type']
                );
            } catch (NoSuchEntityException $e) {
                $response['quote_msg'] = $e->getMessage();
            }
        }
        return $resultJson->setData($response);
    }
}