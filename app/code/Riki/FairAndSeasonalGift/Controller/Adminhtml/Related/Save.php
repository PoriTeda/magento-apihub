<?php

namespace Riki\FairAndSeasonalGift\Controller\Adminhtml\Related;

class Save extends \Riki\FairAndSeasonalGift\Controller\Adminhtml\Fair
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        if ($data = $this->getRequest()->getParams()) {

            $id = $this->getRequest()->getParam('fair_id');
            $relatedId = $this->getRequest()->getParam('related_id');

            if( empty($relatedId) ){
                $res = ['error' => true, 'message' => __('Unable to find related fair to save')];
            } else {

                $validate = $this->checkRelatedFairExist($id, $relatedId);

                if($validate){
                    $res = ['error' => true, 'message' => __('Related fair is already exist')];
                } else {
                    $this->addRelatedFair($id, $relatedId);
                    $this->initModel();
                    $res = $this->_view->getLayout()->createBlock('Riki\FairAndSeasonalGift\Block\Adminhtml\Fair\Edit\Tab\Related')->toHtml();
                }
            }
        } else {
            $res = ['error' => true, 'message' => __('Unable to find item to save')];
        }

        if ( is_array($res) ){
            $res = $this->_jsonHelper->jsonEncode( $res );
            $this->getResponse()->representJson( $res );
        } else {
            $this->getResponse()->setBody( $res );
        }
    }

    /**
     * @param $id
     * @param $relatedId
     * return boolean
     */
    public function checkRelatedFairExist($id, $relatedId)
    {
        $connection = $this->_fairConnectionFactory->create();
        $collection = $connection->getCollection();
        $collection->addFieldToFilter('fair_id', $id)->addFieldToFilter('fair_related_id', $relatedId);
        if($collection->getSize()){
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param $id
     * @param $relatedId
     */
    public function addRelatedFair($id, $relatedId)
    {
        $connection = $this->_fairConnectionFactory->create();
        $connection->setFairId($id);
        $connection->setFairRelatedId($relatedId);
        try {
            $connection->save();
            return true;
        } catch (\Exception $e){
            return false;
        }
    }
}
