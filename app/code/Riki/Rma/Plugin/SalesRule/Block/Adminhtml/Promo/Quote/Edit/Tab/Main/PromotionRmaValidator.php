<?php
namespace Riki\Rma\Plugin\SalesRule\Block\Adminhtml\Promo\Quote\Edit\Tab\Main;

use Riki\Rma\Api\Data\SalesRule\IgnoreWarningRmaInterface;

class PromotionRmaValidator
{
    /**
     * @var \Riki\Rma\Model\Config\Source\SalesRule\IgnoreWarningRma
     */
    protected $ignorePromotionRmaValidatorSource;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * PromotionRmaValidator constructor.
     *
     * @param \Magento\Framework\Registry $registry
     * @param \Riki\Rma\Model\Config\Source\SalesRule\IgnoreWarningRma $ignoreWarningRmaSource
     */
    public function __construct(
        \Magento\Framework\Registry $registry,
        \Riki\Rma\Model\Config\Source\SalesRule\IgnoreWarningRma $ignoreWarningRmaSource
    ) {
        $this->registry = $registry;
        $this->ignorePromotionRmaValidatorSource = $ignoreWarningRmaSource;
    }

    /**
     * Add custom field  into form
     *
     * @param \Magento\SalesRule\Block\Adminhtml\Promo\Quote\Edit\Tab\Main $subject
     * @param \Magento\Framework\Data\Form $form
     *
     * @return mixed[]
     */
    public function beforeSetForm(\Magento\SalesRule\Block\Adminhtml\Promo\Quote\Edit\Tab\Main $subject, \Magento\Framework\Data\Form $form)
    {
        $model = $this->registry->registry('current_promo_sales_rule');
        $fieldSet = $form->getElement('base_fieldset');
        $fieldSet->addField('ignore_warning_rma', 'select', [
            'name' => 'ignore_warning_rma',
            'title' => __('Do not show an alert on RMA'),
            'label' => __('Do not show an alert on RMA'),
            'options' => $this->ignorePromotionRmaValidatorSource->toArray(),
            'value' => $model ? $model->getData('ignore_warning_rma') : IgnoreWarningRmaInterface::NO
        ], 'is_rss');

        return [$form];
    }
}