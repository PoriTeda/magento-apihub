<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\MachineApi\Model;

use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Framework\Exception\InputException;
use Psr\Log\LoggerInterface as Logger;
use Riki\MachineApi\Api\BillingAddressManagementInterface;
use Magento\Framework\Exception\NoSuchEntityException;

/** Quote billing address write service object. */
class BillingAddressManagement implements BillingAddressManagementInterface
{
    /**
     * Validator.
     *
     * @var QuoteAddressValidator
     */
    protected $addressValidator;

    /**
     * Logger.
     *
     * @var Logger
     */
    protected $logger;

    /**
     * Quote repository.
     *
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var \Magento\Customer\Api\AddressRepositoryInterface
     */
    protected $addressRepository;

    public $quoteData;

    /**
     * Constructs a quote billing address service object.
     *
     * @param \Riki\MachineApi\Api\CartRepositoryInterface $quoteRepository Quote repository.
     * @param QuoteAddressValidator $addressValidator Address validator.
     * @param Logger $logger Logger.
     * @param \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
     */
    public function __construct(
        \Riki\MachineApi\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Quote\Model\QuoteAddressValidator $addressValidator,
        Logger $logger,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
    ) {
        $this->addressValidator = $addressValidator;
        $this->logger = $logger;
        $this->quoteRepository   = $quoteRepository;
        $this->addressRepository = $addressRepository;
    }

    /**
     * Get quote item by cart id
     *
     * @param $cartId
     * @return \Magento\Quote\Model\QuoteRepository
     */
    public function getQuoteRepository($cartId)
    {
        if (!$this->quoteData instanceof \Magento\Quote\Model\Quote )
        {
            $this->quoteData = $this->quoteRepository->get($cartId);
        }
        return $this->quoteData;
    }

    /**
     * {@inheritDoc}
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function assign($cartId, \Magento\Quote\Api\Data\AddressInterface $address, $useForShipping = false)
    {
        $this->addressValidator->validate($address);

        return $this->assignAddressDataToQuote($cartId, $address, $useForShipping);
    }

    /**
     * Assign or change address data for quote
     *
     * @param $cartId
     * @param $address
     * @param $useForShipping
     * @return mixed
     * @throws InputException
     */
    public function assignAddressDataToQuote($cartId, $address, $useForShipping)
    {
        $quote = $this->getQuoteRepository($cartId);
        $customerAddressId = $address->getCustomerAddressId();
        $shippingAddress = null;

        if ($useForShipping) {
            $shippingAddress = $address;
        }

        $saveInAddressBook = $address->getSaveInAddressBook() ? 1 : 0;

        if ($customerAddressId) {
            $addressData = $this->addressRepository->getById($customerAddressId);
            $address = $quote->getBillingAddress()->importCustomerAddressData($addressData);
            if ($useForShipping) {
                $shippingAddress = $quote->getShippingAddress()->importCustomerAddressData($addressData);
                $shippingAddress->setSaveInAddressBook($saveInAddressBook);
            }
        } elseif ($quote->getCustomerId()) {
            $address->setEmail($quote->getCustomerEmail());
        }
        $address->setSaveInAddressBook($saveInAddressBook);
        $quote->setBillingAddress($address);

        if ($useForShipping) {
            $shippingAddress->setSameAsBilling(1);
            $shippingAddress->setCollectShippingRates(true);
            $quote->setShippingAddress($shippingAddress);
        }
        $quote->setDataChanges(true);

        //calculate when place order
        //$quote->collectTotals();
        try {
            $quote->save($quote);
            //$this->quoteRepository->save($quote);
        } catch (\Exception $e) {
            $this->logger->critical($e);
            throw new InputException(__('Unable to save address. Please, check input data.'));
        }

        return $quote->getBillingAddress()->getId();
    }

    /**
     * {@inheritDoc}
     */
    public function get($cartId)
    {
        $cart = $this->quoteRepository->getActive($cartId);
        return $cart->getBillingAddress();
    }
}
