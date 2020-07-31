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

use \Riki\CvsPayment\Model\CvsPayment;

/**
 * Class CvsPaymentConfigProvider
 *
 * @category  RIKI
 * @package   Riki\CvsPayment\Model
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class CvsPaymentConfigProvider
    implements \Magento\Checkout\Model\ConfigProviderInterface
{
    /**
     * MethodCode
     *
     * @var string
     */
    protected $methodCode = CvsPayment::PAYMENT_METHOD_CVS_CODE;

    /**
     * Method
     *
     * @var \Riki\CvsPayment\Model\CvsPayment
     */
    protected $method;
    /**
     * Escaper
     *
     * @var \Magento\Framework\Escaper
     */
    protected $escaper;

    /**
     * CvsPaymentConfigProvider constructor.
     *
     * @param \Magento\Payment\Helper\Data $paymentHelper paymentHelper
     * @param \Magento\Framework\Escaper   $escaper       escaper
     */
    public function __construct(
        \Magento\Payment\Helper\Data $paymentHelper,
        \Magento\Framework\Escaper $escaper
    ) {
        $this->escaper = $escaper;
        $this->method = $paymentHelper->getMethodInstance($this->methodCode);
    }

    /**
     * Get Config
     *
     * {@inheritdoc}
     *
     * @return array
     */
    public function getConfig()
    {
        return $this->method->isAvailable() ? [
            'payment' => [
                'cvspayment' => [
                    'mailingAddress' => $this->getMailingAddress(),
                    'payableTo' => $this->getPayableTo(),
                ],
            ],
        ] : [];
    }

    /**
     * Get mailing address from config
     *
     * @return string
     */
    protected function getMailingAddress()
    {
        return nl2br($this->escaper->escapeHtml($this->method->getMailingAddress()));
    }

    /**
     * Get payable to from config
     *
     * @return string
     */
    protected function getPayableTo()
    {
        return $this->method->getPayableTo();
    }
}