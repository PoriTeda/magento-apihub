<?php

namespace Riki\Checkout\Controller\Index;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;

class Single extends \Magento\Checkout\Controller\Onepage
{
    /**
     * @var \Magento\Checkout\Helper\Data
     */
    protected $checkoutHelper;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Framework\Url\Encoder
     */
    protected $urlEncoder;
    /**
     * @var \Riki\SubscriptionPage\Helper\Data
     */
    protected $subPageHelper;

    /**
     * @var \Riki\SubscriptionCourse\Helper\ValidateDelayPayment
     */
    protected $helperDelayPayment;

    /**
     * @var \Riki\MachineApi\Helper\Machine
     */
    protected $helperMachine;

    /**
     * @var \Riki\Subscription\Api\Data\ValidatorInterface
     */
    protected $subscriptionValidator;

    /**
     * Single constructor.
     * @param \Riki\SubscriptionPage\Helper\Data $subPageHelper
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param CustomerRepositoryInterface $customerRepository
     * @param AccountManagementInterface $accountManagement
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Framework\Translate\InlineInterface $translateInline
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\View\LayoutFactory $layoutFactory
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory
     * @param \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Checkout\Helper\Data $checkoutHelper
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Framework\Url\Encoder $urlEncoder
     * @param \Riki\SubscriptionCourse\Helper\ValidateDelayPayment $helperDelayPayment
     * @param \Riki\MachineApi\Helper\Machine $helperMachine
     * @param \Riki\Subscription\Api\Data\ValidatorInterface $subscriptionValidator
     */
    public function __construct(
        \Riki\SubscriptionPage\Helper\Data $subPageHelper,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        CustomerRepositoryInterface $customerRepository,
        AccountManagementInterface $accountManagement,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\Translate\InlineInterface $translateInline,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\View\LayoutFactory $layoutFactory,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Checkout\Helper\Data $checkoutHelper,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\Url\Encoder $urlEncoder,
        \Riki\SubscriptionCourse\Helper\ValidateDelayPayment $helperDelayPayment,
        \Riki\MachineApi\Helper\Machine $helperMachine,
        \Riki\Subscription\Api\Data\ValidatorInterface $subscriptionValidator
    ) {
        $this->helperDelayPayment = $helperDelayPayment;
        $this->checkoutHelper = $checkoutHelper;
        $this->checkoutSession = $checkoutSession;
        $this->urlEncoder = $urlEncoder;
        $this->subPageHelper = $subPageHelper;
        $this->helperMachine = $helperMachine;
        $this->subscriptionValidator = $subscriptionValidator;
        parent::__construct(
            $context,
            $customerSession,
            $customerRepository,
            $accountManagement,
            $coreRegistry,
            $translateInline,
            $formKeyValidator,
            $scopeConfig,
            $layoutFactory,
            $quoteRepository,
            $resultPageFactory,
            $resultLayoutFactory,
            $resultRawFactory,
            $resultJsonFactory
        );
    }

    /**
     * Checkout page.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $url = $this->checkoutSession->getCartRefererUrl();
        if($url === null || $url === ''){
            $url = $this->_url->getUrl('');
        }

        if (!$this->checkoutHelper->canOnepageCheckout()) {
            $this->messageManager->addError(__('One-page checkout is turned off.'));

            return $this->resultRedirectFactory->create()->setPath($url);
        }

        $quote = $this->getOnepage()->getQuote();
        $quote->setData('is_multiple_shipping', 0);
        if (!$this->_customerSession->isLoggedIn() && !$this->checkoutHelper->isAllowedGuestCheckout($quote)) {
            $loginUrl = $this->_url->getUrl('customer/account/login', [
                \Magento\Customer\Model\Url::REFERER_QUERY_PARAM_NAME => $this->urlEncoder->encode(
                    $this->_url->getUrl('*/*/*', ['_secure' => true])
                ),
            ]);
            $this->checkoutSession->setCheckoutRefererUrl($this->_url->getUrl('*/*/*', ['_secure' => true]));

            return $this->resultRedirectFactory->create()->setUrl($loginUrl);
        }

        if (!$quote->hasItems() || $quote->getHasError() || !$quote->validateMinimumAmount()) {
            $errors = $quote->getErrors();
            if ($errors) {
                foreach ($errors as $error) {
                    $this->messageManager->addMessage($error);
                }
            }
            return $this->resultRedirectFactory->create()->setPath($url);
        }

        if($quote->getData('riki_course_id') and $quote->getData('riki_frequency_id')) {
            $validateSubCourse = $this->subPageHelper->validateSubscriptionRule($quote);

            $courseName = null;
            $courseModel = $this->subPageHelper->getSubscriptionCourseModelFromCourseId($quote->getData('riki_course_id'));
            if ($courseModel->getId()) {
                $courseName = $courseModel->getData('course_name');
            }
            $categoryName = $this->subPageHelper->getCategoryNameMustSkuInSubCourse($courseModel);
            switch ($validateSubCourse) {
                case 3:
                    $this->messageManager->addError(__("You need to purchase items of %1",$categoryName));
                    break;
                case 4:
                    $this->messageManager->addError(__("In %1, the total number of items in the shopping cart have at least %2 quantity",
                        $courseName,$courseModel->getData('minimum_order_qty')));
                    break;
                case 5:
                    $this->messageManager->addError(__("You need to purchase items of %1",$categoryName));
                default:
                    // Do nothing
            }

            /** Validate maximum qty restriction */
            $prepareData = $this->subscriptionValidator->prepareProductDataByQuote($quote);
            $validateMaximumQty = $this->subscriptionValidator
                ->setCourseId($quote->getRikiCourseId())
                ->setProductCarts($prepareData)
                ->validateMaximumQtyRestriction();

            if ($validateMaximumQty['error']) {
                $message = $this->subscriptionValidator->getMessageMaximumError(
                    $validateMaximumQty['product_errors'],
                    $validateMaximumQty['maxQty']
                );
                $this->messageManager->addError($message);
                return $this->resultRedirectFactory->create()->setPath($url);
            }

            if ($validateSubCourse != 0) {
                return $this->resultRedirectFactory->create()->setPath($url);
            }

            /** Validate have machine in cart */
            if ($courseModel->getSubscriptionType() == \Riki\SubscriptionCourse\Model\Course\Type::TYPE_MULTI_MACHINES) {
                $validateMachine = $this->helperMachine->hasMachineInCart($quote);
                $machineNotRequired = $this->checkoutSession->getMachineNotRequired();
                if (!$validateMachine && $machineNotRequired !== true) {
                    $this->messageManager->addError(__('You will need to select one or more machines for this scheduled flight.'));
                    return $this->resultRedirectFactory->create()->setPath($url);
                }
            }
        }

        $this->_customerSession->regenerateId();
        $this->checkoutSession->setCartWasUpdated(false);
        $currentUrl = $this->_url->getUrl('*/*/*', ['_secure' => true]);
        $this->_customerSession->setBeforeAuthUrl($currentUrl);
        $this->getOnepage()->initCheckout();
        $resultPage = $this->resultPageFactory->create();
        $resultPage->addHandle('checkout_index_single');
        $resultPage->getConfig()->getTitle()->set(__('Checkout Details'));

        return $resultPage;
    }
}
