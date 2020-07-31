<?php
namespace Riki\Subscription\Block\Adminhtml\Profile\Edit\AdditionalCategories\Grid\Column\Renderer;


class Link extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    protected $actionUrlBuilder;

    /**
     * Link constructor.
     * @param \Magento\Backend\Block\Context $context
     * @param \Magento\Cms\Block\Adminhtml\Page\Grid\Renderer\Action\UrlBuilder $actionUrlBuilder
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Cms\Block\Adminhtml\Page\Grid\Renderer\Action\UrlBuilder $actionUrlBuilder,
        array $data = []
    ) {
        $this->actionUrlBuilder = $actionUrlBuilder;
        parent::__construct($context, $data);
    }

    /**
     * Render action
     *
     * @param \Magento\Framework\DataObject $row
     * @return string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $profileId = $row->getData('profile_id');
        $html = '<a href="' . $this->getUrl('*/*/edit',['id'=>$profileId]) . '">' . __('View/Edit') . '</a>';
        if(strtotime(date('Y-m-d')) != strtotime($row->getData('next_order_date'))){
            $html .= '<br><a href="' . $this->getUrl('*/*/addSpotProduct',['id'=>$profileId]) . '" >' . __('Add Spot Product') . '</a>';
        }
        return $html;
    }
}