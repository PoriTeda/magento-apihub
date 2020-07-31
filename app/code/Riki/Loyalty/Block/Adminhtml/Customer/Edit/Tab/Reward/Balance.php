<?php

namespace Riki\Loyalty\Block\Adminhtml\Customer\Edit\Tab\Reward;

class Balance extends \Magento\Backend\Block\Template
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;

    /**
     * PersonalInfo constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * @return array|null
     */
    public function getStatistic()
    {
        return $this->coreRegistry->registry('riki_reward_statistic');
    }

    /**
     * @return bool
     */
    public function canAddPoint(){
        return $this->_authorization->isAllowed('Riki_Customer::addpoint');
    }
}
