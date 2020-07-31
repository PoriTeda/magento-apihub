<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedCheckout\Block\Adminhtml\Sku;

use Magento\Framework\View\Element\Template;

/**
 * Admin Checkout main form container
 *
 * @method string                                           getListType()
 * @method \Magento\AdvancedCheckout\Block\Adminhtml\Sku\AbstractSku setListType()
 * @method string                                           getDataContainerId()
 * @method \Magento\AdvancedCheckout\Block\Adminhtml\Sku\AbstractSku setDataContainerId()
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
abstract class AbstractSku extends \Magento\Backend\Block\Template
{
    /**
     * List type of current block
     */
    const LIST_TYPE = 'add_by_sku';

    /**
     * @var string
     */
    protected $_template = 'sku/add.phtml';

    /**
     * @var \Magento\Framework\Json\EncoderInterface
     */
    protected $_jsonEncoder;

    /**
     * @codeCoverageIgnore
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param array $data
     * @codeCoverageIgnore
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        array $data = []
    ) {
        $this->_jsonEncoder = $jsonEncoder;
        parent::__construct($context, $data);
    }

    /**
     * Initialize SKU container
     *
     * @return void
     */
    protected function _construct()
    {
        // Used by JS to tell accordions from each other
        $this->setId('sku');
        /* @see \Magento\AdvancedCheckout\Controller\Adminhtml\Index::_getListItemInfo() */
        $this->setListType(self::LIST_TYPE);
        $this->setDataContainerId('sku_container');
    }

    /**
     * Define ADD and DEL buttons
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        $this->addChild(
            'deleteButton',
            \Magento\Backend\Block\Widget\Button::class,
            ['label' => '', 'onclick' => 'addBySku.del(this)', 'class' => 'action-delete']
        );

        $this->addChild(
            'addButton',
            \Magento\Backend\Block\Widget\Button::class,
            ['label' => 'Add another', 'onclick' => 'addBySku.add()', 'class' => 'add']
        );

        return $this;
    }

    /**
     * HTML of "+" button, which adds new field for SKU and qty
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getAddButtonHtml()
    {
        return $this->getChildHtml('addButton');
    }

    /**
     * HTML of "x" button, which removes field with SKU and qty
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getDeleteButtonHtml()
    {
        return $this->getChildHtml('deleteButton');
    }

    /**
     * Returns URL to which CSV file should be submitted
     *
     * @abstract
     * @return string
     * @codeCoverageIgnore
     */
    abstract public function getFileUploadUrl();

    /**
     * Configuration data for AddBySku instance
     *
     * @return string
     */
    public function getAddBySkuDataJson()
    {
        $data = [
            'dataContainerId' => $this->getDataContainerId(),
            'deleteButtonHtml' => $this->getDeleteButtonHtml(),
            'fileUploaded' => \Magento\AdvancedCheckout\Helper\Data::REQUEST_PARAMETER_SKU_FILE_IMPORTED_FLAG,
            // All functions requiring listType affects error grid only
            'listType' => \Magento\AdvancedCheckout\Block\Adminhtml\Sku\Errors\AbstractErrors::LIST_TYPE,
            'errorGridId' => $this->getErrorGridId(),
            'fileFieldName' => \Magento\AdvancedCheckout\Model\Import::FIELD_NAME_SOURCE_FILE,
            'fileUploadUrl' => $this->getFileUploadUrl(),
        ];

        $json = $this->_jsonEncoder->encode($data);
        return $json;
    }

    /**
     * JavaScript instance of AdminOrder or AdminCheckout
     *
     * @abstract
     * @return string
     */
    abstract public function getJsOrderObject();

    /**
     * HTML ID of error grid container
     *
     * @abstract
     * @return string
     */
    abstract public function getErrorGridId();

    /**
     * Retrieve context specific JavaScript
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getContextSpecificJs()
    {
        return '';
    }

    /**
     * Retrieve additional JavaScript
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getAdditionalJavascript()
    {
        return '';
    }
}
