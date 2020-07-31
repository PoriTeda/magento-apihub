<?php
namespace Riki\Subscription\Webapi;
use Magento\Framework\Webapi\ServiceOutputProcessor as FrameworkServiceOutputProcessor;

class ServiceOutputProcessor
{
    /**
     * Convert associative array into proper data object.
     * Return special object for Watson API
     *
     * @param FrameworkServiceOutputProcessor $subject
     * @param \Closure $proceed
     * @param $data
     * @param $serviceClassName
     * @param $serviceMethodName
     *
     * @return mixed
     */
    public function aroundProcess(
        FrameworkServiceOutputProcessor $subject,
        \Closure $proceed,
        $data,
        $serviceClassName,
        $serviceMethodName
    ) {
        if ($serviceClassName == \Riki\Subscription\Api\WebApi\ProfileRepositoryInterface::class) {
            return $data;
        }
        if ($serviceClassName == \Riki\Catalog\Api\CategoryLinkManagementInterface::class) {
            return $data;
        }
        if ($serviceClassName == \Riki\Checkout\Api\DeliveryDateMethodInterface::class) {
            return $data;
        }
        if ($serviceClassName == \Riki\SpotOrderApi\Api\PaymentInformationManagementInterface::class) {
            return $data;
        }
        if ($serviceClassName == \Riki\SpotOrderApi\Api\CartItemRepositoryInterface::class) {
            return $data;
        }

        return $proceed($data, $serviceClassName, $serviceMethodName);
    }
}