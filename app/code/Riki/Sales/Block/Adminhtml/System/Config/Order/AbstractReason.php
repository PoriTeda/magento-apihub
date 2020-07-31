<?php
namespace Riki\Sales\Block\Adminhtml\System\Config\Order;

class AbstractReason extends \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray
{
    /**
     * Grid columns
     *
     * @var array
     */
    protected $columns = [];
    protected $customerGroupRenderer;
    /**
     * Enable the "Add after" button or not
     *
     * @var bool
     */
    protected $addAfter = true;
    /**
     * Label of add button
     *
     * @var string
     */
    protected $addButtonLabel;
    /**
     * Check if columns are defined, set template
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->addButtonLabel = __('Add');
    }

    /**
     * Prepare to render
     *
     * @return void
     */
    protected function _prepareToRender()
    {
        $this->addColumn(
            'code',
            ['label' => __('Reason Code')]
        );
        $this->addColumn(
            'title',
            ['label' => __('Reason Title')]
        );

        $this->addAfter = false;
        $this->addButtonLabel = __('Add');
    }
}
