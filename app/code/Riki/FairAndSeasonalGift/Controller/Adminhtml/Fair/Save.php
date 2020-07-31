<?php

namespace Riki\FairAndSeasonalGift\Controller\Adminhtml\Fair;

use Magento\Framework\Exception\LocalizedException;

class Save extends \Riki\FairAndSeasonalGift\Controller\Adminhtml\Fair
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();

        if ($data = $this->getRequest()->getParams()) {

            $model = $this->initModel();

            $data['fair']['mem_ids'] = implode(',', $data['fair']['mem_ids']);

            $model->addData($data['fair']);

            try {

                $valid = true;

                $validateDate = $this->validateFairDate( $model->getStartDate(), $model->getEndDate());

                if( !$validateDate ){
                    $this->messageManager->addError(__('End date must be later than start date.'));
                    $valid = false;
                }

                $validateUniqueFair = $this->validateUniqueFair($model);

                if( !$validateUniqueFair ){
                    $this->messageManager->addError(__('Fair Code "%1" already exist.', $model->getFairCode()));
                    $valid = false;
                }

                if( !$valid ) {
                    throw new LocalizedException(__('Unable to save this item.'));
                }

                $model->save();

                if( !empty($data['related']) ){
                    $this->processRelated($model->getId(),$data['related']);
                }

                if( !empty($data['detail']) ){
                    $this->processDetail($model->getId(),$data['detail']);
                }

                if( !empty($data['recommend_product']) ){
                    $this->processRecommend($model->getId(),$data['recommend_product']);
                }

                $this->messageManager->addSuccess(__('Item was successfully saved'));

                if ($this->getRequest()->getParam('back') == 'edit') {
                    return $resultRedirect->setPath('*/*/edit', ['fair_id' => $model->getFairId()]);
                }

                return $resultRedirect->setPath('*/*/');

            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
                $this->_getSession()->setData('riki_fair_form_data', $data['fair']);
                return $resultRedirect->setPath('*/*/edit', ['fair_id' => $model->getFairId()]);
            }
        } else {
            $this->messageManager->addError(__('Unable to find item to save'));
            return $resultRedirect->setPath('*/*/');
        }
    }

    /**
     * @param $fairId
     * @param $related
     */
    public function processRelated($fairId,$related){
        foreach ( $related as $k => $rl) {
            $factory = $this->_fairConnectionFactory->create();
            $collection = $factory->getCollection();
            $collection->addFieldToFilter('fair_id', $fairId)->addFieldToFilter('fair_related_id', $k);
            if($collection->getSize()){
                $factory = $collection->getFirstItem();
            } else {
                $factory->setFairId($fairId);
                $factory->setFairRelatedOrder($k);
            }

            $factory->setFairRelatedOrder($rl['fair_related_order']);
            try{
                $factory->save();
            } catch (\Exception $e){
                $this->_logger->error($e->getMessage());
            }
        }
    }

    /**
     * @param $fairId
     * @param $detail
     */
    public function processDetail($fairId, $detail){
        $isRecommend = 0;
        foreach ( $detail as $k => $rl) {
            $factory = $this->_fairDetailFactory->create();
            $collection = $factory->getCollection();
            $collection->addFieldToFilter('fair_id', $fairId)->addFieldToFilter('product_id', $k);
            if($collection->getSize()){
                $factory = $collection->getFirstItem();
                if( !empty($rl['is_deleted']) && $rl['is_deleted'] == 1 ){
                    $factory->delete();
                    continue;
                }
            } else {
                $factory->setFairId($fairId);
                $factory->setProductId($k);
            }

            $factory->setSerialNo($rl['serial_no']);

            if( !empty($rl['is_recommend']) ){
                $factory->setIsRecommend($rl['is_recommend']);
                if($rl['is_recommend'] == 1){
                    $isRecommend = 1;
                }
            }

            try{
                $factory->save();
            } catch (\Exception $e){
                $this->_logger->error($e->getMessage());
            }
        }

        if($isRecommend == 0){
            $this->setFairRecommendProduct($fairId);
        }
    }

    /**
     * @param $fairId
     * @param $recommend
     */
    public function processRecommend($fairId, $recommend){
        if( !empty( $recommend['recommended_fair_id'] ) && $recommend['recommended_fair_id'] != 0 ){
            if( !empty($recommend['detail']) ){
                foreach ($recommend['detail'] as $k => $dt){
                    $factory = $this->_fairRecommendationFactory->create();
                    $collection = $factory->getCollection();
                    $collection->addFieldToFilter('fair_id', $fairId)
                        ->addFieldToFilter('recommended_fair_id', $recommend['recommended_fair_id'])
                        ->addFieldToFilter('recommended_product_id', $k);

                    if( $collection->getSize() ){
                        $factory = $collection->getFirstItem();
                        if( $dt['productId'] == 0 ){
                            $factory->delete();
                            return;
                        }
                    } else {
                        if($dt['productId'] == 0 ){
                            return;
                        } else {
                            $factory->setFairId($fairId);
                            $factory->setRecommendedFairId($recommend['recommended_fair_id']);
                            $factory->setRecommendedProductId($k);
                        }
                    }

                    $factory->setProductId($dt['productId']);
                    try {
                        $factory->save();
                    } catch (\Exception $e){
                        $this->_logger->error($e->getMessage());
                    }
                }
            }
        }
    }

    /**
     * @param $startDate
     * @param $endDate
     * @return bool
     */
    public function validateFairDate( $startDate, $endDate )
    {
        $start = $this->_dateTime->timestamp($startDate);
        $end = $this->_dateTime->timestamp($endDate);
        if($start > $end){
            return false;
        } else {
            return true;
        }
    }

    /**
     * @param $model | FairFactory
     * @return bool
     */
    public function validateUniqueFair($model){
        $collection = $this->_fairFactory->create()->getCollection();
        $collection->addFieldToFilter('fair_code', $model->getFairCode());
        if($model->getId()){
            $collection->addFieldToFilter('fair_id', ['neq' => $model->getFairId()]);
        }
        if( $collection->getSize() ){
            return false;
        } else {
            return true;
        }
    }

    public function setFairRecommendProduct($fairId){
        $collection = $this->_fairDetailFactory->create()->getCollection();
        $collection->addFieldToFilter('fair_id', $fairId)->setOrder( 'serial_no', 'ASC');
        if($collection->getSize()){
            $recommend = $collection->getFirstItem();
            if($recommend->getIsRecommend() == 0){
                $recommend->setIsRecommend(1);
                try {
                    $recommend->save();
                } catch (\Exception $e){
                    $this->_logger->error($e->getMessage());
                }
            }
        }
    }
}
