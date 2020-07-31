<?php
namespace Riki\ThirdPartyImportExport\Controller\Adminhtml\Customer;


class Order extends \Riki\ThirdPartyImportExport\Controller\Adminhtml\Customer
{
    const ADMIN_RESOURCE = 'Riki_ThirdPartyImportExport::order_legacy';

    public function execute()
    {
        $this->initCurrentCustomer();
        $layoutPage = $this->initLayoutPage();

        return $layoutPage;
    }
    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(self::ADMIN_RESOURCE);
    }

}
