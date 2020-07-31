<?php

namespace Riki\Subscription\Controller\Profile;

use Magento\Framework\Controller\ResultFactory;

class EditSuccess extends \Magento\Framework\App\Action\Action
{

    public function execute()
    {
        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->getConfig()->getTitle()->set(__('Edit Subscription Profile Success.'));
        return $resultPage;
    }
}