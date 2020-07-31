<?php
namespace Riki\Sales\Block\Adminhtml\Order\View;

use Magento\Framework\Exception\NoSuchEntityException;

class AdditionalInfo extends \Magento\Sales\Block\Adminhtml\Order\AbstractOrder
{
    const ORDER_SPOT = 'SPOT';

    const ORDER_SUBSCRIPTION = 'SUBSCRIPTION';

    const ORDER_HANPUNKAI = 'HANPUKAI';

    protected $_orderChannel;
    protected $_orderChargeType;

    /**
     * @var \Riki\Subscription\Api\ProfileRepositoryInterface
     */
    protected $_profileRepository;

    protected $_courseFactory;

    /**
     * @var \Riki\Sales\Model\Order\OrderAdditionalInformationFactory
     */
    protected $orderAdditionalInformationFactory;

    /**
     * AdditionalInfo constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Sales\Helper\Admin $adminHelper
     * @param \Riki\Sales\Model\Config\Source\OrderChannel $orderChannel
     * @param \Riki\Sales\Model\Config\Source\OrderType $orderType
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Riki\Subscription\Api\ProfileRepositoryInterface $profileRepository
     * @param \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Sales\Helper\Admin $adminHelper,
        \Riki\Sales\Model\Config\Source\OrderChannel $orderChannel,
        \Riki\Sales\Model\Config\Source\OrderType $orderType,
        \Riki\Subscription\Api\ProfileRepositoryInterface $profileRepository,
        \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory,
        \Riki\Sales\Model\Order\OrderAdditionalInformationFactory $orderAdditionalInformationFactory,
        array $data = []
    ){
        parent::__construct(
            $context,
            $registry,
            $adminHelper,
            $data
        );

        $this->_orderChannel = $orderChannel;
        $this->_orderChargeType = $orderType;
        $this->_profileRepository = $profileRepository;
        $this->_courseFactory = $courseFactory;
        $this->orderAdditionalInformationFactory = $orderAdditionalInformationFactory;
    }

    /**
     * @return \Magento\Framework\Phrase
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getChannelTitle(){
        return $this->_orderChannel->getTitleByCode($this->getOrder()->getOrderChannel());
    }

    /**
     * @return \Magento\Framework\Phrase
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getOrderChargeTypeTitle(){
        return $this->_orderChargeType->getTitleByCode($this->getOrder()->getChargeType());
    }

    /**
     * GetOrderTypeTitle
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getOrderTypeTitle(){
        if(self::ORDER_SUBSCRIPTION == $this->getOrder()->getRikiType() ||
            \Riki\Sales\Helper\Order::RIKI_TYPE_DELAY_PAYMENT == $this->getOrder()->getRikiType()
        ){
            echo __('Subscription Order');
        }
        elseif(self::ORDER_HANPUNKAI == $this->getOrder()->getRikiType()){
            echo __('Hanpukai Order');
        }
        else{
            echo __('Normal Order');
        }
    }

    /**
     * IsSubscriptionOrder
     *
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function isSubscriptionOrder(){
        if($this->getOrder()->getSubscriptionProfileId()){
            return true;
        }
        return false;
    }

    /**
     * GetSubscriptionOrderCourse
     *
     * @return \Magento\Framework\Phrase|string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getSubscriptionOrderCourse()
    {
        $iProfileId = $this->getOrder()->getSubscriptionProfileId();
        $result = __('None');

        try {
            $oProfile = $this->_profileRepository->get($iProfileId);
            $iCourseId = $oProfile->getCourseId();
            if ($iCourseId) {
                $oCourse = $this->_courseFactory->create()->load($iCourseId);
                $sProfileUrl = $this->_urlBuilder->getUrl('profile/profile/edit', ['id' => $iProfileId, 'list'=>1]);
                $result = '<a href="' . $sProfileUrl . '">' . $oCourse->getCourseName() . '</a>';
            }
        } catch (NoSuchEntityException $e) {
            $result = __('None');
        }

        return $result;
    }

    public function getOrderAdditionalInformation() {
        $orderAdditionalInformation = $this->orderAdditionalInformationFactory->create()
            ->load($this->getOrder()->getId(),'order_id');

        return $orderAdditionalInformation;
    }
}
