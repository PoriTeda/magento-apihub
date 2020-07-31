<?php

namespace Riki\SalesRule\Plugin;
use Magento\Framework\Model\AbstractModel;
use Magento\SalesRule\Model\ResourceModel\Rule;
class LoadWebsiteIds
{
    /**
     * Fix bug display wrong website ids on BE Cart Price Rule
     *
     * @param Rule          $subject Rule
     * @param \Closure      $proceed Closure
     * @param AbstractModel $object  AbstractModel
     *
     * @return $this
     */
    public function aroundLoadWebsiteIds(
        Rule $subject,
        \Closure $proceed,
        AbstractModel $object
    ) {
        $websiteId = (array)$subject->getWebsiteIds($object->getId());
        $object->setData('website_ids', $websiteId);
        return $this;
    }
}