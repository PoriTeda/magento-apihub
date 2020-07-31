<?php
namespace Riki\Subscription\Model\Profile;

class ProfilePool
{
    /**
     * @var \Riki\Subscription\Model\Profile\ProfileFactory
     */
    protected $profileFactory;

    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    protected $profileHelper;

    protected $profileMain = null;
    protected $profileVersion = null;
    protected $profileTmp = null;

    public function __construct(
        \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory,
        \Riki\Subscription\Helper\Profile\Data $profileHelper
    ) {
        $this->profileFactory = $profileFactory;
        $this->profileHelper = $profileHelper;
    }

    /**
     * Load profile data
     *
     * @param $profileId
     * @return $this
     */
    public function load($profileId)
    {
        $profileMain = $this->profileFactory->create()->load($profileId,null,true);

        if ($profileMain->getId()) {

            $this->profileMain = $profileMain;

            $versionId = $this->profileHelper->checkProfileHaveVersion($profileId);

            if($versionId) {

                $profileVersion = $this->profileFactory->create()->load($versionId);

                if ($profileVersion->getId()) {
                    $this->profileVersion = $profileVersion;
                }
            }

            $tmp = $this->profileHelper->getTmpProfile($profileId);

            if ($tmp) {

                $tmpId = $tmp->getData('linked_profile_id');

                $profileTmp = $this->profileFactory->create()->load($tmpId);

                if($profileTmp->getId()) {
                    $this->profileTmp = $profileTmp;
                }
            }
        }

        return $this;
    }

    /**
     * Get profile id
     *
     * @return null|mixed
     */
    public function getId()
    {
        if ($this->profileMain) {
            return $this->profileMain->getProfileId();
        }
        return null;
    }

    /**
     * Get profile data, default is data on profile main
     *
     * @param $key
     * @return null|mixed
     */
    public function getData($key)
    {
        if ($this->profileMain) {
            return $this->profileMain->getData($key);
        }
        return null;
    }

    /**
     * Set data for profile
     *
     * @param $key
     * @param $value
     */
    public function setData($key, $value)
    {
        if (!empty($this->profileMain)) {
            $this->profileMain->setData($key, $value);
        }

        if (!empty($this->profileVersion)) {
            $this->profileVersion->setData($key, $value);
        }

        if (!empty($this->profileTmp)) {
            $this->profileTmp->setData($key, $value);
        }
    }

    /**
     * Save profile data
     * @return $this
     */
    public function save()
    {
        if (!empty($this->profileMain)) {
            $this->profileMain->save();
        }

        if (!empty($this->profileVersion)) {
            $this->profileVersion->save();
        }

        if (!empty($this->profileTmp)) {
            $this->profileTmp->save();
        }

        return $this;
    }
}