<?php

namespace Riki\Catalog\Plugin;

class AfterGetAllPlaceId
{
    /**
     * @var \Riki\AdvancedInventory\Helper\Assignation
     */
    protected $assignationHelper;

    /**
     * CheckAvailableBundleProduct constructor.
     *
     * @param \Riki\AdvancedInventory\Helper\Assignation $assignationHelper
     */
    public function __construct(
        \Riki\AdvancedInventory\Helper\Assignation $assignationHelper
    ) {
        $this->assignationHelper = $assignationHelper;
    }

    /**
     * GetAllPlaceId
     *
     * @return array|null
     */
    public function afterGetAllPlaceId(
        \Riki\Catalog\Model\Product\Bundle\Type $subject,
        $result
    ) {
        if (empty($result)) {
            return $result;
        }

        return $this->assignationHelper->getDefaultPosForFo();
    }
}
