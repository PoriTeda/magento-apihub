<?php
namespace Riki\Rma\Block\Adminhtml\System\Config\Form\Field;

use Riki\Rma\Block\Adminhtml\System\Config\Form\Field\MassActionCondition\FullPartial;
use Riki\Rma\Block\Adminhtml\System\Config\Form\Field\MassActionCondition\PaymentMethod;
use Riki\Rma\Block\Adminhtml\System\Config\Form\Field\MassActionCondition\Reason;

class MassActionCondition extends \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray
{
    /**
     * @var FullPartial
     */
    protected $fullPartialRenderer = null;

    /**
     * @var PaymentMethod
     */
    protected $paymentMethodRenderer = null;

    /**
     * @var Reason
     */
    protected $reasonRenderer = null;

    /**
     * @return \Magento\Framework\View\Element\BlockInterface|FullPartial
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getFullPartialRenderer()
    {
        if (!$this->fullPartialRenderer) {
            $this->fullPartialRenderer = $this->getLayout()->createBlock(
                FullPartial::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->fullPartialRenderer;
    }

    /**
     * @return \Magento\Framework\View\Element\BlockInterface|PaymentMethod
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getPaymentMethodRenderer()
    {
        if (!$this->paymentMethodRenderer) {
            $this->paymentMethodRenderer = $this->getLayout()->createBlock(
                PaymentMethod::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->paymentMethodRenderer;
    }

    /**
     * @return \Magento\Framework\View\Element\BlockInterface|Reason
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getReasonRenderer()
    {
        if (!$this->reasonRenderer) {
            $this->reasonRenderer = $this->getLayout()->createBlock(
                Reason::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->reasonRenderer;
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareToRender()
    {
        $this->addColumn(
            'full_partial',
            [
                'label'     => __('Full/Partial'),
                'renderer'  => $this->getFullPartialRenderer(),
            ]
        );
        $this->addColumn(
            'payment_method',
            [
                'label' => __('Payment Method'),
                'renderer'  => $this->getPaymentMethodRenderer(),
            ]
        );
        $this->addColumn(
            'reason',
            [
                'label' => __('Reason'),
                'renderer'  => $this->getReasonRenderer(),
            ]
        );
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add Rule');
    }

    /**
     * @param \Magento\Framework\DataObject $row
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareArrayRow(\Magento\Framework\DataObject $row)
    {
        $options = [];

        $fullPartial = $row->getFullPartial();
        $fullPartial = is_array($fullPartial)? $fullPartial : [$fullPartial];

        foreach ($fullPartial as $fullOrPartial) {
            $options['option_' . $this->getFullPartialRenderer()->calcOptionHash($fullOrPartial)]
                = 'selected="selected"';
        }

        $paymentMethods = $row->getPaymentMethod();
        $paymentMethods = is_array($paymentMethods)? $paymentMethods : [$paymentMethods];

        foreach ($paymentMethods as $paymentMethod) {
            $options['option_' . $this->getPaymentMethodRenderer()->calcOptionHash($paymentMethod)]
                = 'selected="selected"';
        }

        $reasons = $row->getReason();
        $reasons = is_array($reasons)? $reasons : [$reasons];

        foreach ($reasons as $reason) {
            $options['option_' . $this->getReasonRenderer()->calcOptionHash($reason)]
                = 'selected="selected"';
        }

        $row->setData('option_extra_attrs', $options);
        return;
    }
}
