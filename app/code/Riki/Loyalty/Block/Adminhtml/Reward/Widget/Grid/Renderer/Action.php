<?php

namespace Riki\Loyalty\Block\Adminhtml\Reward\Widget\Grid\Renderer;

class Action extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Input
{
    /**
     * @var \Magento\Backend\Model\UrlInterface
     */
    protected $_urlBuilder;

    /**
     * OrderNo constructor.
     *
     * @param \Magento\Backend\Block\Context $context
     * @param \Magento\Backend\Model\UrlInterface $urlBuilder
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Backend\Model\UrlInterface $urlBuilder,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->_urlBuilder = $urlBuilder;
    }

    /**
     * @param \Magento\Framework\DataObject $row
     * @return string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        if (!$this->_authorization->isAllowed('Riki_Loyalty::delete_point') ||
            $row->getData('point_type') != \Riki\Loyalty\Model\Reward::TYPE_ADJUSTMENT) {
            return '';
        }
        $url = $this->_urlBuilder->getUrl('riki_loyalty/reward/delete', ['id' => $row->getId()]);
        $event = 'jQuery("#btn-delete-point-'.$row->getId().'").reducePoint({url:"'.$url.'"}).reducePoint("showDialog")';
        return "<a id='btn-delete-point-{$row->getId()}' onclick='{$event}' href='javascript:void(0)'>".__('Delete')."</a>";

    }

}