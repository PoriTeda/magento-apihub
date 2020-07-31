<?php

namespace Riki\CatalogRule\Controller\Adminhtml\Wbs;

class Index extends \Riki\CatalogRule\Controller\Adminhtml\Wbs\WbsAction
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $resultPage = $this->resultFactory->create(
            \Magento\Framework\Controller\ResultFactory::TYPE_PAGE
        );
        $this->initPage($resultPage);
        return $resultPage;
    }
}
