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
namespace Riki\CvsPayment\Plugin\OfflinePayments\Model;

use \Riki\CvsPayment\Model\CvsPayment;

/**
 * Class InstructionsConfigProvider
 *
 * @category  RIKI
 * @package   Riki\CvsPayment\Plugin
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class InstructionsConfigProvider
{
    /**
     * PaymentHelper
     *
     * @var \Magento\Payment\Helper\Data
     */
    protected $paymentHelper;
    /**
     * Escaper
     *
     * @var \Magento\Framework\Escaper
     */
    protected $escaper;

    /**
     * InstructionsConfigProvider constructor.
     *
     * @param \Magento\Payment\Helper\Data $paymentHelper paymentHelper
     * @param \Magento\Framework\Escaper   $escaper       escaper
     */
    public function __construct(
        \Magento\Payment\Helper\Data $paymentHelper,
        \Magento\Framework\Escaper $escaper
    ) {
        $this->paymentHelper = $paymentHelper;
        $this->escaper = $escaper;
    }

    /**
     * Extend getConfig
     *
     * @param mixed $subject subject
     * @param mixed $result  result
     *
     * @return mixed
     */
    public function afterGetConfig($subject, $result)
    {
        $method = $this->paymentHelper
            ->getMethodInstance(CvsPayment::PAYMENT_METHOD_CVS_CODE);
        if (!$method || !$method->isAvailable()) {
            return $result;
        }

        if (!isset($result['payment']['instructions'])) {
            $result['payment']['instructions'] = [];
        }

        $instructions = $result['payment']['instructions'];
        if (isset($instructions[CvsPayment::PAYMENT_METHOD_CVS_CODE])) {
            return $result;
        }

        $instruction = $this->escaper->escapeHtml($method->getInstructions());
        $instructions[CvsPayment::PAYMENT_METHOD_CVS_CODE] = nl2br($instruction);

        $result['payment']['instructions'] = $instructions;

        return $result;
    }
}