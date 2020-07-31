<?php

namespace Riki\Loyalty\Controller\Adminhtml\Reward;

class Nestlepoint extends \Riki\Loyalty\Controller\Adminhtml\Reward
{
    /**
     * Shopping point grid
     *
     * @return \Magento\Framework\View\Result\Layout
     */
    public function execute()
    {
        $this->initCurrentCustomer();
        /** @var \Magento\Framework\View\Result\Layout $resultLayout */
        $this->_view->loadLayout();
        $this->_view->renderLayout();
    }

    /**
     * Customer access rights checking
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_Customer::viewpoint');
    }
}