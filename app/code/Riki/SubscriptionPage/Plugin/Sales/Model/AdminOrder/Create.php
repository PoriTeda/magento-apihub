<?php

namespace Riki\SubscriptionPage\Plugin\Sales\Model\AdminOrder;

use \Magento\Framework\Exception\LocalizedException;

class Create
{
    /**
     * @var \Riki\SubscriptionPage\Helper\Data
     */
    protected $subPageHelper;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * Create constructor.
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Riki\SubscriptionPage\Helper\Data $subPageHelper
     */
    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Riki\SubscriptionPage\Helper\Data $subPageHelper
    ) {
        $this->request = $request;
        $this->subPageHelper = $subPageHelper;
    }

    /**
     * Validate application limit
     *
     * @param \Magento\Sales\Model\AdminOrder\Create $subject
     * @param callable $proceed
     * @param array $products
     *
     * @return bool|\Exception|LocalizedException
     *
     * @throws LocalizedException
     */
    public function aroundAddProducts(
        \Magento\Sales\Model\AdminOrder\Create $subject,
        \Closure $proceed,
        $products
    ) {
        $quote = $subject->getQuote();
        $courseId = $this->request->getParam('course_id');
        if ($courseId > 0) {
            $customerId = $quote->getCustomerId();
            $applicationLimitValidatingResult = $this->subPageHelper->checkApplicationLimit($customerId, $courseId);
            if ($applicationLimitValidatingResult['has_error']) {
                $errorMessage = $this->subPageHelper->getApplicationLimitErrorMessage(
                    $applicationLimitValidatingResult
                );
                throw new LocalizedException(__($errorMessage));
            }
        }
        return $proceed($products);
    }
}
