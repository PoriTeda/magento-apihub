<?php
namespace Riki\SapIntegration\Helper;

use Magento\OfflinePayments\Model\Cashondelivery;
use Magento\Payment\Model\Method\Free;
use Bluecom\Paygent\Model\Paygent;
use Riki\SapIntegration\Model\Config\Source\Options;
use Riki\PaymentBip\Model\InvoicedBasedPayment;
use Riki\CvsPayment\Model\CvsPayment;
use Riki\SapIntegration\Model\Api\Shipment as ShipmentApi;
use Riki\Customer\Model\Shosha\ShoshaCode;
use Riki\SapIntegration\Api\ConfigInterface;
use Riki\NpAtobarai\Model\Payment\NpAtobarai;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const DISTRIBUTION_CHANNEL_CONFIG = 'sap_integration_config/sap_customer_id/year';

    const TOYO_POINT = 'TOYO';
    const BIZEX_POINT = 'BIZEX';
    const TOYO_CODE = '2435';
    const BIZEX_CODE = '6600';
    const CARRIER_CODE_ASKUL = 'askul';
    const CARRIER_CODE_ASKUL_TYPE_BIZEX = 'bizex';
    const CARRIER_CODE_ASKUL_TYPE_ANSHIN = 'anshin';
    const CARRIER_CODE_ASKUL_TYPE_YUPACK = 'yupack';
    const CARRIER_CODE_YAMATO = 'yamato';
    const CARRIER_CODE_YAMATO_TYPE_ASKUL = 'yamatoaskul';
    const CARRIER_CODE_YAMATO_TYPE_GLOBAL = 'yamatoglobal';
    const CARRIER_CODE_WELLNET = 'wellnet';
    const CARRIER_CODE_NP = 'np';
    const CARRIER_CODE_POINT_PURCHASE = 'point_purchase';
    const CARRIER_CODE_KINKI = 'kinki';
    const CARRIER_CODE_TOKAI = 'tokai';
    const CARRIER_CODE_ECOHAI = 'ecohai';
    const CARRIER_CODE_SAGAWA = 'sagawa';
    const CUSTOMER_CODE_YAMATO_GLOBAL = 'yamato_global_express';
    const CUSTOMER_CODE_ECOHAI = 'ecohai';
    const CUSTOMER_CODE_SAGAWA = 'sagawa';
    const TAX_CODE_EIGHT_PERCENT_BEFORE_CHANGE_DATE = 'A3';
    const TAX_CODE_EIGHT_PERCENT_AFTER_CHANGE_DATE = 'A4';
    const TAX_CODE_TEN_PERCENT = 'A5';

    /**
     * @var \Magento\Framework\App\Config
     */
    protected $_appConfig;

    /**
     * @var \Riki\Customer\Model\ResourceModel\Shosha\CollectionFactory
     */
    protected $shoshaCollection;

    /**
     * @var \Riki\Framework\Helper\Cache\FunctionCache
     */
    protected $functionCache;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var \Riki\Framework\Helper\Search
     */
    protected $searchHelper;

    /**
     * @var \Magento\Sales\Api\OrderItemRepositoryInterface
     */
    protected $orderItemRepository;

    /**
     * @var \Riki\Framework\Helper\ScopeConfig
     */
    protected $scopeConfigHelper;

    /**
     * @var \Magento\Sales\Api\ShipmentTrackRepositoryInterface
     */
    protected $shipmentTrackRepository;

    /**
     * @var \Wyomind\PointOfSale\Model\PointOfSaleFactory
     */
    protected $pointOfSaleFactory;

    /**
     * @var \Magento\Rma\Api\TrackRepositoryInterface
     */
    protected $rmaTrackRepository;

    /**
     * @var \Riki\Rma\Api\ReasonRepositoryInterface
     */
    protected $reasonRepository;

    /**
     * Data constructor.
     *
     * @param \Riki\Rma\Api\ReasonRepositoryInterface $reasonRepository
     * @param \Magento\Rma\Api\TrackRepositoryInterface $rmaTrackRepository
     * @param \Wyomind\PointOfSale\Model\PointOfSaleFactory $pointOfSaleFactory
     * @param \Magento\Sales\Api\ShipmentTrackRepositoryInterface $shipmentTrackRepository
     * @param \Riki\Framework\Helper\ScopeConfig $scopeConfigHelper
     * @param \Magento\Sales\Api\OrderItemRepositoryInterface $orderItemRepository
     * @param \Riki\Framework\Helper\Search $searchHelper
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Riki\Framework\Helper\Cache\FunctionCache $functionCache
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\App\Config $appConfig
     * @param \Riki\Customer\Model\ResourceModel\Shosha\CollectionFactory $shoshaCollection
     */
    public function __construct(
        \Riki\Rma\Api\ReasonRepositoryInterface $reasonRepository,
        \Magento\Rma\Api\TrackRepositoryInterface $rmaTrackRepository,
        \Wyomind\PointOfSale\Model\PointOfSaleFactory $pointOfSaleFactory,
        \Magento\Sales\Api\ShipmentTrackRepositoryInterface $shipmentTrackRepository,
        \Riki\Framework\Helper\ScopeConfig $scopeConfigHelper,
        \Magento\Sales\Api\OrderItemRepositoryInterface $orderItemRepository,
        \Riki\Framework\Helper\Search $searchHelper,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Riki\Framework\Helper\Cache\FunctionCache $functionCache,
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\Config $appConfig,
        \Riki\Customer\Model\ResourceModel\Shosha\CollectionFactory $shoshaCollection
    ) {
        $this->reasonRepository = $reasonRepository;
        $this->rmaTrackRepository = $rmaTrackRepository;
        $this->pointOfSaleFactory = $pointOfSaleFactory;
        $this->shipmentTrackRepository = $shipmentTrackRepository;
        $this->scopeConfigHelper = $scopeConfigHelper;
        $this->orderItemRepository = $orderItemRepository;
        $this->searchHelper = $searchHelper;
        $this->orderRepository = $orderRepository;
        $this->functionCache = $functionCache;

        parent::__construct($context);
        $this->_appConfig = $appConfig;
        $this->shoshaCollection = $shoshaCollection;
    }

    /**
     * getDistributionChannel depend on sales organization or customer type
     *
     * @param  boolean $isAmbSale is amb sales or not
     * @param  string $salesOrgLabel [attribute of product tpe]
     * @return string [number of channel]
     */
    public function getDistributionChannel($isAmbSale, $salesOrgLabel = '')
    {
        $settings = $this->_appConfig->getValue(self::DISTRIBUTION_CHANNEL_CONFIG);

        if ($settings === Options::SETTINGS_2016) {
            return $salesOrgLabel == 'JP30' || $salesOrgLabel == 'JP36' ? '14' : ($isAmbSale ? '02' : '14');
        }

        return $isAmbSale ? '06' : '14';
    }

    /**
     * Check current is use 2017 setting or not
     *
     * @return bool
     */
    public function isUse2017Settings()
    {
        $settings = $this->_appConfig->getValue(self::DISTRIBUTION_CHANNEL_CONFIG);

        return $settings === Options::SETTINGS_2017;
    }

    /**
     * Get SAP customer ID
     *
     * @param \Magento\Sales\Model\Order $order
     * @return string|null
     */
    public function sapCustomerId($order)
    {
        $payment = $order->getPayment();
        if (!$payment) {
            return null;
        }
        $sapCodes = $this->sapCustomerConfig();
        switch ($payment->getMethod()) {
            case Free::PAYMENT_METHOD_FREE_CODE:
                return $sapCodes['POINT_PURCHASE']['code'];

            case Paygent::CODE:
                $paymentAgent = strtoupper($order->getData('payment_agent'));
                if (isset($sapCodes['CC'][$paymentAgent])) {
                    return $sapCodes['CC'][$paymentAgent]['code'];
                }
                return null;

            case Cashondelivery::PAYMENT_METHOD_CASHONDELIVERY_CODE:
            case CvsPayment::PAYMENT_METHOD_CVS_CODE:
                $carrierCode = strtoupper($order->getData('carrier_code'));
                if ($carrierCode == 'ANSHIN') {
                    $carrierCode = 'ASKUL';
                }
                if (isset($sapCodes['COD_CVS'][$carrierCode])) {
                    return $sapCodes['COD_CVS'][$carrierCode]['code'];
                }
                return null;

            case InvoicedBasedPayment::PAYMENT_CODE:
                $shosha = $order->getData('shosha_business_code');
                if (!empty($shosha)) {
                    $shoshaCollection = $this->shoshaCollection->create();
                    $shoshaCollection->addFieldToFilter('shosha_business_code', $shosha);

                    if (!empty($shoshaCollection->getSize())) {
                        foreach ($shoshaCollection->getItems() as $value) {
                            $shoshaCode = $value->getData('shosha_code');
                        }
                        return $sapCodes['INVOICE'][$shoshaCode]['code'];
                    } else {
                        return null;
                    }
                }
                return null;

            default:
                return null;
        }
    }

    /**
     * SAP customer ID mapping
     *
     * @return array
     */
    public function sapCustomerConfig()
    {
        return [
            'CC' => [
                'NICOS' => [
                    'id' => 'NICOS',
                    'code' => $this->getSAPCustomerCode('NICOS')
                ],
                'NICOS2' => [
                    'id' => 'NICOS2',
                    'code' => $this->getSAPCustomerCode('NICOS2')
                ],
                'JCB' => [
                    'id' => 'JCB',
                    'code' => $this->getSAPCustomerCode('JCB')
                ],
                'JCB2' => [
                    'id' => 'JCB2',
                    'code' => $this->getSAPCustomerCode('JCB2')
                ]
            ],
            'COD_CVS' => [
                'YAMATOASKUL' => [
                    'id' => 'YAMATO',
                    'code' => $this->getSAPCustomerCode('YAMATO')
                ],
                'ASKUL' => [
                    'id' => 'ASKUL',
                    'code' => $this->getSAPCustomerCode('ASKUL')
                ],
                'WELLNET' => [
                    'id' => 'WELLNET',
                    'code' => $this->getSAPCustomerCode('WELLNET')
                ],
            ],
            'INVOICE' => [
                ShoshaCode::ITOCHU => [
                    'id' => 'ITOCHU',
                    'code' => $this->getSAPCustomerCode('ITOCHU')
                ],
                ShoshaCode::MC => [
                    'id' => 'MISUBISHI',
                    'code' => null
                ],
                ShoshaCode::CEDYNA => [
                    'id' => 'CEDYNA',
                    'code' => $this->getSAPCustomerCode('CEDYNA')
                ],
                ShoshaCode::FKJEN => [
                    'id' => 'FUKUJUEN',
                    'code' => $this->getSAPCustomerCode('FUKUJUEN')
                ],
                ShoshaCode::LUPICIA => [
                    'id' => 'LUPICIA',
                    'code' => $this->getSAPCustomerCode('LUPICIA')
                ]
            ],
            'POINT_PURCHASE' => [
                'id' => 'POINT PURCHASE',
                'code' => $this->getSAPCustomerCode('POINT_PURCHASE')
            ],
        ];
    }

    /**
     * Get sap customer code
     *
     * @param string $sapCustomerId
     * @return string
     */
    public function getSAPCustomerCode($sapCustomerId)
    {
        $xPathTemplate = 'sap_integration_config/sap_customer_id/%s';
        $xPathConfig = sprintf($xPathTemplate, strtolower($sapCustomerId));
        return $this->_appConfig->getValue($xPathConfig);
    }

    /**
     * Get warehouse code to send SAP
     *
     * @param string $wareHouse
     * @return string|null
     */
    public function getWareHouse($wareHouse)
    {
        switch (strtoupper($wareHouse)) {
            case self::TOYO_POINT:
                return self::TOYO_CODE;
            case self::BIZEX_POINT:
                return self::BIZEX_CODE;
            default:
                return null;
        }
    }

    /**
     * @return array
     */
    public function getFlagOptions()
    {
        return [
            ShipmentApi::NO_NEED_TO_EXPORT => __('No need to export to SAP'),
            ShipmentApi::WAITING_FOR_EXPORT => __('Waiting for export'),
            ShipmentApi::EXPORTED_TO_SAP => __('Exported to SAP'),
            ShipmentApi::FAILED_TO_EXPORT => __('Failed to export to SAP'),
        ];
    }

    /**
     * @return mixed
     */
    public function isRmaEnable()
    {
        $xPathConfig = 'sap_integration_config/export_rma/enable';
        return $this->_appConfig->getValue($xPathConfig);
    }

    /**
     * @return mixed
     */
    public function isShipmentEnable()
    {
        $xPathConfig = 'sap_integration_config/export_shipment/enable';
        return $this->_appConfig->getValue($xPathConfig);
    }

    /**
     * Get unit ecom
     *
     * @param \Magento\Framework\DataObject $object
     *
     * @return null|string
     */
    public function getUnitEcom(\Magento\Framework\DataObject $object)
    {
        if ($object instanceof \Magento\Sales\Model\Order\Shipment) {
            return ShipmentApi::UNIT_ECOM_DEFAULT;
        } elseif ($object instanceof \Magento\Rma\Model\Rma) {
            return ShipmentApi::UNIT_ECOM_DEFAULT;
        }

        return null;
    }

    /**
     * Get reason code
     *
     * @param \Magento\Framework\DataObject $object
     *
     * @return string|null
     */
    public function getReasonCode(\Magento\Framework\DataObject $object)
    {
        $id = null;
        if ($object instanceof \Magento\Sales\Model\Order\Shipment) {
            $id = 'shipment' . ($this->getOrder($object) ? $this->getOrder($object)->getData('substitution') : '_');
        } elseif ($object instanceof \Magento\Rma\Model\Rma) {
            $id = 'rma' . $object->getData('reason_id');
        }

        if ($this->functionCache->has($id)) {
            return $this->functionCache->load($id);
        }

        $result = null;
        if ($object instanceof \Magento\Sales\Model\Order\Shipment) {
            /*get order data*/
            $order = $this->getOrder($object);

            if ($order) {
                /*default reason code for order which substitution is 1 or order chanel is marchine maintenance*/
                if ($order->getData('substitution')
                    || $order->getData('order_channel') == \Riki\Sales\Model\Config\Source\OrderChannel::ORDER_CHANEL_TYPE_MACHINE_API
                ) {
                    $result = ShipmentApi::SAP_REASON_CODE_DEFAULT;
                }
            }
        } elseif ($object instanceof \Magento\Rma\Model\Rma) {
            $reason = $this->searchHelper
                ->getById(intval($object->getData('reason_id')))
                ->getOne()
                ->execute($this->reasonRepository);
            if ($reason) {
                $result = $reason->getData('sap_code');
            }
        }

        $this->functionCache->store($result, $id);

        return $result;
    }

    /**
     * Get distribute channel from shipment item, rma item
     *      if null get from order item
     *
     * @param \Magento\Framework\DataObject $object
     * @param \Magento\Framework\DataObject $orderItem
     *
     * @return null|string
     */
    public function getDistributeChannel(
        \Magento\Framework\DataObject $object,
        \Magento\Framework\DataObject $orderItem
    ) {
        if ($object instanceof \Magento\Sales\Model\Order\Shipment\Item
            || $object instanceof \Magento\Rma\Model\Item
        ) {
            $distributionChannel = $object->getData('distribution_channel');

            if (empty($distributionChannel)) {
                if ($orderItem instanceof \Magento\Sales\Model\Order\Item) {
                    $distributionChannel = $orderItem->getData('distribution_channel');
                }
            }

            return $distributionChannel;
        }

        return null;
    }

    /**
     * Get order model
     *
     * @param \Magento\Framework\DataObject $object
     *
     * @return  \Magento\Sales\Model\Order|null
     */
    public function getOrder(\Magento\Framework\DataObject $object)
    {
        $id = null;
        if ($object instanceof \Magento\Sales\Model\Order\Shipment) {
            $id = $object->getOrderId();
        } elseif ($object instanceof \Magento\Rma\Model\Rma) {
            $id = $object->getOrderId();
        }

        if ($this->functionCache->has($id)) {
            return $this->functionCache->load($id);
        }

        $result = $this->searchHelper
            ->getByEntityId($id)
            ->getOne()
            ->execute($this->orderRepository);

        $this->functionCache->store($result, $id);

        return $result;
    }

    /**
     * Get order item model
     *
     * @param \Magento\Framework\DataObject $object
     *
     * @return \Magento\Sales\Model\Order\Item|null
     */
    public function getOrderItem(\Magento\Framework\DataObject $object)
    {
        $id = null;
        if ($object instanceof \Magento\Sales\Model\Order\Shipment\Item) {
            $id = $object->getOrderItemId();
        } elseif ($object instanceof \Magento\Rma\Model\Item) {
            $id = $object->getOrderItemId();
        }

        if ($this->functionCache->has($id)) {
            return $this->functionCache->load($id);
        }

        $result = $this->searchHelper
            ->getByItemId($id)
            ->getOne()
            ->execute($this->orderItemRepository);

        $this->functionCache->store($result, $id);

        return $result;
    }

    /**
     * Get order payment model
     *
     * @param \Magento\Framework\DataObject $object
     *
     * @return \Magento\Sales\Model\Order\Payment|null
     */
    public function getOrderPayment(\Magento\Framework\DataObject $object)
    {
        $id = null;
        if ($object instanceof \Magento\Sales\Model\Order\Shipment) {
            $id = $object->getOrderId();
        } else if ($object instanceof \Magento\Rma\Model\Rma) {
            $id = $object->getOrderId();
        }

        if ($this->functionCache->has($id)) {
            return $this->functionCache->load($id);
        }

        $result = null;
        $order = $this->getOrder($object);
        if ($order) {
            $result = $order->getPayment();
        }

        $this->functionCache->store($result, $id);

        return $result;
    }

    /**
     * Get method of payment
     *
     * @param \Magento\Framework\DataObject $object
     *
     * @return string
     */
    public function getOrderPaymentMethodCode(\Magento\Framework\DataObject $object)
    {
        $id = null;
        if ($object instanceof \Magento\Sales\Model\Order\Shipment) {
            $id = $object->getOrderId();
        } else if ($object instanceof \Magento\Rma\Model\Rma) {
            $id = $object->getOrderId();
        }

        if ($this->functionCache->has($id)) {
            return $this->functionCache->load($id);
        }

        $result = '';
        $payment = $this->getOrderPayment($object);
        if ($payment) {
            $result = $payment->getMethod();
        }

        $this->functionCache->store($result, $id);

        return $result;
    }

    /**
     * Get customer sap id
     *
     * @param \Magento\Framework\DataObject $object
     *
     * @return string|null
     */
    public function getCustomerSapId(\Magento\Framework\DataObject $object)
    {
        $result = '';

        $order = $this->getOrder($object);
        if (!$order) {
            return '';
        }

        $methodCode = $this->getOrderPaymentMethodCode($object);

        /*return payment agent data for order which payment method is paygent*/
        if ($methodCode == Paygent::CODE) {

            return $this->getOrderPaymentAgent($order);

        } else if ($methodCode == CvsPayment::PAYMENT_METHOD_CVS_CODE) {
            /*default carrier for cvs is wellnet*/
            $result = self::CARRIER_CODE_WELLNET;
        } elseif ($methodCode == NpAtobarai::PAYMENT_METHOD_NP_ATOBARAI_CODE) {
            /*default carrier for npatobarai is np*/
            $result = self::CARRIER_CODE_NP;
        } elseif ($methodCode == Cashondelivery::PAYMENT_METHOD_CASHONDELIVERY_CODE) {
            /*get carrier code for shipment or rma*/
            $carrierCode = $this->getCarrierCodeFromObject($object);

            if ($carrierCode) {
                if ( $carrierCode == self::CARRIER_CODE_ASKUL_TYPE_ANSHIN
                    || $carrierCode == self::CARRIER_CODE_ASKUL_TYPE_BIZEX
                    || $carrierCode == self::CARRIER_CODE_ASKUL_TYPE_YUPACK
                ) {
                    $result = self::CARRIER_CODE_ASKUL;
                } elseif ($carrierCode == self::CARRIER_CODE_YAMATO_TYPE_ASKUL) {
                    $result = self::CARRIER_CODE_YAMATO;
                } elseif ($carrierCode == self::CARRIER_CODE_KINKI) {
                    $result = self::CARRIER_CODE_KINKI;
                } elseif ($carrierCode == self::CARRIER_CODE_TOKAI) {
                    $result = self::CARRIER_CODE_TOKAI;
                } elseif ($carrierCode == self::CARRIER_CODE_YAMATO_TYPE_GLOBAL) {
                    $result = self::CUSTOMER_CODE_YAMATO_GLOBAL;
                } elseif ($carrierCode == self::CARRIER_CODE_ECOHAI) {
                    $result = self::CUSTOMER_CODE_ECOHAI;
                } elseif ($carrierCode == self::CARRIER_CODE_SAGAWA) {
                    $result = self::CUSTOMER_CODE_SAGAWA;
                }
            }
        } elseif ($methodCode == InvoicedBasedPayment::PAYMENT_CODE) {

            /*get shosha code for invoice order from order object*/
            $shoshaCode = $order->getData('shosha_business_code');

            if ($shoshaCode) {
                /*get carrier code by shosha code*/
                $carrierCode = $this->getCarrierCodeByShoshaCode($shoshaCode);

                if ($carrierCode) {
                    $result = $carrierCode;
                }
            }
        } elseif ($methodCode == Free::PAYMENT_METHOD_FREE_CODE) {
            $result = self::CARRIER_CODE_POINT_PURCHASE;
        }

        return $result;
    }

    /**
     * Get customer sap code from shipment
     *
     * @param \Magento\Framework\DataObject $object
     *
     * @return string|null
     */
    public function getCustomerSapCode(\Magento\Framework\DataObject $object)
    {
        $customerSapId = $this->getCustomerSapId($object);
        return $this->getCustomerSapCodeByCustomerSapId($customerSapId);
    }


    /**
     * Get customer sap code
     *
     * @param $customerSapId
     *
     * @return null|string
     */
    public function getCustomerSapCodeByCustomerSapId($customerSapId)
    {
        $method = lcfirst(implode('', array_map('ucfirst', explode('_', $customerSapId))));

        if (!$method) {
            return null;
        }

        return $this->scopeConfigHelper->read(ConfigInterface::class)
            ->sapIntegration()
            ->sapCustomerId()
            ->$method();
    }

    /**
     * Get warehouse
     *
     * @param \Magento\Framework\DataObject $object
     *
     * @return mixed|null|string
     */
    public function getWh(\Magento\Framework\DataObject $object)
    {
        $id = [null];
        if ($object instanceof \Magento\Sales\Model\Order\Shipment) {
            $id = ['store_code' => $object->getData('warehouse')];
        } elseif ($object instanceof \Magento\Rma\Model\Rma) {
            $id = ['place_id' => $object->getData('returned_warehouse')];
        }

        if ($this->functionCache->has($id)) {
            return $this->functionCache->load($id);
        }

        $result = '';

        /** @var \Wyomind\PointOfSale\Model\ResourceModel\PointOfSale\Collection $collection */
        $collection = $this->pointOfSaleFactory->create()->getCollection();

        if (isset($id['store_code'])) {
            $collection->addFieldToFilter(
                'store_code', ['eq' => $id['store_code']]
            );
        } elseif (isset($id['place_id'])) {
            $collection->addFieldToFilter(
                'place_id', ['eq' => $id['place_id']]
            );
        }

        if ($collection->getSize()) {
            $result = $collection->setPageSize(1)->getFirstItem();
        }

        if (!empty($result) && $result->getId()
            &&$result instanceof \Wyomind\PointOfSale\Model\PointOfSale
        ) {
            $result = $result->getData('sap_code');
        }

        $this->functionCache->store($result, $id);

        return $result;
    }


    /**
     * Get carrier code for shipment or rma object
     *
     * @param $object
     * @return bool|string
     */
    public function getCarrierCodeFromObject($object)
    {
        if ($object instanceof \Magento\Sales\Model\Order\Shipment) {
            $track = $this->searchHelper
                ->getByParentId($object->getId())
                ->getOne()
                ->execute($this->shipmentTrackRepository);
        } elseif ($object instanceof \Magento\Rma\Model\Rma) {
            $track = $this->searchHelper
                ->getByRmaEntityId($object->getId())
                ->getOne()
                ->execute($this->rmaTrackRepository);
        }

        if ($track) {
            return strtolower((string)$track->getData('carrier_code'));
        }

        return false;
    }

    /**
     * Get carrier code by shosha code
     *
     * @param string $shoshaCode
     * @return bool|mixed
     */
    public function getCarrierCodeByShoshaCode($shoshaCode)
    {
        /*get shosha data*/
        $shoshaCollection = $this->shoshaCollection
            ->create()
            ->addFieldToFilter('shosha_business_code', $shoshaCode);

        foreach ($shoshaCollection->getItems() as $shosha) {
            $shoshaCode = $shosha->getData('shosha_code');
        }

        /*list carrier code for each shosha code which we need to export to SAP*/
        $shoshaCodes = [
            ShoshaCode::ITOCHU => 'itochu',
            ShoshaCode::CEDYNA => 'cedyna',
            ShoshaCode::FKJEN => 'fukujuen',
            ShoshaCode::LUPICIA => 'lupicia'
        ];

        if (!empty($shoshaCodes[$shoshaCode])) {
            return $shoshaCodes[$shoshaCode];
        }

        return false;
    }

    /**
     * convert wbs for sap exported
     *
     * @param $bookingItemWbs
     * @return mixed
     */
    public function convertWbsForSapExported($bookingItemWbs)
    {
        return $bookingItemWbs;
    }

    /**
     * get order payment agent
     *
     * @param $order
     * @return string
     */
    public function getOrderPaymentAgent($order)
    {
        $paymentAgent = $order->getData('payment_agent');

        if (empty($paymentAgent)) {
            $paymentAgent = $this->getPaymentAgentFromPaygentHistory($order);

            if (!empty($paymentAgent)) {
                $order->setData('payment_agent', $paymentAgent);
                $this->functionCache->store($order, $order->getId());
            }
        }

        return strtolower($paymentAgent);
    }

    /**
     * Get payment agent from paygent history -> plugin
     *
     * @param $order
     * @return mixed
     */
    public function getPaymentAgentFromPaygentHistory($order)
    {
        return $order->getData('payment_agent');
    }

    /**
     * Get tax code
     *
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @param \Magento\Sales\Model\Order\Item $orderItem
     * @return string
     */
    public function getTaxCode($shipment, $orderItem)
    {
        $scopeConfig = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $taxChangedDate = $this->scopeConfig->getValue(\Riki\Tax\Helper\Data::XML_CONFIG_TAX_CHANGE_DATE, $scopeConfig);
        $taxCode = '';
        if (!$shipment->getShippedOutDate() || !$orderItem->getTaxPercent()) {
            return $taxCode;
        }
        $taxPercent = $orderItem->getTaxPercent();
        $shippedOutDate = $shipment->getShippedOutDate();
        $taxChangedTime = strtotime($taxChangedDate);
        $shippedOutTime = strtotime($shippedOutDate);
        if ((float)$taxPercent == 8.0) {
            if ($shippedOutTime < $taxChangedTime) {
                $taxCode = self::TAX_CODE_EIGHT_PERCENT_BEFORE_CHANGE_DATE;
            } elseif ($shippedOutTime >= $taxChangedTime) {
                $taxCode = self::TAX_CODE_EIGHT_PERCENT_AFTER_CHANGE_DATE;
            }
        } elseif ((float)$taxPercent == 10.0) {
            $taxCode = self::TAX_CODE_TEN_PERCENT;
        }
        return $taxCode;
    }
}
