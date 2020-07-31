<?php
namespace Riki\Customer\Ui\Component\EnquiryHeader\Listing\Grid\Column;

use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;

class BusinessUserId extends Column
{

    /**
     * @var \Magento\User\Model\UserFactory
     */
    protected $userFactory;

    /**
     * BusinessUserId constructor.
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param \Magento\User\Model\UserFactory $customerFactory
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        \Magento\User\Model\UserFactory $userFactory,
        array $components = [],
        array $data = []
    ){
        $this->userFactory = $userFactory;
        parent::__construct($context,$uiComponentFactory, $components, $data);
    }

    /**
     * @param array $dataSource
     *
     * @return array
     */

    public function prepareDataSource(array $dataSource)
    {
        return $dataSource;
    }
}