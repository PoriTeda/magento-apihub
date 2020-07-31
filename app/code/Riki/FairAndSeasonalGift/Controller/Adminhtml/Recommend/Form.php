<?php
namespace Riki\FairAndSeasonalGift\Controller\Adminhtml\Recommend;

class Form extends \Riki\FairAndSeasonalGift\Controller\Adminhtml\Fair
{
    /**
     * @return mixed
     */
    public function execute()
    {
        $this->initModel();

        return $this->getResponse()->setBody(
            $this->_view->getLayout()->createBlock(
                'Riki\FairAndSeasonalGift\Block\Adminhtml\Recommend\Form'
            )->toHtml()
        );
    }
}
