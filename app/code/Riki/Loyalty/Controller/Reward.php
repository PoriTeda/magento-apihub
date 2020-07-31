<?php

namespace Riki\Loyalty\Controller;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NotFoundException;
use Magento\Customer\Model\Session;

abstract class Reward extends \Magento\Framework\App\Action\Action
{
    /**
     * @var Session
     */
    protected $_customerSession;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $_resultPageFactory;

    /**
     * @var \Magento\Framework\Data\Form\FormKey\Validator
     */
    protected $_formKeyValidator;

    /**
     * @var \Riki\Loyalty\Model\ConsumerDb\CustomerSub
     */
    protected $_customerSub;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $_quoteRepository;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Riki\Loyalty\Api\CheckoutRewardPointInterface
     */
    protected $_checkoutRewardPoint;

    /**
     * @var \Riki\Loyalty\Model\RewardQuoteFactory
     */
    protected $_rewardQuoteFactory;


    protected $_rewardManagement;
    /**
     * Reward constructor.
     *
     * @param Session $session
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\View\Result\PageFactory $pageFactory
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     * @param \Riki\Loyalty\Model\ConsumerDb\CustomerSub $customerSub
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Riki\Loyalty\Api\CheckoutRewardPointInterface $checkoutRewardPoint
     * @param \Riki\Loyalty\Model\RewardQuoteFactory $rewardQuoteFactory
     */
    public function __construct(
        \Magento\Customer\Model\Session $session,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Riki\Loyalty\Model\ConsumerDb\CustomerSub $customerSub,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Riki\Loyalty\Api\CheckoutRewardPointInterface $checkoutRewardPoint,
        \Riki\Loyalty\Model\RewardQuoteFactory $rewardQuoteFactory,
        \Riki\Loyalty\Model\RewardManagement $rewardManagement
    )
    {
        parent::__construct($context);
        $this->_customerSession = $session;
        $this->_registry = $registry;
        $this->_formKeyValidator = $formKeyValidator;
        $this->_customerSub = $customerSub;
        $this->_resultPageFactory = $pageFactory;
        $this->_quoteRepository = $quoteRepository;
        $this->_storeManager = $storeManager;
        $this->_checkoutRewardPoint = $checkoutRewardPoint;
        $this->_rewardQuoteFactory = $rewardQuoteFactory;
        $this->_rewardManagement = $rewardManagement;
    }

    /**
     * @param RequestInterface $request
     * @return \Magento\Framework\App\ResponseInterface
     * @throws NotFoundException
     */
    public function dispatch(RequestInterface $request)
    {
        if (!$this->_customerSession->isLoggedIn()) {
            $this->_redirect('customer/account/login');
        }
        $this->_registry->register('current_customer', $this->_customerSession->getCustomer());
        return parent::dispatch($request);
    }

    /**
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->_resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__('Reward Point'));
        return $resultPage;
    }
}