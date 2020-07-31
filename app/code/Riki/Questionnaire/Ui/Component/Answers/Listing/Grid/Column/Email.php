<?php

namespace Riki\Questionnaire\Ui\Component\Answers\Listing\Grid\Column;

class Email extends \Magento\Ui\Component\Listing\Columns\Column
{
    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $_customerRepository;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * Email constructor.
     * @param \Magento\Framework\View\Element\UiComponent\ContextInterface $context
     * @param \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Psr\Log\LoggerInterface $logger
     * @param array $components
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\UiComponent\ContextInterface $context,
        \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Psr\Log\LoggerInterface $logger,
        $components = [],
        $data = []
    ){
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->_customerRepository = $customerRepository;
        $this->_logger = $logger;
    }

    /**
     * Prepare Data source
     *
     * @param array $dataSource
     *
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        foreach ($dataSource['data']['items'] as &$item) {
            if( !empty($item['customer_id']) ){
                $customer = $this->getCustomerById($item['customer_id']);
                if($customer){
                    $item['email'] = $customer->getEmail();
                }
            }
        }

        return $dataSource;
    }

    /**
     * @param $customerId
     * @return bool|\Magento\Customer\Api\Data\CustomerInterface
     */
    public function getCustomerById($customerId){
        try {
            return $this->_customerRepository->getById($customerId);
        } catch (\Exception $e){
            $this->_logger->critical($e->getMessage());
            return false;
        }
    }
}