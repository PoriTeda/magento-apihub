<?php

namespace Riki\FairAndSeasonalGift\Controller\Adminhtml\Related;

class Order extends \Riki\FairAndSeasonalGift\Controller\Adminhtml\Fair
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        if ($data = $this->getRequest()->getParams()) {

            $order = $this->_jsonHelper->jsonDecode($this->getRequest()->getParam('order'));

            if( !empty( $order) ){
                $success = 0;
                $failed = 0;
                foreach ($order as $item){
                    $connection = $this->_fairConnectionFactory->create();

                    $connection->load($item['id']);

                    if( $connection->getId() ){
                        $connection->setData('fair_related_order', $item['order']);
                        try {
                            $connection->save();
                            $success++;
                        }catch (\Exception $e){
                            $failed++;
                        }
                    }
                }
                $message = '';
                if( $success > 0 ) {
                    $message .= __('A total of %1 record(s) have been save.', $success);
                }
                if( $failed > 0 ) {
                    $message .= __('A total of %1 record(s) cannot been save.', $failed);
                }
                $res = ['error' => false, 'message' => $message];
            } else {
                $res = ['error' => true, 'message' => __('Unable to find item to save')];
            }
        } else {
            $res = ['error' => true, 'message' => __('Unable to find item to save')];
        }
        $res = $this->_jsonHelper->jsonEncode( $res );
        $this->getResponse()->representJson( $res );
    }
}
