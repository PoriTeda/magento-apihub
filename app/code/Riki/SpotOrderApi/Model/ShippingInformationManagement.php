<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Riki\SpotOrderApi\Model;

use Magento\Checkout\Exception;
use \Riki\SpotOrderApi\Api\ShippingInformationManagementInterface as RikiApiShippingInformationManagementInterface;
use \Magento\Checkout\Model\ShippingInformationManagement as ShippingDefaultMagento;

class ShippingInformationManagement extends ShippingDefaultMagento implements RikiApiShippingInformationManagementInterface
{

    /**
     * @var \Riki\Checkout\Model\ShippingAddress
     */
    protected $rikiShippingAddress;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var \Magento\Checkout\Model\Session\Proxy
     */
    protected $sessionCheckout;
    /**
     * @var \Riki\ShippingProvider\Model\Carrier
     */
    protected $shippingFeeCalculator;
    /**
     * @var \Magento\Framework\Webapi\Rest\Request
     */
    protected $request;
    /**
     * @var \Riki\SpotOrderApi\Helper\HandleMessageApi
     */
    protected $helperHandleMessage;
    /**
     * @var \Magento\Directory\Model\RegionFactory
     */
    protected $regionFactory;

    /**
     * ShippingInformationManagement constructor.
     * @param \Magento\Quote\Api\PaymentMethodManagementInterface $paymentMethodManagement
     * @param \Magento\Checkout\Model\PaymentDetailsFactory $paymentDetailsFactory
     * @param \Magento\Quote\Api\CartTotalRepositoryInterface $cartTotalsRepository
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Quote\Model\QuoteAddressValidator $addressValidator
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Quote\Model\Quote\TotalsCollector $totalsCollector
     * @param \Magento\Checkout\Model\Session\Proxy $proxy
     * @param \Riki\ShippingProvider\Model\Carrier $shippingFeeCalculator
     * @param \Magento\Framework\Webapi\Rest\Request $request
     * @param \Riki\SpotOrderApi\Helper\HandleMessageApi $helperHandleMessage
     */
    public function __construct(
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
        \Riki\SpotOrderApi\Helper\HandleMessageApi $helperHandleMessage,
        \Magento\Directory\Model\RegionFactory $regionFactory
    )
    {
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
        $this->regionFactory = $regionFactory;
    }

    /**
     * @param int $cartId
     * @param \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation
     * @return \Magento\Checkout\Api\Data\PaymentDetailsInterface
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\StateException
     */
    public function saveAddressInformation(
        $cartId,
        \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation
    )
    {
        try {
            /**
             * Set param for machine api
             */
            $this->request->setParam('call_spot_order_api', 'call_spot_order_api');

            $addressInformation = $this->convertRegionByCode($addressInformation);

            /**
             * Load quote
             */
            $quote = $this->quoteRepository->getActive($cartId);

            /**
             * Save shipping ,billing address
             */

            return parent::saveAddressInformation($cartId, $addressInformation);
        } catch (\Exception $e) {
            /**
             * Handel message
             */
            $arrMessage = $this->helperHandleMessage->handleMessage($e->getMessage(), $e->getFile());
            return $arrMessage;
        }
    }

    /**
     * @param $regionCode
     * @param $countryId
     * @param $region
     * @return null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getRegionIdByCode($regionCode,$countryId,$region)
    {
        $dataResult  = $this->regionFactory->create()->getCollection()
            ->addFieldToFilter('code',$regionCode)
            ->addFieldToFilter('country_id',$countryId)
            ->setPageSize(1);

        $result = null;
        if($dataResult->getSize()>0)
        {
            $firstItem = $dataResult->getFirstItem();
            if(strtolower(trim($region))==strtolower(trim($firstItem->getData('default_name'))))
            {
                $result = $firstItem->getRegionId();
            }
        }

        if($result!=null) {
            return $result;
        }else {
            throw new Exception( __( "The region is not valid"));
        }
    }

    /**
     * @param \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation
     * @return \Magento\Checkout\Api\Data\ShippingInformationInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function convertRegionByCode(\Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation)
    {
        $shipping = $addressInformation->getShippingAddress();
        $countryId = $shipping->getData('country_id');
        $regionCode = $shipping->getData('region_code');
        $region = $shipping->getData('region');
        $regionIdShipping = $this->getRegionIdByCode($regionCode,$countryId,$region);
        if($regionIdShipping !=null )
        {
            $addressInformation->getShippingAddress()->setRegion($regionIdShipping);
        }

        $billing = $addressInformation->getBillingAddress();
        $countryIdBilling = $billing->getData('country_id');
        $regionCodeBilling = $billing->getData('region_code');
        $regionBilling = $billing->getData('region');
        $regionIdBilling = $this->getRegionIdByCode($regionCodeBilling,$countryIdBilling,$regionBilling);
        if($regionIdBilling !=null )
        {
            $addressInformation->getBillingAddress()->setRegion($regionIdBilling);
        }

        return $addressInformation;
    }
}
