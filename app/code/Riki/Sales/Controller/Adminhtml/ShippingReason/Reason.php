<?php

namespace Riki\Sales\Controller\Adminhtml\ShippingReason;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Riki\Sales\Api\ShippingReasonRepositoryInterface;
use Riki\Sales\Model\ShippingReasonData;

abstract class Reason extends Action
{
    /**
     * Shipping Reason repository
     *
     * @var ShippingReasonRepositoryInterface
     */
    protected $shippingReasonRepository;

    /**
     * Core registry
     *
     * @var Registry
     */
    protected $coreRegistry;

    /**
     * Result Page Factory
     *
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * Result Forward Factory
     *
     * @var ForwardFactory
     */
    protected $resultForwardFactory;

    /**
     * Data constructor.
     *
     * @param Registry $registry
     * @param ShippingReasonRepositoryInterface $shippingReasonRepository
     * @param PageFactory $resultPageFactory
     * @param ForwardFactory $resultForwardFactory
     * @param Context $context
     */
    public function __construct(
        Registry $registry,
        ShippingReasonRepositoryInterface $shippingReasonRepository,
        PageFactory $resultPageFactory,
        ForwardFactory $resultForwardFactory,
        Context $context
    )
    {
        $this->coreRegistry = $registry;
        $this->shippingReasonRepository = $shippingReasonRepository;
        $this->resultPageFactory = $resultPageFactory;
        $this->resultForwardFactory = $resultForwardFactory;
        parent::__construct($context);
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(ShippingReasonData::ACL_ROOT_ACTIONS);
    }
}
