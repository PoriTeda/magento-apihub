<?php

namespace Riki\Subscription\Controller\Multiple\Category;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

class SaveSelectedProduct extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\Session\SessionManagerInterface
     */
    protected $sessionManager;

    /**
     * SaveSelectedProduct constructor.
     * @param Context $context
     * @param \Magento\Framework\Session\SessionManagerInterface $sessionManager
     */
    public function __construct(
        Context $context,
        \Magento\Framework\Session\SessionManagerInterface $sessionManager
    ) {
        parent::__construct($context);
        $this->sessionManager = $sessionManager;
    }

    public function execute()
    {
            $data = $this->getRequest()->getParams();
            $this->sessionManager->setMulltipleCategoryCampaignSelectedProduct(json_encode($data));
    }
}
