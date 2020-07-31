<?php

namespace Riki\FairAndSeasonalGift\Controller\Adminhtml\Items;

class Add extends \Riki\FairAndSeasonalGift\Controller\Adminhtml\Fair
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        if ($data = $this->getRequest()->getParams()) {
            $id = $this->getRequest()->getParam('fair_id');
            $productList = $this->_jsonHelper->jsonDecode($this->getRequest()->getParam('productList'));
            if( !empty($productList) ){
                $this->registry->register('new_item', $productList);
            }

            return $this->getResponse()->setBody(
                $this->_view->getLayout()->createBlock(
                    'Riki\FairAndSeasonalGift\Block\Adminhtml\Items\ListItem'
                )->toHtml()
            );
        }
    }
}
