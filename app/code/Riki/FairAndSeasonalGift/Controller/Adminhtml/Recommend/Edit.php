<?php
namespace Riki\FairAndSeasonalGift\Controller\Adminhtml\Recommend;

class Edit extends \Riki\FairAndSeasonalGift\Controller\Adminhtml\Fair
{
    /**
     * @return mixed
     */
    public function execute()
    {
        if ($data = $this->getRequest()->getParams()) {

            $fairId = $this->getRequest()->getParam('fairId');
            $recommendedFairId = $this->getRequest()->getParam('relatedFairId');
            $recommendInfo = $this->_jsonHelper->jsonDecode($this->getRequest()->getParam('recommend'));
            foreach ($recommendInfo as $item){
                $this->addRecommendItems($fairId, $item['productId'], $recommendedFairId, $item['recommendedProductId']);
            }
            $res = ['error' => false, 'message' => __('Item was successfully saved')];
        } else {
            $res = ['error' => true, 'message' => __('Unable to find item to save')];
        }
        $res = $this->_jsonHelper->jsonEncode( $res );
        $this->getResponse()->representJson( $res );
    }

    /**
     * @param $fairId
     * @param $productId
     * @param $recommendFair
     * @param $recommendProduct
     */
    public function addRecommendItems($fairId, $productId, $recommendFair, $recommendProduct){
        $factory = $this->_fairRecommendationFactory->create();
        $collection = $factory->getCollection();
        $collection->addFieldToFilter('fair_id', $fairId)
            ->addFieldToFilter('recommended_fair_id', $recommendFair)
            ->addFieldToFilter('recommended_product_id', $recommendProduct);

        if( $collection->getSize() ){
            $factory = $collection->getFirstItem();
            if( $productId == 0 ){
                $factory->delete();
                return;
            } else {
                $factory->setProductId($productId);
            }
        } else {
            if($productId == 0 ){
                return;
            } else {
                $factory->setFairId($fairId);
                $factory->setRecommendedFairId($recommendFair);
                $factory->setRecommendedProductId($recommendProduct);
            }
        }

        $factory->setProductId($productId);

        try {
            $factory->save();
        } catch (\Exception $e){
            $this->messageManager->addError($e->getMessage());
        }
    }
}
