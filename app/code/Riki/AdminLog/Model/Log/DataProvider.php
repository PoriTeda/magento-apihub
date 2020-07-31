<?php
namespace Riki\AdminLog\Model\Log;

use Magento\Ui\DataProvider\AbstractDataProvider;

class DataProvider extends AbstractDataProvider
{
    /**
     * @var array
     */
    protected $loadedData;

    /**
     * @var \Riki\AdminLog\Model\LogFactory
     */
    protected $consumerLogFactory;


    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var \Riki\AdminLog\Model\ResourceModel\Log\CollectionFactory
     */
    protected $collectionFactory;


    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        \Riki\AdminLog\Model\LogFactory $consumerLogFactory,
        \Riki\AdminLog\Model\ResourceModel\Log\CollectionFactory $collectionFactory,
        \Magento\Framework\App\RequestInterface $request,
        array $meta = [],
        array $data = []
    )
    {
        $this->consumerLogFactory= $consumerLogFactory;
        $this->request = $request;
        $this->collectionFactory = $collectionFactory;

        $this->collection = $this->collectionFactory->create();

        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }


    /**
     * @return array
     */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }
        $items = $this->collection->getItems();
        /** @var \Riki\AdminLog\Model\Log $model */
        foreach ($items as $model) {
            $data = $model->getData();
            if(isset($data['ip']) && $data['ip'] !=''){
                $data['ip'] = long2ip($data['ip']);
            }
            $this->loadedData[$model->getId()] = [
                'data' => $data
            ];
        }
        return $this->loadedData;
    }
}
