<?php

namespace Riki\MachineApi\Plugin\Promo\Model;

class Registry
{
    /**
     * @var \Riki\MachineApi\Helper\Data
     */
    protected $helper;

    /**
     * Registry constructor.
     *
     * @param \Riki\MachineApi\Helper\Data $helper
     */
    public function __construct(
        \Riki\MachineApi\Helper\Data $helper
    )
    {
        $this->helper = $helper;
    }

    /**
     * Do not add free gift to free of machine order
     *
     * @param \Amasty\Promo\Model\Registry $subject
     * @param \Closure $proceed
     *
     * @return array
     */
    public function aroundGetPromoItems(\Amasty\Promo\Model\Registry $subject, \Closure $proceed)
    {
        if ($this->helper->isMachineApiRequest()) {
            return ['_groups' => []];
        }
        return $proceed();
    }
}
