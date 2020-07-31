<?php

namespace Riki\Loyalty\Controller\Adminhtml\Reward;

use Riki\Loyalty\Model\Reward;
use Riki\Loyalty\Model\ConsumerDb\ShoppingPoint;
use Magento\Framework\Controller\ResultFactory;

class NewAction extends \Riki\Loyalty\Controller\Adminhtml\Reward
{

    /**
     * Point earn: Case customer complain
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $customerCode = $this->getRequest()->getParam('customer_code');
        /** @var \Riki\Loyalty\Model\Reward $model */
        $model = $this->_rewardFactory->create();
        $arrData = [
            'point_type' => Reward::TYPE_ADJUSTMENT,
            'point' => $this->getRequest()->getParam('amount'),
            'description' => $this->getRequest()->getParam('comment'),
            'wbs_code' => $this->getRequest()->getParam('booking_point_wbs'),
            'account_code' => $this->getRequest()->getParam('booking_point_account'),
            'expiry_period' => abs((int) $this->getRequest()->getParam('expiration_period')),
            'customer_id' => $this->getRequest()->getParam('customer_id'),
            'customer_code' => $customerCode,
            'action_date' => $this->_loyaltyHelper->pointActionDate()
        ];
        if (!$arrData['expiry_period']) {
            $arrData['expiry_period'] = $this->_loyaltyHelper->getDefaultExpiryPeriod();
        }
        $model->setData($arrData);
        //step 1: create point from consumerDB, element must follow sort order
        $consumerData = [
            'pointIssueType' => Reward::TYPE_ADJUSTMENT,
            'description' => $model->getData('description'),
            'pointAmountId' => ShoppingPoint::POINT_AMOUNT_ID,
            'point' => $model->getData('point'),
            'orderNo' => '',
            'scheduledExpiredDate' => $this->_loyaltyHelper->scheduledExpiredDate($model->getData('expiry_period')),
            'serialNo' => '',
            'wbsCode' => $model->getData('wbs_code'),
            'accountCode' => $model->getData('account_code'),
        ];
        $response = $this->_consumerDb->setPoint(ShoppingPoint::REQUEST_TYPE_ALLOCATION, $customerCode, $consumerData);
        //json response
        if ($response['error']) {
            /** @var \Magento\Framework\View\Result\Layout $resultLayout */
            $resultLayout = $this->resultFactory->create(ResultFactory::TYPE_LAYOUT);
            /** @var $messagesBlock \Magento\Framework\View\Element\Messages */
            $messagesBlock = $resultLayout->getLayout()->createBlock('Magento\Framework\View\Element\Messages');
            $messagesBlock->addError($response['msg']);
            $response['html'] = $messagesBlock->toHtml();
        } else {
            //step 2: create point from Magento
            $model->setData('status', Reward::STATUS_SHOPPING_POINT);
            $model->save();
        }
        $result = $this->_resultJsonFactory->create();
        $result->setData($response);
        return $result;
    }

    /**
     * Customer access rights checking
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_Customer::addpoint');
    }

}