<?php


namespace Nestle\Gillette\Helper;


use Nestle\Gillette\Api\Data\CartEstimationInterface;

/**
 * Class Data
 * @package Nestle\Gillette\Helper
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Store\Model\App\Emulation
     */
    protected $appEmulation;

    /**
     * @var \Magento\Catalog\Helper\ImageFactory
     */
    protected $productImageHelper;

    /**
     * @var \Riki\Loyalty\Model\RewardManagement
     */
    protected $rewardManagement;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    public function __construct(
        \Magento\Store\Model\App\Emulation $appEmulation,
        \Magento\Catalog\Helper\ImageFactory $imageFactory,
        \Riki\Loyalty\Model\RewardManagement $rewardManagement,
        \Psr\Log\LoggerInterface $logger
    )
    {
        $this->appEmulation = $appEmulation;
        $this->productImageHelper = $imageFactory;
        $this->rewardManagement = $rewardManagement;
        $this->logger = $logger;
    }

    /**
     * Helper function that provides full cache image url
     * @param \Magento\Catalog\Model\Product
     * @param string|null $imageType
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getImageUrl($product, string $imageType = null){
        $storeId = 1; // This API only support for EC store
        $this->appEmulation->startEnvironmentEmulation($storeId, \Magento\Framework\App\Area::AREA_FRONTEND, true);
        $imageUrl = $this->productImageHelper->create()->init($product, $imageType)->getUrl();
        $this->appEmulation->stopEnvironmentEmulation();
        return $imageUrl;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param $customer
     * @return array
     */
    public function getRewardPoint($order, $customer) {
        $customerCode = $customer->getCustomAttribute('consumer_db_id')->getValue();
        $totalPoint = $this->rewardManagement->getPointBalance($customerCode, false);
        $rewardPoint = $order->getData('reward_point')? : [];
        if (empty($rewardPoint)) {
            $customerSetting = $this->rewardManagement->getRewardUserSetting($customerCode);
            $rewardPoint['reward_user_setting'] = (int)$customerSetting['use_point_type'];
            $rewardPoint['reward_user_redeem'] = (int)$customerSetting['use_point_amount'];
        }
        $rewardPoint['balance'] = (int)$totalPoint;
        return $rewardPoint;
    }

    /**
     * @param CartEstimationInterface $request
     */
    public function buildRequestLog($request, $type) {
        $products = [];
        foreach ($request->getProducts() as $product) {
            $products[] = $product->getData();
        }
        $request->setProducts($products);
        $this->logger->info($type . ' request::' . json_encode($request->getData(),JSON_UNESCAPED_UNICODE));
    }

}