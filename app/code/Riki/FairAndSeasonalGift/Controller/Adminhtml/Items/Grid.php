<?php
namespace Riki\FairAndSeasonalGift\Controller\Adminhtml\Items;

class Grid extends \Magento\Backend\App\Action
{
    /**
     * @var Registry
     */
    protected $registry;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $registry
    ) {
        parent::__construct($context);
        $this->registry = $registry;
    }
    /**
     * @return mixed
     */
    public function execute()
    {
        $this->registry->register('current_fair_id', $this->getRequest()->getParam('fair_id'));

        return $this->getResponse()->setBody(
            $this->_view->getLayout()->createBlock(
                'Riki\FairAndSeasonalGift\Block\Adminhtml\Items\Grid'
            )->toHtml()
        );
    }
}
