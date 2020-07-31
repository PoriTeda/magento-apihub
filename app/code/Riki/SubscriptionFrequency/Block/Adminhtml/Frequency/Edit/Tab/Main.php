<?php
/**
 * SubscriptionFrequency
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\SubscriptionFrequency
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\SubscriptionFrequency\Block\Adminhtml\Frequency\Edit\Tab;

/**
 * Class Main
 *
 * @category  RIKI
 * @package   Riki\SubscriptionFrequency\Block\Adminhtml\Frequency\Edit\Tab
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Main extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * SystemStore
     *
     * @var \Magento\Store\Model\System\Store
     */
    protected $_systemStore;

    /**
     * Main constructor.
     *
     * @param \Magento\Backend\Block\Template\Context $context Context
     * @param \Magento\Framework\Registry $registry Registry
     * @param \Magento\Framework\Data\FormFactory $formFactory FormFactory
     * @param \Magento\Store\Model\System\Store $systemStore SystemStore
     * @param array $data Data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Store\Model\System\Store $systemStore,
        array $data = []
    )
    {
        $this->_systemStore = $systemStore;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * PrepareForm
     *
     * @return $this
     */
    protected function _prepareForm() // @codingStandardsIgnoreLine
    {
        parent::_prepareForm();

        $model = $this->_coreRegistry->registry('frequency_item');

        /**
         * Form
         *
         * @var \Magento\Framework\Data\Form $form Form
         */
        $form = $this->_formFactory->create();

        $form->setHtmlIdPrefix('fre_');

        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Frequency Form')]);

        if ($model->getId()) {
            $fieldset->addField('frequency_id', 'hidden', ['name' => 'frequency_id']);
        }

        $fieldset->addField(
            'frequency_unit',
            'select',
            [
                'name' => 'frequency_unit',
                'id' => 'frequency_unit',
                'label' => __('Frequency Unit'),
                'title' => __('Frequency Unit'),
                'options' => ['week' => __('Week'), 'month' => __('Month')],
                'required' => true,
            ]
        );

        $fieldset->addField(
            'frequency_interval',
            'text',
            [
                'name' => 'frequency_interval',
                'id' => 'frequency_interval',
                'label' => __('Frequency interval(in unit)'),
                'title' => __('Frequency interval (in unit)'),
                'class' => 'required-entry validate-number validate-greater-than-zero',
                'required' => true,
            ]
        );

        $fieldset->addField(
            'position',
            'text',
            [
                'name' => 'position',
                'id' => 'position',
                'label' => __('Position'),
                'title' => __('Position'),
                'class' => 'validate-number',
            ]
        );

        $form->setValues($model->getData());
        $this->setForm($form);

        return $this;
    }

    /**
     * Prepare label for tab
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('Frequency Information');
    }

    /**
     * Prepare title for tab
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Frequency Information');
    }

    /**
     * Can show tab
     *
     * @return bool
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * Is hidden
     *
     * @return bool
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * Is allowed action
     *
     * @param string $resourceId Resource Id
     *
     * @return bool
     */
    protected function _isAllowedAction($resourceId) // @codingStandardsIgnoreLine
    {
        return $this->_authorization->isAllowed($resourceId);
    }
}
