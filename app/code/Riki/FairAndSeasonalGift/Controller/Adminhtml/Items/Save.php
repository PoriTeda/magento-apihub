<?php

namespace Riki\FairAndSeasonalGift\Controller\Adminhtml\Items;

class Save extends \Riki\FairAndSeasonalGift\Controller\Adminhtml\Fair
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        if ($data = $this->getRequest()->getParams()) {
            $detailList = $this->_jsonHelper->jsonDecode($this->getRequest()->getParam('detailList'));
            if( !empty($detailList) ){
                $success = 0;
                $fail = 0;
                foreach($detailList as $dt){
                    $detail = $this->_fairDetailFactory->create();
                    $detail->load($dt['id']);
                    if( $detail->getId() ){
                        $detail->setSerialNo($dt['serialNo']);
                        $detail->setIsRecommend($dt['isRecommend']);
                        try {
                            $detail->save();
                            $success++;
                        } catch (\Exception $e){
                            $fail++;
                        }
                    } else {
                        $fail++;
                    }
                }

                $message = '';
                if( $success > 0 ) {
                    $message .= __('A total of %1 record(s) have been save.', $success);
                }
                if( $fail > 0 ) {
                    $message .= __('A total of %1 record(s) cannot been save.', $fail);
                }
                $res = ['error' => false, 'message' => $message];

            } else {
                $res = ['error' => true, 'message' => __('Unable to find item to save')];
            }
        } else {
            $res = ['error' => true, 'message' => __('Unable to find item to save')];
        }

        $res = $this->_jsonHelper->jsonEncode($res);
        $this->getResponse()->representJson($res);
    }
}
