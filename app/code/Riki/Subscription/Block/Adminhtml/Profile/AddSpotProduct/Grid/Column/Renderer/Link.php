<?php
namespace Riki\Subscription\Block\Adminhtml\Profile\AddSpotProduct\Grid\Column\Renderer;


class Link extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * @var \Magento\Cms\Block\Adminhtml\Page\Grid\Renderer\Action\UrlBuilder
     */
    protected $actionUrlBuilder;
    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    protected $helperProfile;
    /**
     * @var \Riki\Subscription\Model\Profile\ProfileFactory
     */
    protected $profileFactory;

    /**
     * Link constructor.
     * @param \Magento\Backend\Block\Context $context
     * @param \Magento\Cms\Block\Adminhtml\Page\Grid\Renderer\Action\UrlBuilder $actionUrlBuilder
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Cms\Block\Adminhtml\Page\Grid\Renderer\Action\UrlBuilder $actionUrlBuilder,
        \Riki\Subscription\Helper\Profile\Data $helperProfile,
        \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory,
        array $data = []
    ) {
        $this->actionUrlBuilder = $actionUrlBuilder;
        $this->helperProfile =  $helperProfile;
        $this->profileFactory = $profileFactory;
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
        $profileModel = $this->profileFactory->create()->load($profileId);
        $html = '<a href="' . $this->getUrl('*/*/edit',['id'=>$profileId,'list'=>1]) . '">' . __('View/Edit') . '</a>';
        if($row->getStatus() == 1){
            $html .= '<br><a href="' . $this->getUrl('*/*/addSpotProduct',['id'=>$profileId]) . '" >' . __('Add Spot Product') . '</a>';
            $html .= '<br><a href="'. $this->getUrl('*/*/editSalesCount',['id'=>$profileId]). '">'.__('Edit Sales Count').'</a>';
            if($this->helperProfile->checkProfileHaveTmp($profileId) and is_null($profileModel->getPaymentMethod())) {
                $html .= '<br><a href="' . $this->getUrl('*/*/payment_method_edit', ['id' => $profileId]) . '">' . __('Change payment method') . '</a>';
            }
        }
        return $html;
    }
}