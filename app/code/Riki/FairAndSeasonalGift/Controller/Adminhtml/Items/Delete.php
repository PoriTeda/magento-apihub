<?php

namespace Riki\FairAndSeasonalGift\Controller\Adminhtml\Items;

class Delete extends \Riki\FairAndSeasonalGift\Controller\Adminhtml\Fair
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        try {
            $detailId = $this->getRequest()->getParam('id');
            $detail = $this->_fairDetailFactory->create();
            $detail->load($detailId);
            if($detail->getId()){
                if( $detail->getIsRecommend() == 0 ){
                    $detail->delete();
                    $this->messageManager->addSuccess(__('Fair item was successfully deleted.'));
                } else {
                    $collection = $detail->getCollection();
                    $collection->addFieldToFilter('fair_id', $detail->getFairId())->addFieldToFilter('id', ['neq' => $detail->getId()]);

                    if( $collection->getSize() ){
                        $this->messageManager->addError(__('Recommend product is required.'));
                    } else {
                        $detail->delete();
                        $this->messageManager->addSuccess(__('Fair item was successfully deleted.'));
                    }
                }
            }
        } catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
        }
        return $resultRedirect->setPath('fair_seasonal/fair/edit', ['fair_id' => $this->getRequest()->getParam('fair_id')]);
    }
}
