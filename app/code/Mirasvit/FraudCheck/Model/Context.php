<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-fraud-check
 * @version   1.0.38
 * @copyright Copyright (C) 2019 Mirasvit (https://mirasvit.com/)
 */


namespace Mirasvit\FraudCheck\Model;

use Magento\Framework\DataObject;
use Mirasvit\FraudCheck\Api\Service\MatchServiceInterface;
use Mirasvit\FraudCheck\Rule\IndicatorFactory;

/**
 * @method string getIp()
 *
 * @method string getFirstname()
 * @method string getLastname()
 * @method string getEmail()
 * @method int getOrderId()
 *
 * @method string getBillingCountry()
 * @method string getBillingCity()
 * @method string getBillingState()
 * @method string getBillingStreet()
 * @method string getBillingPostcode()
 * @method string getBillingPhone()
 *
 * @method string getShippingCountry()
 * @method string getShippingCity()
 * @method string getShippingState()
 * @method string getShippingStreet()
 * @method string getShippingPostcode()
 * @method string getShippingPhone()
 *
 * @method float getGrandTotal()
 */
class Context extends DataObject
{
    /**
     * @var IndicatorFactory
     */
    private $indicatorFactory;

    /**
     * @var MatchServiceInterface
     */
    private $matchService;

    /**
     * @var \Magento\Sales\Model\Order
     */
    public $order;

    public function __construct(
        IndicatorFactory $indicatorFactory,
        MatchServiceInterface $matchService
    ) {
        $this->indicatorFactory = $indicatorFactory;
        $this->matchService = $matchService;

        parent::__construct();
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return $this
     */
    public function extractOrderData($order)
    {
        $this->order = $order;

        $this->setData([
            'ip'          => $order->getRemoteIp() != '127.0.0.1' ? $order->getRemoteIp() : $order->getXForwardedFor(),
            'firstname'   => $order->getCustomerFirstname(),
            'lastname'    => $order->getCustomerLastname(),
            'email'       => $order->getCustomerEmail(),
            'order_id'    => $order->getId(),
            'grand_total' => $order->getBaseGrandTotal(),
        ]);

        if ($order->getBillingAddress()) {
            $address = $order->getBillingAddress();
            $this->addData([
                'billing_country'  => $address->getCountryId(),
                'billing_city'     => $address->getCity(),
                'billing_state'    => $address->getRegion(),
                'billing_street'   => implode(', ', $address->getStreet()),
                'billing_postcode' => $address->getPostcode(),
                'billing_phone'    => $address->getTelephone(),
            ]);

            if (!$this->getData('firstname')) {
                $this->setData('firstname', $address->getFirstname());
            }
            if (!$this->getData('lastname')) {
                $this->setData('lastname', $address->getLastname());
            }
        }

        if ($order->getShippingAddress()) {
            $address = $order->getShippingAddress();
            $this->addData([
                'shipping_country'  => $address->getCountryId(),
                'shipping_city'     => $address->getCity(),
                'shipping_state'    => $address->getRegion(),
                'shipping_street'   => implode(', ', $address->getStreet()),
                'shipping_postcode' => $address->getPostcode(),
                'shipping_phone'    => $address->getTelephone(),
            ]);
        }

        return $this;
    }

    /**
     * @return IndicatorFactory
     */
    public function getIndicatorFactory()
    {
        return $this->indicatorFactory;
    }

    /**
     * @return MatchServiceInterface
     */
    public function getMatchService()
    {
        return $this->matchService;
    }
}