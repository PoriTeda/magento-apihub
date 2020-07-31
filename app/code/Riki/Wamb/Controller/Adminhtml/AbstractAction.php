<?php
namespace Riki\Wamb\Controller\Adminhtml;

abstract class AbstractAction extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Riki_Wamb::top_level';

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * AbstractAction constructor.
     *
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Registry $registry,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->logger = $logger;
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
        $result->addBreadcrumb(__('WAMB'), __('WAMB'));
        $result->setActiveMenu('Magento_Customer::customer');
        $result->getConfig()->getTitle()->prepend(__('WAMB Rule'));

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
     * Initialize result
     *
     * @return \Magento\Framework\Controller\Result\Forward
     */
    public function initForwardResult()
    {
        /** @var \Magento\Framework\Controller\Result\Forward $result */
        $result = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_FORWARD);

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