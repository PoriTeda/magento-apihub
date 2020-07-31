<?php

namespace Riki\Sales\Controller\Adminhtml\ShippingCause;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Riki\Sales\Api\ShippingCauseRepositoryInterface;
use Riki\Sales\Model\ShippingCauseData;

abstract class Cause extends Action
{

    /**
     * Shipping Cause repository
     *
     * @var ShippingCauseRepositoryInterface
     */
    protected $shippingCauseRepository;

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
     * @param ShippingCauseRepositoryInterface $shippingCauseRepository
     * @param PageFactory $resultPageFactory
     * @param ForwardFactory $resultForwardFactory
     * @param Context $context
     */
    public function __construct(
        Registry $registry,
        ShippingCauseRepositoryInterface $shippingCauseRepository,
        PageFactory $resultPageFactory,
        ForwardFactory $resultForwardFactory,
        Context $context
    )
    {
        $this->coreRegistry = $registry;
        $this->shippingCauseRepository = $shippingCauseRepository;
        $this->resultPageFactory = $resultPageFactory;
        $this->resultForwardFactory = $resultForwardFactory;
        parent::__construct($context);
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(ShippingCauseData::ACL_ROOT_ACTIONS);
    }
}
