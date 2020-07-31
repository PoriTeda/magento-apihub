<?php
/**
 * Format Billing and Shipping Address
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\Sales\Plugin
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\Sales\Plugin;
use Riki\EmailMarketing\Helper\Order as OrderHelper;
use Psr\Log\LoggerInterface;
use Magento\Directory\Model\CountryFactory;
/**
 * Class FormatAddress
 *
 * @category  RIKI
 * @package   Riki\Sales\Plugin
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class FormatAddress
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    /**
     * @var OrderHelper
     */
    protected $orderHelper;
    /**
     * @var CountryFactory
     */
    protected $countryFactory;
    /**
     * FormatAddress constructor.
     * @param LoggerInterface $logger
     * @param OrderHelper $orderHelper
     */
    public function __construct(
        LoggerInterface $logger,
        OrderHelper $orderHelper,
        CountryFactory $countryFactory

    ){
        $this->logger = $logger;
        $this->orderHelper = $orderHelper;
        $this->countryFactory = $countryFactory;
    }

    /**
     * @param \Magento\Sales\Model\Order $subject
     * @return mixed
     */
    public function beforeAfterSave(
        \Magento\Sales\Model\Order $subject
    )
    {
        $billingAddress = $subject->getBillingAddress();
        $shippingAddress = $subject->getShippingAddress();
        $countryModel = $this->countryFactory->create();

        //combine new Billing Address
        $billingRegion = $billingAddress->getRegion();
        $billingCity = $this->orderHelper->isCityNull($billingAddress->getCity());
        $billingCountryID = $billingAddress->getCountryId();
        $billingCountryName = $countryModel->loadByCode($billingCountryID)->getName();
        $billingStreet = implode(' ',$billingAddress->getStreet());
        $billingPostCode = $billingAddress->getPostcode();
        $billingAddressValue = $billingPostCode;
        if($billingCity)
        {
            $billingAddressValue .= ' '.$billingCountryName. ' '. $billingRegion. ' '.$billingCity;
        }
        else
        {
            $billingAddressValue .= ' '.$billingCountryName. ' '. $billingRegion;
        }
        $billingAddressValue .= ' '.$billingStreet;
        //combine new Shipping Address
        $shippingRegion = $shippingAddress->getRegion();
        $shippingCity = $this->orderHelper->isCityNull($shippingAddress->getCity());
        $shippingCountryID = $shippingAddress->getCountryId();
        $shippingCountryName = $countryModel->loadByCode($shippingCountryID)->getName();
        $shippingStreet = implode(' ',$shippingAddress->getStreet());
        $shippingPostCode = $shippingAddress->getPostcode();
        $shippingAddressValue = $shippingPostCode;
        if($shippingCity)
        {
            $shippingAddressValue .= ' '.$shippingCountryName. ' '. $shippingRegion. ' '.$shippingCity;
        }
        else
        {
            $shippingAddressValue .= ' '.$shippingCountryName. ' '. $shippingRegion;
        }
        $shippingAddressValue .= ' '.$shippingStreet;
        $subject->setData('billing_address',$billingAddressValue);
        $subject->setData('shipping_address',$shippingAddressValue);
   }
}
