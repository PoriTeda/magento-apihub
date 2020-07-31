<?php
namespace Riki\SubscriptionProfileDisengagement\Controller\Adminhtml\Reason;

class Grid extends \Riki\SubscriptionProfileDisengagement\Controller\Adminhtml\Reason
{

    public function execute()
    {
        $resultLayout = $this->resultPageFactory->create();
        return $resultLayout;
    }
}