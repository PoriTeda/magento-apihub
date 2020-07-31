<?php
namespace Riki\NpAtobarai\Block\Adminhtml\System\Config\Order;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;

/**
 * Class TransactionPendingReason
 */
class NpTransactionPendingReason extends AbstractFieldArray
{
    /**
     * Grid columns
     *
     * @var array
     */
    protected $columns = [];

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
            ['label' => __('Code')]
        );
        $this->addColumn(
            'title',
            ['label' => __('Message')]
        );

        $this->addAfter = false;
        $this->addButtonLabel = __('Add');
    }
}
