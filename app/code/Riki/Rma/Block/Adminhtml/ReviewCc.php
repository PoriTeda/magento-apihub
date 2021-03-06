<?php
namespace Riki\Rma\Block\Adminhtml;

class ReviewCc extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     * Initialize RMA management page
     *
     * @return void
     */
    public function _construct()
    {
        $this->_controller = 'adminhtml_reviewCc';
        $this->_blockGroup = 'Riki_Rma';
        $this->_headerText = __('Review By CC Operator');

        parent::_construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function _prepareLayout()
    {
        $this->removeButton('add');

        $this->addButton('back', [
            'label' => __('Close'),
            'onclick' => 'setLocation(\'' . $this->getUrl('adminhtml/rma/'). '\')',
            'class' => 'back primary'
        ]);

        return parent::_prepareLayout();
    }
}
