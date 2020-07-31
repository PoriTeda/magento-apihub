<?php
namespace Riki\Checkout\Controller\Cart;

use Magento\Checkout\Model\Cart as CustomerCart;
use Magento\Framework\Exception\LocalizedException;
use Riki\DelayPayment\Helper\Data as DelayPaymentHelper;
use Magento\Framework\Escaper;

class Delete extends \Magento\Checkout\Controller\Cart\Delete
{
    /**
     * @var \Riki\Subscription\Helper\Order
     */
    protected $orderAmountRestriction;

    /**
     * @var Escaper
     */
    protected $escaper;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $url;

    /**
     * @var \Riki\MachineApi\Helper\Machine
     */
    protected $helperMachine;

    /**
     * Delete constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     * @param CustomerCart $cart
     * @param Escaper $escaper
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Riki\MachineApi\Helper\Machine $helperMachine
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        CustomerCart $cart,
        \Riki\Subscription\Helper\Order $orderAmountRestriction,
        Escaper $escaper,
        \Psr\Log\LoggerInterface $logger,
        \Riki\MachineApi\Helper\Machine $helperMachine
    ) {
        parent::__construct($context, $scopeConfig, $checkoutSession, $storeManager, $formKeyValidator, $cart);
        $this->orderAmountRestriction = $orderAmountRestriction;
        $this->escaper = $escaper;
        $this->url = $context->getUrl();
        $this->logger = $logger;
        $this->helperMachine = $helperMachine;
    }

    /**
     * Delete shopping cart item action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        if (!$this->_formKeyValidator->validate($this->getRequest())) {
            return $this->resultRedirectFactory->create()->setPath('*/*/');
        }

        $id = (int)$this->getRequest()->getParam('id');
        if ($id) {
            try {
                $this->cart->removeItem($id);
                $quote = $this->cart->getQuote();
                /** Remove machine product for course multiple machine */
                $this->helperMachine->removeMachineInvalid($quote);
                /** Validate order total amount threshold*/
                $this->cart->setValidateMaxMinCourse(true);
                $this->cart->save();
            } catch (LocalizedException $e) {
                $this->messageManager->addError($this->escaper->escapeHtml($e->getMessage()));
            } catch (\Exception $e) {
                $this->messageManager->addError(__('We can\'t remove the item.'));
                $this->logger->critical($e);
            }
        }
        $defaultUrl = $this->url->getUrl('*/*');
        return $this->resultRedirectFactory->create()->setUrl($this->_redirect->getRedirectUrl($defaultUrl));
    }
}
