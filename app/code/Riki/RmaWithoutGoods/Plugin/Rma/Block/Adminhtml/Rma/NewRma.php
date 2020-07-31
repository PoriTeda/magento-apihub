<?php
namespace Riki\RmaWithoutGoods\Plugin\Rma\Block\Adminhtml\Rma;

class NewRma
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    public function __construct(
        \Magento\Framework\Registry $registry
    ){
        $this->_coreRegistry = $registry;
    }

    /**
     * @param \Magento\Rma\Block\Adminhtml\Rma\NewRma $subject
     * @param \Closure $proceed
     * @return string
     */
    public function aroundGetFormActionUrl(
        \Magento\Rma\Block\Adminhtml\Rma\NewRma $subject,
        \Closure $proceed
    ) {
        $request = $subject->getRequest();

        if(
            $request->getModuleName() == 'rma_wg'
            && $request->getActionName() == 'newAction'
        )
            return $subject->getUrl('rma_wg/rma/saveNew', ['order_id' => $this->_coreRegistry->registry('current_order')->getId()]);

        return $proceed();
    }
}
