<?php
namespace Riki\SubscriptionProfileDisengagement\Controller\Adminhtml;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Registry;

abstract class Reason extends \Magento\Backend\App\Action
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    protected $_reasonFactory;

    /**
     * @param Context $context
     * @param Registry $coreRegistry
     * @param PageFactory $resultPageFactory
     * @param \Riki\SubscriptionProfileDisengagement\Model\ReasonFactory $reasonFactory
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        PageFactory $resultPageFactory,
        \Riki\SubscriptionProfileDisengagement\Model\ReasonFactory $reasonFactory
    ) {
        parent::__construct($context);
        $this->_coreRegistry = $coreRegistry;
        $this->resultPageFactory = $resultPageFactory;
        $this->_reasonFactory = $reasonFactory;
    }

    /**
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function initRedirectResult()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $result */
        $result = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);

        return $result;
    }

    /**
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_SubscriptionProfileDisengagement::reason');
    }
}