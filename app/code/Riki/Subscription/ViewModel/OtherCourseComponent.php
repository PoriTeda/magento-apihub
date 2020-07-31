<?php

namespace Riki\Subscription\ViewModel;

use Magento\Framework\DataObject;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class OtherCourseComponent extends DataObject implements ArgumentInterface
{
    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;

    public function __construct(\Magento\Framework\Registry $registry, array $data = [])
    {
        parent::__construct($data);
        $this->registry = $registry;
    }


    /**
     * @return mixed
     */
    public function getCurrentProfile()
    {
        return $this->registry->registry('subscription_profile');
    }
}