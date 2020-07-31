<?php
namespace Riki\Prize\Controller\Adminhtml\Index;
use Magento\Backend\App\Action;
class NewAction extends \Riki\Prize\Controller\Adminhtml\Action
{
    public function __construct(
        Action\Context $context
    )
    {
        parent::__construct($context);
    }

    /**
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        return $this->_forward('edit');
    }
}