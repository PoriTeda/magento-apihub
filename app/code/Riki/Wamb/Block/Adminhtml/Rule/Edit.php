<?php
namespace Riki\Wamb\Block\Adminhtml\Rule;
use Magento\Backend\Block\Widget\Form\Container;

class Edit extends Container
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->registry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        $this->_objectId = 'id';
        $this->_blockGroup = 'Riki_Wamb';
        $this->_controller = 'adminhtml_rule';

        $this->buttonList->add(
            'saveandcontinue',
            [
                'label' => __('Save and Continue Edit'),
                'class' => 'save',
                'data_attribute' => [
                    'mage-init' => [
                        'button' => ['event' => 'saveAndContinueEdit', 'target' => '#edit_form'],
                    ],
                ]
            ],
            -100
        );

        parent::_construct();
    }

    /**
     * Get current model
     *
     * @return \Riki\Wamb\Model\Rule|null
     */
    public function getModel()
    {
        return $this->registry->registry('current_wamb_rule');
    }



    /**
     * {@inheritdoc}
     *
     * @return \Magento\Framework\Phrase
     */
    public function getHeaderText()
    {
        $rule = $this->getModel();

        if (!$rule instanceof \Riki\Wamb\Model\Rule || !$rule->getRuleId()) {
            return __('New WAMB Rule');
        }

        return __("Edit WAMB: %1", $this->escapeHtml($rule->getName()));
    }


}
