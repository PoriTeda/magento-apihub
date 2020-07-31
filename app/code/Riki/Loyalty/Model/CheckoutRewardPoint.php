<?php

namespace Riki\Loyalty\Model;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;

class CheckoutRewardPoint implements \Riki\Loyalty\Api\CheckoutRewardPointInterface
{
    protected $_quoteRepository;

    protected $_rewardQuoteFactory;

    protected $_appState;

    protected $_paymentMethodManagement;

    protected $_paymentDetailsFactory;

    protected $_cartTotalsRepository;

    /**
     * @var \Magento\Checkout\Api\ShippingInformationManagementInterface
     */
    protected $shippingInformationManagement;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * The flag resource.
     *
     * @var \Magento\Framework\FlagManager
     */
    private $flagManager;

    /**
     * CheckoutRewardPoint constructor.
     * @param \Magento\Framework\App\State $appState
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Quote\Api\PaymentMethodManagementInterface $paymentMethodManagement
     * @param \Magento\Checkout\Model\PaymentDetailsFactory $paymentDetailsFactory
     * @param \Magento\Quote\Api\CartTotalRepositoryInterface $cartTotalsRepository
     * @param RewardQuoteFactory $rewardQuoteFactory
     * @param \Magento\Checkout\Api\ShippingInformationManagementInterface $shippingInformationManagement
     */
    public function __construct(
        \Magento\Framework\App\State $appState,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Quote\Api\PaymentMethodManagementInterface $paymentMethodManagement,
        \Magento\Checkout\Model\PaymentDetailsFactory $paymentDetailsFactory,
        \Magento\Quote\Api\CartTotalRepositoryInterface $cartTotalsRepository,
        \Riki\Loyalty\Model\RewardQuoteFactory $rewardQuoteFactory,
        \Magento\Checkout\Api\ShippingInformationManagementInterface $shippingInformationManagement,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\FlagManager $flagManager
    )
    {
        $this->_appState = $appState;
        $this->_quoteRepository = $quoteRepository;
        $this->_paymentMethodManagement = $paymentMethodManagement;
        $this->_paymentDetailsFactory = $paymentDetailsFactory;
        $this->_cartTotalsRepository = $cartTotalsRepository;
        $this->_rewardQuoteFactory = $rewardQuoteFactory;
        $this->shippingInformationManagement = $shippingInformationManagement;
        $this->registry = $registry;
        $this->flagManager = $flagManager;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @throws InputException
     * @throws LocalizedException
     */
    private function validateQuote(\Magento\Quote\Model\Quote $quote)
    {
        if (!$quote->getId()) {
            throw new InputException(__('This quote is no longer exist'));
        }
    }

    /**
     * {@inheritDoc}
     */
    public function saveShippingInformationAndApplyRewardPoint(
        $cartId,
        \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation,
        $usedPoints,
        $option
    )
    {
        $this->applyRewardPoint($cartId, $usedPoints, $option);

        return $this->shippingInformationManagement->saveAddressInformation($cartId, $addressInformation);
    }

    /**
     * {@inheritDoc}
     */
    public function applyRewardPoint($cartId, $usedPoints, $option)
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        if ($this->_appState->getAreaCode() == 'webapi_rest') {
            $quote = $this->_quoteRepository->getActive($cartId);
        } else {
            $quote = $this->_quoteRepository->get($cartId);
        }

        $this->validateQuote($quote);
        // NED-3837 log data
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/NED3837.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        /*RMM-375 applies point for trial*/
        if($quote->getData('point_for_trial') > 0 ) {
            $usedPoints = $quote->getData('point_for_trial');
            $option = 1;
            $logger->info('Quote ID: #'. $quote->getId());
            $logger->info('Reward user setting: ' . $option);
            $logger->info('Reward user redeem: ' . $usedPoints);
        }

        /** @var \Riki\Loyalty\Model\RewardQuote $rewardQuote */
        $rewardQuote = $this->_rewardQuoteFactory->create();
        $rewardQuote->load($quote->getId(), 'quote_id');
        $rewardQuote->setQuoteId($cartId);
        $rewardQuote->setRewardUserSetting($option);
        $rewardQuote->setRewardUserRedeem($usedPoints);
        try {
            $rewardQuote->save();
            if (!$this->registry->registry(\Riki\AdvancedInventory\Helper\OutOfStock\Quote::RIKI_IS_OOS_QUOTE_ID)) {
                $quote->collectTotals();
                $this->_quoteRepository->save($quote);
            }
        } catch (\Exception $e) {
            $logger->info($e);
            if ($quote->getData('point_for_trial') > 0 ) {
                // NED-5510 Save trial point reward data to registry for backup
                $flagcode = $quote->getId() . '_' . $quote->getCustomerId() . '_' . $quote->getRikiCourseId();
                $this->flagManager->saveFlag($flagcode, $rewardQuote->getData());
            }
            //
            throw new LocalizedException(__('Unable to apply reward point. Please, check input data.'));
        }

        /** @var \Magento\Checkout\Api\Data\PaymentDetailsInterface $paymentDetails */
        $paymentDetails = $this->_paymentDetailsFactory->create();
        $paymentDetails->setPaymentMethods($this->_paymentMethodManagement->getList($cartId));
        $paymentDetails->setTotals($this->_cartTotalsRepository->get($cartId));

        return $paymentDetails;
    }

    /**
     * {@inheritDoc}
     */
    public function removeRewardPoint($cartId)
    {
        $option = \Riki\Loyalty\Model\RewardQuote::USER_DO_NOT_USE_POINT;
        return $this->applyRewardPoint($cartId, 0, $option);
    }

    /**
     * {@inheritDoc}
     */
    public function useAllPoint($cartId)
    {
        $option = \Riki\Loyalty\Model\RewardQuote::USER_USE_ALL_POINT;
        return $this->applyRewardPoint($cartId, 0, $option);
    }

    /**
     * {@inheritDoc}
     */
    public function usePoint($cartId, $usedPoints)
    {
        $option = \Riki\Loyalty\Model\RewardQuote::USER_USE_SPECIFIED_POINT;
        return $this->applyRewardPoint($cartId, $usedPoints, $option);
    }
}
