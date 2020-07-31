<?php
namespace Riki\ThirdPartyImportExport\Controller\Order;

class History extends \Riki\ThirdPartyImportExport\Controller\Order
{
    public function execute()
    {
        $resultPage = $this->initResultPage();

        $resultPage->getConfig()
            ->getTitle()
            ->set(__('My Orders Before %1', $this->_config->getCommonAnchor_date()));

        return $resultPage;
    }
}
