<?php
namespace Riki\AdvancedInventory\Block\Adminhtml\OutOfStock;

class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var \Magento\Sales\Model\Order
     */
    protected $originalOrder;

    /**
     * @var \Magento\Sales\Model\Order
     */
    protected $generatedOrder;

    /**
     * @var string
     */
    protected $returnUrl;

    /**
     * @var \Riki\AdvancedInventory\Api\OutOfStockRepositoryInterface
     */
    protected $outOfStockRepository;

    /**
     * @var \Magento\Framework\Url\EncoderInterface
     */
    protected $urlEncoder;

    /**
     * Grid constructor.
     *
     * @param \Magento\Framework\Url\EncoderInterface $urlEncoder
     * @param \Riki\AdvancedInventory\Api\OutOfStockRepositoryInterface $outOfStockRepository
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Url\EncoderInterface $urlEncoder,
        \Riki\AdvancedInventory\Api\OutOfStockRepositoryInterface $outOfStockRepository,
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    ) {
        $this->urlEncoder = $urlEncoder;
        $this->outOfStockRepository = $outOfStockRepository;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function _construct()
    {
        parent::_construct();

        $this->setDefaultDir('DESC');
    }

    /**
     * Set original order id
     *
     * @param \Magento\Sales\Model\Order $order
     *
     * @return $this
     */
    public function setOriginalOrder(\Magento\Sales\Model\Order $order)
    {
        $this->originalOrder = $order;
        return $this;
    }

    /**
     * Set generated order id
     *
     * @param \Magento\Sales\Model\Order $order
     *
     * @return $this
     */
    public function setGeneratedOrder(\Magento\Sales\Model\Order $order)
    {
        $this->generatedOrder = $order;
        return $this;
    }

    /**
     * Set return url
     *
     * @param $url
     *
     * @return $this
     */
    public function setReturnUrl($url)
    {
        $this->returnUrl = $url;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return \Magento\Framework\Data\Collection
     */
    public function getCollection()
    {
        if (!$this->_collection) {
            /** @var \Riki\AdvancedInventory\Model\ResourceModel\OutOfStock\Collection $collection */
            $collection = $this->outOfStockRepository->createFromArray()->getCollection();

            if ($this->originalOrder) {
                $conditionType = $this->originalOrder->getId() ? 'eq' : 'is';
                $collection->addFieldToFilter('original_order_id', [
                    $conditionType => $conditionType == 'is' ? new \Zend_Db_Expr('NULL') : $this->originalOrder->getId()
                ]);
            }

            if ($this->generatedOrder) {
                $conditionType = $this->generatedOrder->getId() ? 'eq' : 'is';
                $collection->addFieldToFilter('generated_order_id', [
                    $conditionType => $conditionType == 'is' ? new \Zend_Db_Expr('NULL') : $this->originalOrder->getId()
                ]);
            }

            $this->setCollection($collection);
        }

        return $this->_collection;
    }

    /**
     * {@inheritdoc}
     *
     * @return \Magento\Backend\Block\Widget\Grid\Extended
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'id',
            [
                'header' => __('ID'),
                'index' => 'entity_id',
                'type' => 'number'
            ]
        );

        $this->addColumn(
            'product_id',
            [
                'header' => __('Product Id'),
                'index' => 'product_id',
                'type' => 'text'
            ]
        );

        $this->addColumn(
            'product_sku',
            [
                'header' => __('Product SKU'),
                'index' => 'product_sku',
                'type' => 'text',
            ]
        );

        $this->addColumn(
            'qty',
            [
                'header' => __('Qty'),
                'index' => 'qty',
                'type' => 'text',
            ]
        );

        $this->addColumn(
            'type',
            [
                'header' => __('Type'),
                'type' => 'text',
                'renderer' => \Riki\AdvancedInventory\Block\Adminhtml\OutOfStock\Grid\Column\Renderer\Type::class,
                'filter' => false
            ]
        );

        if (!$this->originalOrder) {
            $this->addColumn(
                'original_order_id',
                [
                    'header' => __('Original Order Id'),
                    'index' => 'original_order_id',
                    'type' => 'text',
                ]
            );
        }

        if (!$this->generatedOrder) {
            $this->addColumn(
                'generated_order_id',
                [
                    'header' => __('Generated Order Id'),
                    'index' => 'generated_order_id',
                    'type' => 'text',
                ]
            );
        }

        return parent::_prepareColumns();
    }

    /**
     * {@inheritdoc}
     *
     * @return \Magento\Backend\Block\Widget\Grid\Extended
     */
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('entity_id');
        $this->getMassactionBlock()->setFormFieldName('entity_ids');
        $this->getMassactionBlock()->addItem(
            'cancel',
            [
                'label' => __('Cancel'),
                'url' => $this->getCancelUrl(),
                'confirm' => __('Are you sure you want to cancel selected out of stock product?')
            ]
        );

        return parent::_prepareMassaction();
    }

    /**
     * Get cancel url
     *
     * @return string
     */
    public function getCancelUrl()
    {
        if ($this->returnUrl) {
            return rtrim($this->getUrl('riki_advancedinventory/outofstock/cancel'), '/') . '/return_url/' . $this->urlEncoder->encode($this->returnUrl);
        }

        return $this->getUrl('riki_advancedinventory/outofstock/cancel');
    }
}
