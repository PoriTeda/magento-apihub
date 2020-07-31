<?php
namespace Riki\AdvancedInventory\Controller\Adminhtml;

abstract class AbstractAction extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Riki_AdvancedInventory::actions';

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * AbstractAction constructor.
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
        \Magento\Framework\Registry $registry,
        \Magento\Backend\App\Action\Context $context
    )
    {
        $this->registry = $registry;
        parent::__construct($context);
    }

    /**
     * Initialize result
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function initPageResult()
    {
        /** @var \Magento\Framework\View\Result\Page $result */
        $result = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_PAGE);
        $result->addBreadcrumb(__('Advanced Inventory'), __('Advanced Inventory'));
        $result->setActiveMenu('Magento_Sales::sales');
        $result->getConfig()->getTitle()->prepend(__('Advanced Inventory'));

        return $result;
    }

    /**
     * Initialize result
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function initRedirectResult()
    {
        /** @var \Magento\Framework\Controller\Result\Redirect $result */
        $result = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(static::ADMIN_RESOURCE);
    }
}