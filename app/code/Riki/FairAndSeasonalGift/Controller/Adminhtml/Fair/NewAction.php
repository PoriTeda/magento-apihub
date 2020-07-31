<?php
namespace Riki\FairAndSeasonalGift\Controller\Adminhtml\Fair;
class NewAction extends \Riki\FairAndSeasonalGift\Controller\Adminhtml\Fair
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        return $this->resultForwardFactory->create()->forward('edit');
    }
}
