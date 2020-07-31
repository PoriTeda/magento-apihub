<?php
/**
 * Nestle Purina Vets
 * PHP version 7
 *
 * @category Nestle_Purina
 * @package  Nestle\Purina
 * @author   Nestle <support@nestle.co.jp>
 * @license  http://nestle.co.jp/policy.html GNU General Public License
 * @link     http://vets.nestle.jp
 */
namespace Nestle\Purina\Model;

use Magento\Framework\Exception\InputException;
use Magento\Checkout\Api\Data\ShippingInformationInterface;

/**
 * Class ShippingInformationManagement
 *
 * @category Nestle_Purina
 * @package  Nestle\Purina
 * @author   Nestle <support@nestle.co.jp>
 * @license  http://nestle.co.jp/policy.html GNU General Public License
 * @link     http://vets.nestle.jp
 */
class ShippingInformationManagement
    extends \Magento\Checkout\Model\ShippingInformationManagement
    implements \Nestle\Purina\Api\ShippingInformationManagementInterface
{

    /**
     * Reward point
     *
     * @var \Riki\Loyalty\Model\CheckoutRewardPoint
     */
    protected $checkoutRewardPoint;

    /**
     * Shipping carrier
     *
     * @var \Riki\ShippingProvider\Model\Carrier
     */
    protected $shippingFeeCalculator;

    /**
     * Checkout proxy
     *
     * @var \Magento\Checkout\Model\Session\Proxy
     */
    protected $sessionCheckout;

    /**
     * Request
     *
     * @var \Magento\Framework\Webapi\Rest\Request
     */
    protected $request;

    /**
     * Message Api
     *
     * @var \Riki\SpotOrderApi\Helper\HandleMessageApi
     */
    protected $helperHandleMessage;

    /**
     * Region
     *
     * @var \Magento\Directory\Model\RegionFactory
     */
    protected $regionFactory;

    /**
     * ShippingInformationManagement constructor.
     *
     * @param \Riki\Loyalty\Model\CheckoutRewardPoint             $checkoutRewardPoint    checkout reward point
     * @param \Magento\Quote\Api\PaymentMethodManagementInterface $paymentMethodManagement payment method
     * @param \Magento\Checkout\Model\PaymentDetailsFactory       $paymentDetailsFactory   payment details
     * @param \Magento\Quote\Api\CartTotalRepositoryInterface     $cartTotalsRepository    cart total
     * @param \Magento\Quote\Api\CartRepositoryInterface          $quoteRepository         quote repository
     * @param \Magento\Quote\Model\QuoteAddressValidator          $addressValidator        address validator
     * @param \Psr\Log\LoggerInterface                            $logger                  logger
     * @param \Magento\Customer\Api\AddressRepositoryInterface    $addressRepository       address repository
     * @param \Magento\Framework\App\Config\ScopeConfigInterface  $scopeConfig             scope config
     * @param \Magento\Quote\Model\Quote\TotalsCollector          $totalsCollector         total collector
     * @param \Magento\Checkout\Model\Session\Proxy               $proxy                   proxy
     * @param \Riki\ShippingProvider\Model\Carrier                $shippingFeeCalculator   shipping fee calculator
     * @param \Magento\Framework\Webapi\Rest\Request              $request                 request
     * @param \Riki\SpotOrderApi\Helper\HandleMessageApi          $helperHandleMessage     message
     */
    public function __construct(
        \Riki\Loyalty\Model\CheckoutRewardPoint $checkoutRewardPoint,
        \Magento\Quote\Api\PaymentMethodManagementInterface $paymentMethodManagement,
        \Magento\Checkout\Model\PaymentDetailsFactory $paymentDetailsFactory,
        \Magento\Quote\Api\CartTotalRepositoryInterface $cartTotalsRepository,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Quote\Model\QuoteAddressValidator $addressValidator,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\TotalsCollector $totalsCollector,
        \Magento\Checkout\Model\Session\Proxy $proxy,
        \Riki\ShippingProvider\Model\Carrier $shippingFeeCalculator,
        \Magento\Framework\Webapi\Rest\Request $request,
        \Riki\SpotOrderApi\Helper\HandleMessageApi $helperHandleMessage
    ) {
        $this->checkoutRewardPoint = $checkoutRewardPoint;
        parent::__construct(
            $paymentMethodManagement,
            $paymentDetailsFactory,
            $cartTotalsRepository,
            $quoteRepository,
            $addressValidator,
            $logger,
            $addressRepository,
            $scopeConfig,
            $totalsCollector
        );
        $this->quoteRepository = $quoteRepository;
        $this->sessionCheckout = $proxy;
        $this->shippingFeeCalculator = $shippingFeeCalculator;
        $this->request = $request;
        $this->helperHandleMessage = $helperHandleMessage;
    }

    /**
     * Apply point and place order
     *
     * @param int                          $cartId             cart_id
     * @param ShippingInformationInterface $addressInformation address_info
     * @param int                          $usedPoints         points
     * @param int                          $option             point_option
     *
     * @return \Magento\Checkout\Api\Data\PaymentDetailsInterface|mixed
     */
    public function applyPointAndSaveAddressInformation(
        $cartId,
        ShippingInformationInterface $addressInformation,
        $usedPoints,
        $option
    ) {
        $this->request->setParam('from_purina_api', '1');
        $this->request->setParam('call_spot_order_api', 'call_spot_order_api');
        if ($usedPoints > 0) {
            $this->checkoutRewardPoint->applyRewardPoint($cartId, $usedPoints, $option);
        }
        //NED-8313 hard code riki_address_type for Purina API as home
        $addressInformation->getShippingAddress()->setCustomAttribute('riki_type_address', 'home');
        $addressInformation->getBillingAddress()->setCustomAttribute('riki_type_address', 'home');
        try {
            $result = parent::saveAddressInformation($cartId, $addressInformation);
            // Set purina quote to inactive , avoid same quote with EC
            $quote = $this->quoteRepository->getActive($cartId);
            $quote->setIsActive(0);
            $this->quoteRepository->save($quote);
            return $result;
        } catch (\Exception $e) {
            $arrMessage = $this->helperHandleMessage->handleMessage(
                $e->getMessage(),
                $e->getFile()
            );
            return $arrMessage;
        }
    }

    protected function validateQuote(\Magento\Quote\Model\Quote $quote)
    {
        if (0 == $quote->getItemsCount()) {
            throw new InputException(
                __("The shipping method can't be set for an empty cart. Add an item to cart and try again.")
            );
        } else {
            $quote->setIsActive(1);
        }
    }
}
