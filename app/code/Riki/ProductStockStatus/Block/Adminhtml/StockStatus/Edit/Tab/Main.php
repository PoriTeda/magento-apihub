<?php
/**
 * ProductStockStatus Edit Tab
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\ProductStockStatus\Block\Adminhtml\StockStatus\Edit\Tab
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\ProductStockStatus\Block\Adminhtml\StockStatus\Edit\Tab;
/**
 * Class Main
 *
 * @category  RIKI
 * @package   Riki\ProductStockStatus\Block\Adminhtml\StockStatus\Edit\Tab
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Main
    extends \Magento\Backend\Block\Widget\Form\Generic
    implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * @var \Magento\Store\Model\System\Store
     */
    protected $_systemStore;
    /**
     * @var \Magento\Cms\Model\Wysiwyg\Config
     */
    protected $_wysiwygConfig;
    /**
     * @var \Bluecom\ReceiveCvsPayment\Model\Config\Source\StatusOption
     */
    protected $_status;

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
        $this->_systemStore = $systemStore;
        $this->_wysiwygConfig = $wysiwygConfig;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Prepare form
     *
     * @return $this
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareForm()
    {

        $model = $this->_coreRegistry->registry('stockdisplay_stockstatus');

        $isElementDisabled = false;

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();

        $form->setHtmlIdPrefix('page_');
        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Stock Status Form')]);

        if ($model->getId()) {
            $fieldset->addField('status_id', 'hidden', ['name' => 'status_id']);
        }

        $fieldset->addField(
            'status_code',
            'text',
            [
                'name' => 'status_code',
                'id' => 'status_code',
                'label' => __('Status Code'),
                'title' => __('Status Code'),
                'required' => true,
            ]
        );

        $fieldset->addField(
            'status_name',
            'text',
            [
                'name' => 'status_name',
                'id' => 'status_name',
                'label' => __('Status Name'),
                'title' => __('Status Name'),
                'required' => true,
            ]
        );
        $fieldset->addField(
            'sufficient_message',
            'text',
            [
                'name' => 'sufficient_message',
                'id' => 'sufficient_message',
                'label' => __('Sufficient Message'),
                'title' => __('Sufficient Message'),
                'required' => true,
            ]
        );
        $fieldset->addField(
            'short_message',
            'text',
            [
                'name' => 'short_message',
                'id' => 'short_message',
                'label' => __('Short Message'),
                'title' => __('Short Message'),
                'required' => true,
            ]
        );
        $fieldset->addField(
            'outstock_message',
            'text',
            [
                'name' => 'outstock_message',
                'id' => 'outstock_message',
                'label' => __('Out-stock Message'),
                'title' => __('Out-stock Message'),
                'required' => true,
            ]
        );
        $fieldset->addField(
            'threshold',
            'text',
            [
                'name' => 'threshold',
                'id' => 'threshold',
                'label' => __('Threshold Qty'),
                'title' => __('Threshold Qty'),
                'required' => true,
                'class' => 'validate-number validate-greater-than-zero'
            ]
        );


        // Setting custom renderer for content field to remove label column
        $renderer = $this->getLayout()->createBlock(
            'Magento\Backend\Block\Widget\Form\Renderer\Fieldset\Element'
        )->setTemplate(
            'Magento_Cms::page/edit/form/renderer/content.phtml'
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
        return __('Stock Status form');
    }

    /**
     * Prepare title for tab
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Stock Status from');
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
