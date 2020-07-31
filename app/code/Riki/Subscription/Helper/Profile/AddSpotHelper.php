<?php
namespace Riki\Subscription\Helper\Profile;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\Escaper;
use Magento\Framework\Stdlib\StringUtils;
use Magento\Search\Model\QueryFactory;
use Magento\Store\Model\StoreManagerInterface;
use Riki\DeliveryType\Model\Delitype as DelitypeModel;

class AddSpotHelper extends \Magento\Search\Helper\Data
{
    /**
     * @var \Riki\Subscription\Model\Profile\ProfileFactory
     */
    protected $profileFactory;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    public function __construct(
        Context $context,
        StringUtils $string,
        Escaper $escaper,
        StoreManagerInterface $storeManager,
        \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
    ) {
        parent::__construct($context, $string, $escaper, $storeManager);
        $this->profileFactory = $profileFactory;
        $this->productRepository =  $productRepository;
    }

    /**
     * Retrieve result page url and set "secure" param to avoid confirm
     * message when we submit form from secure page to unsecure
     *
     * @param   string $query
     * @return  string
     */
    public function getResultUrl($query = null)
    {
        $profileId = $this->_getRequest()->getParam('id');

        return $this->_getUrl(
            'subscriptions/profile/addSpotProduct',
            [
                'id' => $profileId,
                '_query' => [QueryFactory::QUERY_VAR_NAME => $query],
                '_secure' => $this->_request->isSecure()
            ]
        );
    }

    public function getDeliveryTypeOfProfile($profileId)
    {
        $arrGroupCoolNormalDm = array(DelitypeModel::COOL, DelitypeModel::NORMAl, DelitypeModel::DM);
        $profileModel = $this->profileFactory->create()->load($profileId);
        $arrProductCartSession = [];
        if($profileModel->getId()) {
            $arrProductCartSession = $profileModel->getProductCartData();
        }
        $currentDeliveryType = null;
         foreach ($arrProductCartSession as $item) {
            $currentDeliveryType = $this->productRepository->getById($item->getData('product_id'))->getDeliveryType();
            if ($currentDeliveryType != null) {
                break;
            }
        }
        if (in_array($currentDeliveryType, $arrGroupCoolNormalDm)) {
            return $arrGroupCoolNormalDm;
        }
        return [$currentDeliveryType];
    }
}