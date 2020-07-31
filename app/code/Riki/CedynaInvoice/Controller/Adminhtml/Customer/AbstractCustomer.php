<?php
namespace Riki\CedynaInvoice\Controller\Adminhtml\Customer;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Registry;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Framework\App\RequestInterface;

/**
 * Class AbstractCustomer
 * @package Riki\CedynaInvoice\Controller\Adminhtml\Invoice
 */
abstract class AbstractCustomer extends Action
{
    /**
     * ACL name
     */
    const ADMIN_RESOURCE = 'Riki_CedynaInvoice::invoice';
    /**
     * @var \Magento\Backend\Model\View\Result\ForwardFactory
     */
    protected $resultForwardFactory;
    /**
     * @var \Magento\Framework\App\Response\Http\FileFactory
     */
    protected $fileFactory;
    /**
     * @var \Riki\CedynaInvoice\Model\ResourceModel\InvoiceFactory
     */
    protected $resourceInvoiceFactory;
    /**
     * @var \Riki\CedynaInvoice\Helper\Data
     */
    protected $helperData;

    /**
     * AbstractCustomer constructor.
     * @param Action\Context $context
     * @param Registry $registry
     * @param ForwardFactory $resultForwardFactory
     * @param \Riki\CedynaInvoice\Helper\Data $helperData
     * @param \Magento\Framework\App\Response\Http\FileFactory $fileFactory
     * @param \Riki\CedynaInvoice\Model\ResourceModel\InvoiceFactory $invoiceFactory
     */
    public function __construct(
        Action\Context $context,
        Registry $registry,
        ForwardFactory $resultForwardFactory,
        \Riki\CedynaInvoice\Helper\Data $helperData,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Riki\CedynaInvoice\Model\ResourceModel\InvoiceFactory $invoiceFactory
    ) {
        $this->registry = $registry;
        $this->resultForwardFactory = $resultForwardFactory;
        $this->helperData = $helperData;
        $this->fileFactory = $fileFactory;
        $this->resourceInvoiceFactory = $invoiceFactory;
        $this->helperData = $helperData;

        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function initResultPage()
    {
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu(self::ADMIN_RESOURCE);
        $resultPage->getConfig()->getTitle()->prepend(__('Customer Cedyna Invoice'));
        return $resultPage;
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(self::ADMIN_RESOURCE);
    }
    /**
     * @param RequestInterface $request
     * @return \Magento\Framework\App\ResponseInterface
     * @throws NotFoundException
     */
    public function dispatch(RequestInterface $request)
    {
        if (!$this->helperData->isEnable()) {
            $this->messageManager->addWarningMessage(__('Module Cedyna Invoice has been disabled.'));
            $this->_redirect('adminhtml/dashboard/index');
        }
        return parent::dispatch($request);
    }
}
