<?php
namespace Bluecom\Paygent\Ui\Component\Error\Listing\Grid\Column;

use Magento\Ui\Component\Listing\Columns\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;

class CourseName extends Column
{
    /**
     * @var \Riki\Subscription\Model\Profile\Profile
     */
    protected $_subscriptionProfile;

    public  function  __construct(
        \Riki\Subscription\Model\Profile\Profile $subscriptionProfile,
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        array $components = [],
        array $data = []
    )
    {
        $this->_subscriptionProfile = $subscriptionProfile;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @param array $dataSource
     *
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }
        foreach ($dataSource['data']['items'] as $key=> &$item) {
            if(isset($item['subscription_profile_id']) && $item['subscription_profile_id'] !=''){
                $subscriptionProfile = $this->_subscriptionProfile->load($item['subscription_profile_id']);
                if($subscriptionProfile && $subscriptionProfile->getCourseName() !=''){
                    $item['subscription_profile_id'] = $subscriptionProfile->getCourseName();
                }else{
                    $item['subscription_profile_id'] = null;
                }
            }
        }
        return $dataSource;
    }
}