<?php

namespace Riki\Loyalty\Controller\Adminhtml;

class Redeem extends \Magento\Backend\App\Action
{
    /**
     * @var \Riki\Loyalty\Model\RewardManagement
     */
    protected $_rewardManagement;

    /**
     * @var \Riki\Loyalty\Api\CheckoutRewardPointInterface
     */
    protected $_checkoutRewardPoint;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Riki\Loyalty\Model\RewardManagement $rewardManagement,
        \Riki\Loyalty\Api\CheckoutRewardPointInterface $checkoutRewardPoint
    )
    {
        $this->_rewardManagement = $rewardManagement;
        $this->_checkoutRewardPoint = $checkoutRewardPoint;
        parent::__construct($context);
    }

    /**
     * Main action
     */
    public function execute()
    {
        $this->_view->loadLayout();
        $this->_view->getLayout()->initMessages();
        $this->_view->renderLayout();
    }

    protected function _isAllowed()
    {
        return true;
    }
}