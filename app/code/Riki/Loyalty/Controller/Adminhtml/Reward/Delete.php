<?php

namespace Riki\Loyalty\Controller\Adminhtml\Reward;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Riki\Loyalty\Model\Reward;
use Riki\Loyalty\Model\ConsumerDb\ShoppingPoint;

class Delete extends \Riki\Loyalty\Controller\Adminhtml\Reward
{
    /**
     * Delete point adjustment
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $rewardId = $this->_request->getParam('id');
        try {
            /** @var \Riki\Loyalty\Model\Reward $model */
            $model = $this->_rewardFactory->create();
            $model->load($rewardId);
            if (!$model->getId()) {
                throw new LocalizedException(__('Record %1 not found', $rewardId));
            }
            if ($model->getData('point_type') != \Riki\Loyalty\Model\Reward::TYPE_ADJUSTMENT) {
                throw new LocalizedException(__('Only allow delete with point type ADJUSTMENT'));
            }
            $apiData = [
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
            $response = $this->_consumerDb->setPoint(
                ShoppingPoint::REQUEST_TYPE_USE, $model->getData('customer_code'), $apiData
            );
            if (!$response['error']) {
                $model->delete();
            } else {
                throw new LocalizedException(__($response['msg']));
            }
        } catch (\Exception $e) {
            if ($e instanceof LocalizedException) {
                $response['msg'] = $e->getMessage();
            } else {
                $this->logger->critical($e);
                $response['msg'] = __('An error occurs.');
            }

            $response['error'] = true;
        }

        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultRedirect->setData($response);

        return $resultRedirect;
    }

    /**
     * Customer access rights checking
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_Loyalty::delete_point');
    }
}