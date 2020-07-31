<?php
/**
 * CedynaInvoice Edit Tab
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\CedynaInvoice\Block\Adminhtml\Invoice\Edit\Tab
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\CedynaInvoice\Block\Adminhtml\Invoice\Edit\Tab;

/**
 * Class Main
 *
 * @category  RIKI
 * @package   Riki\CedynaInvoice\Block\Adminhtml\Invoice\Edit\Tab
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Main extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * @var \Magento\Store\Model\System\Store
     */
    protected $systemStore;
    /**
     * @var \Magento\Cms\Model\Wysiwyg\Config
     */
    protected $wysiwygConfig;

    /**
     * Main constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Store\Model\System\Store $systemStore
     * @param \Magento\Cms\Model\Wysiwyg\Config $wysiwygConfig
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Store\Model\System\Store $systemStore,
        \Magento\Cms\Model\Wysiwyg\Config $wysiwygConfig,
        array $data = []
    ) {
        $this->systemStore = $systemStore;
        $this->wysiwygConfig = $wysiwygConfig;
        parent::__construct($context, $registry, $formFactory, $data);
    }
    /**
     * Prepare a form
     */
    protected function _prepareForm()
    {
        $model = $this->_coreRegistry->registry('cedyna_invoice');

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();

        $form->setHtmlIdPrefix('page_');
        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Cedyna Invoice Form')]);

        if ($model->getId()) {
            $fieldset->addField('id', 'hidden', ['name' => 'id']);
        }

        $fieldset->addField(
            'import_month',
            'text',
            [
                'name' => 'import_month',
                'id' => 'import_month',
                'label' => __('Import Month'),
                'title' => __('Import Month'),
                'required' => true,
                'maxlength' => 6,
            ]
        );

        $fieldset->addField(
            'target_month',
            'text',
            [
                'name' => 'target_month',
                'id' => 'target_month',
                'label' => __('Target Month'),
                'title' => __('Target Month'),
                'required' => false,
                'maxlength' => 6,
            ]
        );
        $fieldset->addField(
            'business_code',
            'text',
            [
                'name' => 'business_code',
                'id' => 'business_code',
                'label' => __('Business Code'),
                'title' => __('Business Code'),
                'required' => true,
                'maxlength' => 40
            ]
        );
        $dateFormat = $this->_localeDate->getDateFormat(\IntlDateFormatter::SHORT);
        $fieldset->addField(
            'shipped_out_date',
            'date',
            [
                'name' => 'shipped_out_date',
                'label' => __('Shipped out date'),
                'title' => __('Shipped out date'),
                'input_format' => \Magento\Framework\Stdlib\DateTime::DATE_PHP_FORMAT,
                'date_format' => $dateFormat,
                'required' => true,
                'readonly' => 'readonly'
            ]
        );

        $sources = [
            ['value' => '01', 'label' => __('Sales')],
            ['value' => '02', 'label' => __('Return')],
            ['value' => '03', 'label' => __('Discount')]
        ];
        $fieldset->addField(
            'data_type',
            'select',
            [
                'name' => 'data_type',
                'id' => 'data_type',
                'label' => __('Data Type'),
                'title' => __('Data Type'),
                'required' => true,
                'values'   => $sources,
            ]
        );
        $fieldset->addField(
            'row_total',
            'text',
            [
                'name' => 'row_total',
                'id' => 'row_total',
                'label' => __('Row total'),
                'title' => __('Row total'),
                'required' => false,
                'class' => 'validate-number validate-greater-than-zero'
            ]
        );
        $fieldset->addField(
            'increment_id',
            'text',
            [
                'name' => 'increment_id',
                'id' => 'increment_id',
                'label' => __('Invoice Increment Id'),
                'title' => __('Invoice Increment Id'),
                'required' => true,
                'maxlength' => 50
            ]
        );
        $fieldset->addField(
            'product_line_name',
            'text',
            [
                'name' => 'product_line_name',
                'id' => 'product_line_name',
                'label' => __('Product Name'),
                'title' => __('Product Name'),
                'maxlength' => 255
            ]
        );
        $fieldset->addField(
            'unit_price',
            'text',
            [
                'name' => 'unit_price',
                'id' => 'unit_price',
                'label' => __('Unit Price'),
                'title' => __('Unit Price'),
                'required' => false,
                'class' => 'validate-number validate-greater-than-zero'
            ]
        );
        $fieldset->addField(
            'qty',
            'text',
            [
                'name' => 'qty',
                'id' => 'qty',
                'label' => __('Qty'),
                'title' => __('Qty'),
                'required' => false,
                'class' => 'validate-number validate-greater-than-zero'
            ]
        );
        $form->setValues($model->getData());
        $this->setForm($form);
        return parent::_prepareForm();
    }

    /**
     * Prepare label for tab
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('Cedyna Invoice form');
    }

    /**
     * Prepare title for tab
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Cedyna Invoice from');
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * For ACL
     *
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * Check permission for passed action
     *
     * @param string $resourceId
     * @return bool
     */
    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }
}
