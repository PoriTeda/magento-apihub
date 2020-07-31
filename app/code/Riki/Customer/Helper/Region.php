<?php
namespace Riki\Customer\Helper;

class Region extends \Magento\Framework\App\Helper\AbstractHelper
{
    const CONST_DEFAULT_COUNTRY = 'JP';

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $_resource;
    /**
     * @var MappingZoneJapan
     */
    protected $_mappingZoneJapan = array(
        'HKD' => 1,
        'AMR' => 2,
        'IWT' => 3,
        'MYG' => 4,
        'AKT' => 5,
        'YGT' => 6,
        'FSM' => 7,
        'IBR' => 8,
        'TOC' => 9,
        'GUM' => 10,
        'STM' => 11,
        'CHB' => 12,
        'TKY' => 13,
        'KNG' => 14,
        'NGT' => 15,
        'TYM' => 16,
        'IKW' => 17,
        'FKI' => 18,
        'YNS' => 19,
        'NGN' => 20,
        'GFU' => 21,
        'SZK' => 22,
        'AIC' => 23,
        'MIE' => 24,
        'SHG' => 25,
        'KYT' => 26,
        'OSK' => 27,
        'HYG' => 28,
        'NRA' => 29,
        'WKY' => 30,
        'TTR' => 31,
        'SMN' => 32,
        'OKY' => 33,
        'HRS' => 34,
        'YGC' => 35,
        'TKS' => 36,
        'KGW' => 37,
        'EHM' => 38,
        'KCH' => 39,
        'FKO' => 40,
        'SAG' => 41,
        'NGS' => 42,
        'KMM' => 43,
        'OTA' => 44,
        'MYZ' => 45,
        'KGS' => 46,
        'OKN' => 47,
    );

    public function __construct(
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Magento\Framework\App\ResourceConnection $resource
    )
    {
        $this->_regionFactory = $regionFactory;
        $this->_resource = $resource;
    }

    /**
     * GetAPIRegionId
     *
     * @param $regionCode string
     *
     * @return int
     */
    public function getAPIRegionId($regionCode)
    {
        if(isset($this->_mappingZoneJapan[$regionCode])){
            return $this->_mappingZoneJapan[$regionCode];
        }

        return 1; // default is Hokkaido
    }

    /**
     * GetJapanRegion
     *
     * @param $regionId
     *
     * @return string
     */
    public function getJapanRegion($regionId){
        $connection = $this->_resource->getConnection();
        $select = $connection->select()->from(
            ['region' => $connection->getTableName('directory_country_region_name')]
        )->where(
            "region.region_id = ?",
            $regionId
        );

        $data = $connection->fetchRow($select);
        return isset($data['name'])?$data['name']:'';
    }

    public function getRegionIdByName($name){
        $connection = $this->_resource->getConnection();
        $select = $connection->select()->from(
            ['region' => $connection->getTableName('directory_country_region_name')]
        )->where(
            "region.name = ?",
            $name
        );

        $data = $connection->fetchRow($select);
        return isset($data['region_id'])?$data['region_id']:false;
    }
}