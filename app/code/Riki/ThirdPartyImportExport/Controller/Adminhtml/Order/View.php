<?php
namespace Riki\ThirdPartyImportExport\Controller\Adminhtml\Order;

use Riki\ThirdPartyImportExport\Controller\Adminhtml\Order;

class View extends Order
{
    const ADMIN_RESOURCE = 'Riki_ThirdPartyImportExport::order_legacy';

    public function execute()
    {
        $this->initCurrentOrder();
        $resultPage = $this->initResultPage();

        $resultPage->getConfig()
            ->getTitle()
            ->prepend(__('Order Information'));

        return $resultPage;
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(self::ADMIN_RESOURCE);
    }

}
