<?php
namespace Riki\Rma\Plugin\Config\Model\Config\Structure\Element;

class SectionPlugin
{
    /**
     * @var \Magento\Payment\Helper\Data
     */
    protected $paymentDataHelper;

    /**
     * @var \Riki\Rma\Helper\Refund
     */
    protected $refundHelper;

    /**
     * SectionPlugin constructor.
     * @param \Magento\Payment\Helper\Data $paymentDataHelper
     * @param \Riki\Rma\Helper\Refund $refundHelper
     */
    public function __construct(
        \Magento\Payment\Helper\Data $paymentDataHelper,
        \Riki\Rma\Helper\Refund $refundHelper
    )
    {
        $this->paymentDataHelper = $paymentDataHelper;
        $this->refundHelper = $refundHelper;
    }

    /**
     * Hook before render section refund_payment, data should be dynamic
     *
     * @param $subject
     * @param $data
     * @param $scope
     * @return array
     */
    public function beforeSetData($subject, $data, $scope)
    {

        if (!isset($data['id']) || $data['id'] != 'rma') {
            return [$data, $scope];
        }

        $children = [];
        $methods = $this->refundHelper->getEnablePaymentMethods();
        foreach ($methods as $id => $method) {
            if (!isset($method['title'])) {
                continue;
            }
            $children[$id] = [
                'id' => $id,
                'type' => 'text',
                'showInStore' => 1,
                'showInWebsite' => 1,
                'showInDefault' => 1,
                'sortOrder' => 1,
                'translate' => 'label',
                'label' => __($method['title']),
                'children' => [
                    'online_member_default' => [
                        '_elementType' => 'field',
                        'id' => 'online_member_default',
                        'type' => 'select',
                        'showInWebsite' => 1,
                        'showInStore' => 1,
                        'showInDefault' => 1,
                        'sortOrder' => 1,
                        'translate' => 'label',
                        'label' => 'On-line member default',
                        'source_model' => 'Riki\Rma\Model\Config\Source\RefundPayment',
                        'path' => \Riki\Rma\Api\ConfigInterface::RMA . '/' . $id
                    ],
                    'offline_member_default' => [
                        '_elementType' => 'field',
                        'id' => 'offline_member_default',
                        'type' => 'select',
                        'showInWebsite' => 1,
                        'showInStore' => 1,
                        'showInDefault' => 1,
                        'sortOrder' => 1,
                        'translate' => 'label',
                        'label' => 'Off-line member default',
                        'source_model' => 'Riki\Rma\Model\Config\Source\RefundPayment',
                        'path' => \Riki\Rma\Api\ConfigInterface::RMA . '/' . $id
                    ],
                    'alternative' => [
                        '_elementType' => 'field',
                        'id' => 'alternative',
                        'type' => 'multiselect',
                        'showInWebsite' => 1,
                        'showInStore' => 1,
                        'showInDefault' => 1,
                        'sortOrder' => 1,
                        'translate' => 'label',
                        'label' => 'Alternative',
                        'source_model' => 'Riki\Rma\Model\Config\Source\RefundPayment',
                        'path' => \Riki\Rma\Api\ConfigInterface::RMA . '/' . $id
                    ]
                ]
            ];
        }

        $data['children'] = array_merge($data['children'], $children);

        return [$data, $scope];
    }
}
