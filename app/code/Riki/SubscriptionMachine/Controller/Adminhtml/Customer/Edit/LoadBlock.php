<?php

namespace Riki\SubscriptionMachine\Controller\Adminhtml\Customer\Edit;

use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\Result\RawFactory;

class LoadBlock extends \Riki\SubscriptionMachine\Controller\Adminhtml\Customer\Edit
{
    /**
     * @var RawFactory
     */
    protected $resultRawFactory;

    /**
     * @var \Riki\SubscriptionMachine\Model\MachineCustomerFactory
     */
    protected $machineCustomerFactory;

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * LoadBlock constructor.
     * @param Action\Context $context
     * @param PageFactory $resultPageFactory
     * @param \Magento\Framework\Registry $registry
     * @param \Riki\SubscriptionMachine\Model\MachineCustomerFactory $machineCustomerFactory
     * @param RawFactory $resultRawFactory
     */
    public function __construct(
        Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Registry $registry,
        \Riki\SubscriptionMachine\Model\MachineCustomerFactory $machineCustomerFactory,
        RawFactory $resultRawFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->resultRawFactory = $resultRawFactory;
        $this->machineCustomerFactory = $machineCustomerFactory;
        parent::__construct($context,$registry,$machineCustomerFactory);
    }

    /**
     * Loading page block
     *
     * @return \Magento\Backend\Model\View\Result\Redirect|\Magento\Framework\Controller\Result\Raw
     */
    public function execute()
    {
        $request = $this->getRequest();
        $asJson = $request->getParam('json');
        $block = $request->getParam('block');

        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        if ($asJson) {
            $resultPage->addHandle('machine_customer_edit_load_block_json');
        } else {
            $resultPage->addHandle('machine_customer_edit_load_block_plain');
        }

        if ($block) {
            $blocks = explode(',', $block);
            if ($asJson && !in_array('message', $blocks)) {
                $blocks[] = 'message';
            }

            foreach ($blocks as $block) {
                $resultPage->addHandle('machine_customer_edit_load_block_' . $block);
            }
        }
        $result = $resultPage->getLayout()->renderElement('content');
        if ($request->getParam('as_js_varname')) {
            $this->_session->setUpdateResult($result);
            return $this->resultRedirectFactory->create()->setPath('customer/*/showUpdateResult');
        }
        return $this->resultRawFactory->create()->setContents($result);
    }
}
