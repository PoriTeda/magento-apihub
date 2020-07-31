<?php
namespace Riki\AdvancedInventory\Block\Adminhtml;

class ReAssignation extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     * Initialize RMA management page
     *
     * @return void
     */
    public function _construct()
    {
        $this->_controller = 'adminhtml_reAssignation';
        $this->_blockGroup = 'Riki_AdvancedInventory';
        $this->_headerText = __('Re-assign stock for order from CSV');

        parent::_construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function _prepareLayout()
    {
        $this->addButton('add', [
            'label' =>  __('Import new CSV file'),
            'onclick' => 'setLocation(\'' . $this->getCreateUrl() . '\')',
            'class' => 'add primary'
        ]);

        $this->addButton(
            'run_cron',
            [
                'label' => __('Schedule Now'),
                'onclick' => 'setLocation(\'' . $this->getUrl('*/*/schedulenow') . '\')',
                'class' => 'primary'
            ]
        );

        $this->addButton('back', [
            'label' => __('Back'),
            'onclick' => 'setLocation(\'' . $this->getUrl('*/*/'). '\')',
            'class' => 'back primary'
        ]);

        return parent::_prepareLayout();
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getCreateUrl()
    {
        return $this->getUrl('*/*/newAction');
    }
}
