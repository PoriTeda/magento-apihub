<?php
/**
 * TmpRma
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\TmpRma
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\TmpRma\Block\Adminhtml\Rma;

use Riki\TmpRma\Model\Config\Source\Rma\ReturnedWarehouse;

/**
 * Class Grid
 *
 * @category  RIKI
 * @package   Riki\TmpRma\Block
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * RmaFactory
     *
     * @var \Riki\TmpRma\Model\RmaFactory
     */
    protected $rmaFactory;
    /**
     * StatusHelper
     *
     * @var \Riki\TmpRma\Helper\Status
     */
    protected $statusHelper;
    /**
     * ReturnedWarehouseSource
     *
     * @var \Riki\TmpRma\Model\Config\Source\Rma\ReturnedWarehouse
     */
    protected $returnedWhSource;

    /**
     * Grid constructor.
     *
     * @param \Riki\TmpRma\Model\RmaFactory           $rmaFactory       factory
     * @param ReturnedWarehouse                       $returnedWhSource source
     * @param \Riki\TmpRma\Helper\Status              $statusHelper     helper
     * @param \Magento\Backend\Block\Template\Context $context          context
     * @param \Magento\Backend\Helper\Data            $backendHelper    helper
     * @param array                                   $data             param
     */
    public function __construct(
        \Riki\TmpRma\Model\RmaFactory $rmaFactory,
        \Riki\TmpRma\Model\Config\Source\Rma\ReturnedWarehouse $returnedWhSource,
        \Riki\TmpRma\Helper\Status $statusHelper,
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    ) {
        $this->returnedWhSource = $returnedWhSource;
        $this->statusHelper = $statusHelper;
        $this->rmaFactory = $rmaFactory;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    protected function _construct() //@codingStandardsIgnoreLine
    {
        parent::_construct();
        $this->setId('rmaGrid');
        $this->setDefaultSort('id');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    /**
     * {@inheritdoc}
     *
     * @return $this
     */
    protected function _prepareCollection() //@codingStandardsIgnoreLine
    {
        $collection = $this->rmaFactory->create()->getCollection();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * {@inheritdoc}
     *
     * @return $this
     */
    protected function _prepareColumns() //@codingStandardsIgnoreLine
    {
        $this->addColumn(
            'id',
            [
                'header' => __('ID'),
                'type' => 'number',
                'index' => 'id',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id',
            ]
        );
        $this->addColumn(
            'customer_name',
            [
                'header' => __('Customer Name'),
                'index' => 'customer_name'
            ]
        );
        $this->addColumn(
            'customer_address',
            [
                'header' => __('Customer Address'),
                'index' => 'customer_address'
            ]
        );
        $this->addColumn(
            'returned_date',
            [
                'header' => __('Returned Date'),
                'index' => 'returned_date',
                'type' => 'date',
                'header_css_class' => 'col-date',
                'column_css_class' => 'col-date'
            ]
        );
        $this->addColumn(
            'phone_number',
            [
                'header' => __('Home Phone Number'),
                'index' => 'phone_number'
            ]
        );
        $this->addColumn(
            'status',
            [
                'header' => ('Status'),
                'index' => 'status',
                'type' => 'options',
                'options' => $this->statusHelper->getOptions()
            ]
        );
        $this->addColumn(
            'returned_warehouse',
            [
                'header' => __('Warehouse'),
                'index' => 'returned_warehouse',
                'type' => 'options',
                'options' => $this->returnedWhSource->toArray()
            ]
        );

        return parent::_prepareColumns();
    }

    /**
     * {@inheritdoc}
     *
     * @return $this
     */
    protected function _prepareMassaction() //@codingStandardsIgnoreLine
    {
        $this->setMassactionIdField('id');
        $massActionBlock = $this->getMassactionBlock()
            ->setFormFieldName('rma');
        $authorization = $this->getAuthorization();
        if ($authorization->isAllowed('Riki_TmpRma::rma_actions_delete')) {
            $massActionBlock->addItem(
                'delete',
                [
                    'label' => __('Delete'),
                    'url' => $this->getUrl('*/*/massDelete'),
                    'confirm' => __('Are you sure delete these item(s)?'),
                ]
            );
        }
        if ($authorization->isAllowed('Riki_TmpRma::rma_actions_reject')) {
            $massActionBlock->addItem(
                'reject',
                [
                    'label' => __('Reject'),
                    'url' => $this->getUrl('*/*/massReject'),
                    'confirm' => __('Are you sure reject these item(s)?')
                ]
            );
        }
        if ($authorization->isAllowed('Riki_TmpRma::rma_actions_approve')) {
            $massActionBlock->addItem(
                'approve',
                [
                    'label' => __('Approve'),
                    'url' => $this->getUrl('*/*/massApprove'),
                    'confirm' => __('Are you sure approve these item(s)?')
                ]
            );
        }
        if ($authorization->isAllowed('Riki_TmpRma::rma_actions_close')) {
            $massActionBlock->addItem(
                'close',
                [
                    'label' => __('Close'),
                    'url' => $this->getUrl('*/*/massClose'),
                    'confirm' => __('Are you sure close these item(s)?')
                ]
            );
        }


        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current' => true));
    }

    /**
     * Get row url
     *
     * @param object $row row
     *
     * @return string
     */
    public function getRowUrl($row)
    {
        return $this->getUrl(
            '*/*/edit',
            array('id' => $row->getId())
        );
    }
}