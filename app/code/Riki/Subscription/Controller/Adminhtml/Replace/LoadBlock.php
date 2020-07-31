<?php

namespace Riki\Subscription\Controller\Adminhtml\Replace;

class LoadBlock extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \Magento\Framework\Controller\Result\RawFactory
     */
    protected $resultRawFactory;

    /**
     * Load block constructor.
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $pageFactory
     * @param \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
    ) {
        $this->resultPageFactory = $pageFactory;
        $this->resultRawFactory = $resultRawFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $request = $this->getRequest();
        $block = $request->getParam('block');

        $resultPage = $this->resultPageFactory->create();

        if ($block) {
            $blocks = explode(',', $block);

            foreach ($blocks as $block) {
                $resultPage->addHandle('subscription_replace_load_block_' . $block);
            }
        }

        $result = $resultPage->getLayout()->renderElement('content');
        return $this->resultRawFactory->create()->setContents($result);
    }

    public function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_Subscription::replace_product');
    }
}
