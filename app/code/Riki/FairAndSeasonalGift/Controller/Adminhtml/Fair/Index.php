<?php
namespace Riki\FairAndSeasonalGift\Controller\Adminhtml\Fair;
class Index extends \Riki\FairAndSeasonalGift\Controller\Adminhtml\Fair
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $resultPage = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_PAGE);

        $this->initPage($resultPage);

        return $resultPage;
    }
}
