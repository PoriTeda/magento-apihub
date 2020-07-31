<?php

namespace Riki\Subscription\Block\Profiles;

use Magento\Framework\Registry;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\View\Element\Template;
use Riki\Subscription\Api\WebApi\ProfileRepositoryInterface;
use Riki\Subscription\Helper\Profile\CampaignHelper;

/**
 * Class Select
 *
 * @package Riki\Subscription\Block\Profiles
 */
class Select extends \Magento\Framework\View\Element\Template
{
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var ProfileFactory
     */
    protected $profileFactory;

    /**
     * @var ProfileRepositoryInterface
     */
    protected $profileRepository;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * Select constructor.
     *
     * @param SerializerInterface $serializer
     * @param ProfileRepositoryInterface $profileRepository
     * @param Registry $registry
     * @param Template\Context $context
     * @param array $data
     */
    public function __construct(SerializerInterface $serializer,
                                ProfileRepositoryInterface $profileRepository,
                                Registry $registry,
                                Template\Context $context,
                                array $data = [])
    {
        $this->serializer        = $serializer;
        $this->registry          = $registry;
        $this->profileRepository = $profileRepository;
        parent::__construct($context, $data);
    }

    /**
     * @return string[]
     */
    public function getProfiles()
    {
        $profiles = [];

        $customerId = $this->registry->registry(CampaignHelper::CUSTOMER_ID);
        $landingPageId = $this->registry->registry(CampaignHelper::LANDING_PAGE_ID);
        if ($customerId && $landingPageId) {
            $profiles = $this->profileRepository->getProfileByCustomerForSummerCampaign($customerId, $landingPageId);
        }

        return $profiles;
    }

    /**
     * @return string
     */
    public function getCurrentPageViewUrl()
    {
        return $this->_urlBuilder->getCurrentUrl();
    }

    /**
     * @return string
     */
    public function getProductList()
    {
        return $this->registry->registry(CampaignHelper::PRODUCTS);
    }

    public function getResquestData() {
        $reqDataValue = $this->registry->registry(CampaignHelper::REQUIRE_DATA_VALUE);

        return $reqDataValue;
    }
}