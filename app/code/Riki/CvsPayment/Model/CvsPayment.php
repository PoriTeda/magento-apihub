<?php
/**
 * CvsPayment
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\CvsPayment
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\CvsPayment\Model;

/**
 * Class CvsPayment
 *
 * @category  RIKI
 * @package   Riki\CvsPayment\Model
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class CvsPayment extends \Magento\Payment\Model\Method\AbstractMethod
{
    const PAYMENT_METHOD_CVS_CODE = 'cvspayment';

    /**
     * Payment method code
     *
     * @var string
     */
    protected $_code = self::PAYMENT_METHOD_CVS_CODE; //@codingStandardsIgnoreLine

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_isOffline = true; //@codingStandardsIgnoreLine

    /**
     * Get Payable To
     *
     * @return string
     */
    public function getPayableTo()
    {
        //Data from system.xml fields
        return $this->getConfigData('payable_to');
    }

    /**
     * Get Mailing Address
     *
     * @return string
     */
    public function getMailingAddress()
    {
        //Data from system.xml fields
        return $this->getConfigData('mailing_address');
    }

    /**
     * Get instructions text from config
     *
     * @return string
     */
    public function getInstructions()
    {
        return trim($this->getConfigData('instructions'));
    }
}