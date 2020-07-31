<?php
namespace Riki\FairAndSeasonalGift\Controller\Adminhtml\Recommend;

class Item extends \Riki\FairAndSeasonalGift\Controller\Adminhtml\Fair
{
    /**
     * @return mixed
     */
    public function execute()
    {
        $this->initModel();

        $this->registry->register('related_fair_id', $this->_request->getParam('relatedFairId'));

        return $this->getResponse()->setBody(
            $this->_view->getLayout()->createBlock(
                'Riki\FairAndSeasonalGift\Block\Adminhtml\Recommend\Item'
            )->toHtml()
        );
    }
}
