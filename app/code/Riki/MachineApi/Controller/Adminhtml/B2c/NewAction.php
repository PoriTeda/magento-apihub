<?php
namespace Riki\MachineApi\Controller\Adminhtml\B2c;

use Magento\Backend\App\Action\Context;

class NewAction extends \Riki\MachineApi\Controller\Adminhtml\Action
{
    public function __construct(
        Context $context
    ) {
        parent::__construct($context);
    }

    /**
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        return $this->_forward('edit');
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_Subscription::machine_b2c_skus_save');
    }
}
