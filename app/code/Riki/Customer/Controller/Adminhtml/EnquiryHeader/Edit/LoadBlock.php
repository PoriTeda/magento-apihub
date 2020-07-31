<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Customer\Controller\Adminhtml\EnquiryHeader\Edit;

use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\Result\RawFactory;

class LoadBlock extends \Riki\Customer\Controller\Adminhtml\EnquiryHeader\Edit
{
    /**
     * @var RawFactory
     */
    protected $resultRawFactory;

    /**
     * LoadBlock constructor.
     *
     * @param Action\Context $context
     * @param PageFactory $resultPageFactory
     * @param \Magento\Framework\Registry $registry
     * @param \Riki\Customer\Model\EnquiryHeader $model
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface
     * @param RawFactory $resultRawFactory
     */
    public function __construct(
        Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Registry $registry,
        \Riki\Customer\Model\EnquiryHeader $model,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
        RawFactory $resultRawFactory
    ) {
        $this->resultRawFactory = $resultRawFactory;
        $this->_coreRegistry = $registry;
        parent::__construct(
            $context,
            $resultPageFactory,
            $registry,
            $model,
            $orderFactory,
            $customerRepositoryInterface
        );
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

        $this->_coreRegistry->register('enquiry_block_id',$block);

        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->_resultPageFactory->create();
        if ($asJson) {
            $resultPage->addHandle('customer_enquiryheader_edit_load_block_json');
        } else {
            $resultPage->addHandle('customer_enquiryheader_edit_load_block_plain');
        }

        if ($block) {
            $blocks = explode(',', $block);
            if ($asJson && !in_array('message', $blocks)) {
                $blocks[] = 'message';
            }

            foreach ($blocks as $block) {
                if(strpos($block,'searchorder_gridorder') !== false){
                    $block = 'searchorder_gridorder';
                }
                if(strpos($block,'searchcustomer_gridcustomer') !== false){
                    $block = 'searchcustomer_gridcustomer';
                }
                $resultPage->addHandle('customer_enquiryheader_edit_load_block_' . $block);
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
