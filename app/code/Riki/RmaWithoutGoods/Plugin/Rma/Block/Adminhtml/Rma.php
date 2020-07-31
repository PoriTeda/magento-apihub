<?php
namespace Riki\RmaWithoutGoods\Plugin\Rma\Block\Adminhtml;

class Rma
{

    /**
     * @var \Magento\Framework\AuthorizationInterface
     */
    protected $authorization;

    /**
     * Edit constructor.
     *
     * @param \Magento\Framework\AuthorizationInterface $authorization
     */
    public function __construct(
        \Magento\Framework\AuthorizationInterface $authorization
    ) {
        $this->authorization = $authorization;
    }

    /**
     * @param \Magento\Rma\Block\Adminhtml\Rma $subject
     * @param \Magento\Framework\View\LayoutInterface $layout
     * @return array
     */
    public function beforeSetLayout(
        \Magento\Rma\Block\Adminhtml\Rma $subject,
        \Magento\Framework\View\LayoutInterface $layout
    )
    {

        if(
            !$subject->getParentBlock() &&
            $this->authorization->isAllowed('Riki_RmaWithoutGoods::rma_wg_actions_create')
        ){
            $subject->addButton('new_return_wg', [
                'label' => __('New Return Without Goods'),
                'onclick' => 'setLocation(\'' . $subject->getUrl('rma_wg/rma/newAction'). '\')',
                'class' => 'new_return_wg add primary'
            ],
                0,
                20
            );
        }

        return [$layout];
    }
}