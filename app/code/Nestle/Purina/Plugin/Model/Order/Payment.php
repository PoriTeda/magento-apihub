<?php
/**
 * Nestle Purina Vets
 * PHP version 7
 *
 * @category Nestle_Purina
 * @package  Nestle\Purina
 * @author   Nestle <support@nestle.co.jp>
 * @license  http://nestle.co.jp/policy.html GNU General Public License
 * @link     http://vets.nestle.jp
 */
namespace Nestle\Purina\Plugin\Model\Order;

/**
 * Class Payment
 *
 * @category Nestle_Purina
 * @package  Nestle\Purina
 * @author   Nestle <support@nestle.co.jp>
 * @license  http://nestle.co.jp/policy.html GNU General Public License
 * @link     http://vets.nestle.jp
 */
class Payment
{
    /**
     * Request
     *
     * @var \Magento\Framework\Webapi\Rest\Request
     */
    protected $request;

    /**
     * Factory for payment method models
     *
     * @var \Magento\Payment\Model\Method\Factory
     */
    protected $methodFactory;

    /**
     * Payment constructor.
     *
     * @param \Magento\Framework\Webapi\Rest\Request $request       request
     * @param \Magento\Payment\Model\Method\Factory  $methodFactory payment method
     */
    public function __construct(
        \Magento\Framework\Webapi\Rest\Request $request,
        \Magento\Payment\Model\Method\Factory $methodFactory
    ) {
        $this->request = $request;
        $this->methodFactory = $methodFactory;
    }

    /**
     * Request to check if the request come from Purina API
     *
     * @return bool
     */
    public function isPurinaApi()
    {
        try {
            $request = $this->request->getRequestData();
            if (isset($request['from_purina_api'])) {
                return true;
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Plugin to switch to Purina Paygent model interm of Purina payment
     *
     * @param \Magento\Sales\Model\Order\Payment $instance payment
     * @param mixed                              $result   result
     *
     * @return \Nestle\Purina\Model\Paygent
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function afterGetMethodInstance(
        \Magento\Sales\Model\Order\Payment $instance,
        $result
    ) {
        if ($result instanceof \Bluecom\Paygent\Model\Paygent
            && $this->isPurinaApi()
            && !$result instanceof \Riki\Subscription\Model\Emulator\PaymentMethod\Paygent
        ) {
            $newClass = $this->methodFactory
                ->create(\Nestle\Purina\Model\Paygent::class);
            $newClass->setInfoInstance($result->getInfoInstance());
            return $newClass;
        }
        return $result;
    }
}
