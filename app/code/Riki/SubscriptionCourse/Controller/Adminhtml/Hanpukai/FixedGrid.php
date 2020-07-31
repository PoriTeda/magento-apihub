<?php
namespace Riki\SubscriptionCourse\Controller\Adminhtml\Hanpukai;

class FixedGrid extends \Riki\SubscriptionCourse\Controller\Adminhtml\Hanpukai\Products
{

    /**
     * Grid Action
     * Display list of products related to current course
     *
     * @return \Magento\Framework\Controller\Result\Raw
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Raw $resultRaw */
        $resultRaw = $this->resultRawFactory->create();
        return $resultRaw->setContents(
            $this->resultFactory->create()->createBlock(
                'Riki\SubscriptionCourse\Block\Adminhtml\Course\Edit\Tab\Products\HanpukaiFixed',
                'course.product.grid'
            )->toHtml()
        );
    }
}