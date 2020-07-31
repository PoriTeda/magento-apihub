<?php
/**
 * Shipment Data
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\Shipment\Helper
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\Shipment\Helper;
use Magento\Framework\App\Helper\AbstractHelper;
use Riki\Sales\Api\OrderApiItemInterface;
use Riki\Shipment\Model\ResourceModel\Status\Options\Payment as PaymentStatus;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Riki\Sales\Model\ResourceModel\Order\OrderStatus as OrderStatus;
/**
 * Class Data
 *
 * @category  RIKI
 * @package   Riki\Shipment\Helper
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce

 */
class Data extends AbstractHelper
{
    const MAX_SHIPMENTS_PROCEED = 50;

    protected $shipment;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    /**
     * @var \Magento\Sales\Api\OrderAddressRepositoryInterface
     */
    protected $orderAddressReporistory;
    /**
     * @var \Magento\Directory\Model\CountryFactory
     */
    protected $countryFactory;
    /**
     * @var
     */
    protected $searchCriteriaBuilder;
    /**
     * @var OrderApiItemInterface
     */
    protected $orderAddessItemRepository;

    /**
     * Data constructor.
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Sales\Api\OrderAddressRepositoryInterface $orderAddressRepository
     * @param \Riki\Shipment\Model\ShipmentGridFactory $shipmentGridFactory
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Sales\Api\OrderAddressRepositoryInterface $orderAddressRepository,
        \Riki\Checkout\Model\ResourceModel\Order\Address\Item\CollectionFactory $collectionFactory,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->logger = $logger;
        $this->orderAddressReporistory = $orderAddressRepository;
        $this->countryFactory = $countryFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->orderAddessItemRepository = $collectionFactory;
    }


    /**
     * @param $shipment
     * @return array
     */
    public function rebuildAddress($shipment)
    {
        $items = $shipment->getAllItems();
        $orderIds = array();
        foreach($items as $item)
        {
            $orderIds[] = $item->getOrderItemId();
        }
        $billingId = $shipment->getBillingAddressId();
        $shippingId = $shipment->getShippingAddressId();
        $billingObject = $this->orderAddressReporistory->get($billingId);
        $shippingCollection = $this->orderAddessItemRepository->create()
                                    ->addFieldToFilter('order_item_id',array('in'=>$orderIds));

        if($shippingCollection->getSize())
        {
            $shippingId = $shippingCollection->getFirstItem()->getOrderAddressId();
        }
        $shippingObject = $this->orderAddressReporistory->get($shippingId);
        $countryModel = $this->countryFactory->create();

        //combine new Billing Address
        $billingRegion = $billingObject->getRegion();
        $billingCountryID = $billingObject->getCountryId();
        $billingCountryName = $countryModel->loadByCode($billingCountryID)->getName();
        $billingStreet = implode(' ',$billingObject->getStreet());
        $billingPostCode = $billingObject->getPostcode();
        $billingAddress = $billingPostCode;
        $billingAddress .= ' '.$billingCountryName. ' '. $billingRegion;
        $billingAddress .= ' '.$billingStreet;

        //combine new Shipping Address
        $shippingRegion = $shippingObject->getRegion();
        $shippingCountryID = $shippingObject->getCountryId();
        $shippingCountryName = $countryModel->loadByCode($shippingCountryID)->getName();
        $shippingStreet = implode(' ',$shippingObject->getStreet());
        $shippingPostCode = $shippingObject->getPostcode();
        $shippingAddress = $shippingPostCode;
        $shippingAddress .= ' '.$shippingCountryName. ' '. $shippingRegion;
        $shippingAddress .= ' '.$shippingStreet;
        $shippingName = $shippingObject->getLastname(). ' '. $shippingObject->getFirstname();
        $shippingNameKana = $shippingObject->getLastnamekana(). ' '. $shippingObject->getFirstnamekana();
        $shippingAddressSearch = [$shippingObject->getFirstname(), $shippingObject->getLastname(),$shippingStreet];
        return [$billingAddress,$shippingAddress,$shippingName,$shippingNameKana,$shippingObject->getEntityId(), $shippingAddressSearch];
    }//end function

    /**
     * @param $paymentMethod
     * @param $orderStatus
     * @return string
     */
    public function getPaymentStatus($paymentMethod,$orderStatus = null)
    {
        switch(strtolower($paymentMethod))
        {
            //free of charge
            case 'free':
                $status = PaymentStatus::SHIPPING_PAYMENT_STATUS_PAYMENT_COLLECTED;
                break;
            //paygent
            case \Bluecom\Paygent\Model\Paygent::CODE:
                if($orderStatus == OrderStatus::STATUS_ORDER_CAPTURE_FAILED)
                {
                    $status = PaymentStatus::SHIPPING_PAYMENT_STATUS_CAPTURE_FAILED;
                }
                else
                {
                    $status = PaymentStatus::SHIPPING_PAYMENT_STATUS_PAYMENT_COLLECTED;
                }
                break;
                //cash on delivery
            case \Magento\OfflinePayments\Model\Cashondelivery::PAYMENT_METHOD_CASHONDELIVERY_CODE:
                $status = PaymentStatus::SHIPPING_PAYMENT_STATUS_NOT_APPLICABLE;
                break;
                // invoiced base payment
            case \Riki\PaymentBip\Model\InvoicedBasedPayment::PAYMENT_CODE:
                $status = PaymentStatus::SHIPPING_PAYMENT_STATUS_PAYMENT_COLLECTED;
                break;
                //CVS payment
            case \Riki\CvsPayment\Model\CvsPayment::PAYMENT_METHOD_CVS_CODE:
                $status = PaymentStatus::SHIPPING_PAYMENT_STATUS_PAYMENT_COLLECTED;
                break;
            default: // point or other payments
                $status = PaymentStatus::SHIPPING_PAYMENT_STATUS_PAYMENT_COLLECTED;
                break;

        }
        return $status;
    }
    /**
     * @param $collection
     * @param $selected
     * @param $filters
     * @return mixed
     */
    public function filterBuilder($collection,$selected,$filters)
    {
        /* join related tables  */
        $collection->join(
            'sales_order',
            'sales_order.entity_id = `main_table`.`order_id`',
            ''
        );
        $collection->join( 'sales_order_payment',
            'sales_order_payment.parent_id = sales_order.entity_id',
            ''
        );
        if($selected)
        {
            $collection->addFieldToFilter('main_table.entity_id',['in'=>$selected]);
        }
        if($filters)
        {
            foreach($filters as $key=>$val)
            {
                if($key!='placeholder')
                {
                    $filterKey = $this->renameFilterKey($key);
                    $collection->addFieldToFilter($filterKey,$val);
                }
            }
        }
        echo $collection->getSelect();
        return $collection;
    }

    /**
     * @param $key
     * @return string
     */
    public function renameFilterKey($key)
    {
        switch($key)
        {
            case 'payment_method':
                return 'method';
                break;
            default:
                return $key;
        }
    }
}//end class
