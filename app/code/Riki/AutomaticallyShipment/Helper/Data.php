<?php
namespace Riki\AutomaticallyShipment\Helper;

use Magento\OfflinePayments\Model\Cashondelivery;
use Magento\Payment\Model\Method\Free;
use Bluecom\Paygent\Model\Paygent;
use Riki\SapIntegration\Model\Config\Source\Options;
use Riki\PaymentBip\Model\InvoicedBasedPayment;
use Riki\CvsPayment\Model\CvsPayment;
use Riki\SapIntegration\Model\Api\Shipment as ShipmentApi;
use Riki\Customer\Model\Shosha\ShoshaCode;
use Riki\SapIntegration\Api\ConfigInterface;

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
    const CARRIER_CODE_YAMATO = 'yamato';
    const CARRIER_CODE_YAMATO_TYPE_ASKUL = 'yamatoaskul';
    const CARRIER_CODE_WELLNET = 'wellnet';
    const CARRIER_CODE_POINT_PURCHASE = 'point_purchase';


    /**
     * @var \Magento\Framework\App\Config
     */
    protected $_appConfig;
    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $_connection;
    /**
     * @var \Magento\Sales\Model\Order\AddressFactory
     */
    protected $_orderAddressFactory;
    /**
     * @var
     */
    protected $connectionHelper;

    /**
     * Data constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\App\Config $appConfig
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\Config $appConfig,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Sales\Model\Order\AddressFactory $addressFactory,
        \Riki\Sales\Helper\ConnectionHelper $connectionHelper
    ) {
        parent::__construct($context);
        $this->_appConfig = $appConfig;
        $this->_connection = $resourceConnection->getConnection('sales');
        $this->_orderAddressFactory = $addressFactory;
        $this->connectionHelper = $connectionHelper;
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
     * @param \Magento\Sales\Model\Order $order
     * @return array
     */
    public function getOrderAddressToCustomerAddressesByOrder(\Magento\Sales\Model\Order $order){
        $itemIds = [];

        foreach($order->getAllItems() as $item){
            $itemIds[] = $item->getId();
        }

        $select = $this->_connection->select()->from(
            'sales_order_address',
            ['entity_id', 'customer_address_id']
        )->join(
            'order_address_item',
            'sales_order_address.entity_id=order_address_item.order_address_id'
        )->where(
            'order_address_item.order_item_id IN(?)',
            $itemIds
        );

        return $this->_connection->fetchPairs($select);
    }

    /**
     * @param $addressID
     * @return string
     */
    public function getNewShippingName($addressID)
    {
        $addressObject = $this->_orderAddressFactory->create()->load($addressID);
        if($addressObject)
        {
            return $addressObject->getLastname(). ' '.$addressObject->getFirstname();
        }
        return '';
    }

    /**
     * @param $orderId
     * @param $status
     * @param null $state
     */
    public function changeOrderStatusDirect($orderId,$status,$state = null)
    {
        //update shipment status sales_shipment
        $connection = $this->connectionHelper->getSalesConnection();
        $table = $connection->getTableName('sales_order');
        $tableGrid = $connection->getTableName('sales_order_grid');
        $sql = "UPDATE $table set `shipment_status` = '$status'  WHERE entity_id = $orderId";
        $connection->query($sql);
        $sql1 = "UPDATE $tableGrid set `shipment_status` = '$status' WHERE entity_id = $orderId";
        $connection->query($sql1);
    }
}