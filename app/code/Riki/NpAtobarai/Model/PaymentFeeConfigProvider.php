<?php
namespace Riki\NpAtobarai\Model;

use Bluecom\PaymentFee\Api\FeeManagementInterface;
use Magento\Checkout\Model\ConfigProviderInterface;
use Riki\NpAtobarai\Model\Payment\NpAtobarai;

class PaymentFeeConfigProvider implements ConfigProviderInterface
{
    /**
     * @var FeeManagementInterface
     */
    protected $paymentFeeManagement;

    /**
     * PaymentFeeConfigProvider constructor.
     *
     * @param FeeManagementInterface $paymentFeeManagement
     */
    public function __construct(
        FeeManagementInterface $paymentFeeManagement
    ) {
        $this->paymentFeeManagement = $paymentFeeManagement;
    }

    /**
     * @return array|mixed
     */
    public function getConfig()
    {
        $npAtobaraiPaymentFee = $this->paymentFeeManagement
            ->getFeeByMethod(NpAtobarai::PAYMENT_METHOD_NP_ATOBARAI_CODE);
        return ['payment' => ['npatobarai' => ['paymentFee' => $npAtobaraiPaymentFee]]];
    }
}
