<?php

namespace Wyomind\PointOfSale\Block;

class PointOfSale extends \Magento\Framework\View\Element\Template
{

    protected $_pointofsaleModel = null;
    protected $_countryModel = null;
    protected $_posHelper = null;
    private $_places = null;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Wyomind\PointOfSale\Model\PointOfSale $pointofsaleModel,
        \Magento\Directory\Model\Country $countryModel,
        \Wyomind\PointOfSale\Helper\Data $helper,
        array $data = []
    ) {
    
        parent::__construct($context, $data);
        $this->_pointofsaleModel = $pointofsaleModel;
        $this->_countryModel = $countryModel;
        $this->_posHelper = $helper;
    }
    
    public function setPlaces($places)
    {
        $this->_places = $places;
    }

    public function getPointofsale()
    {
        if ($this->_places !== null) {
            $collection = $this->_places;
        } else {
            $collection = $this->_pointofsaleModel->getPlacesByStoreId($this->_storeManager->getStore()->getStoreId(), true);
        }
        return $collection;
    }

    public function getCountries()
    {
        $collection = $this->_pointofsaleModel->getCountries($this->_storeManager->getStore()->getStoreId());
        $countries = [];
        foreach ($collection as $country) {
            if ($country->getCountryCode()) {
                $countryModel = $this->_countryModel->loadByCode($country->getCountryCode());
                $countryName = $countryModel->getName();
                $countries[] = [
                    'code' => $country->getCountryCode(),
                    'name' => $countryName,
                ];
            }
        }
        return $countries;
    }

    public function getJsonData()
    {
        $i = 0;
        $data = [];
        foreach ($this->getPointofsale() as $place) {
            $fullAdress = $place->getAddressLine1();
            if ($place->getAddressLine_2()) {
                $fullAdress.="," . $place->getAddressLine2();
            }
            $fullAdress.="," . $place->getCity();
            if ($place->getCountryCode()) {
                $fullAdress.="," . $this->_countryModel->loadByCode($place->getCountryCode())->getName();
            }
            if (!$place->getGoogleRequest()) {
                $request = $fullAdress;
            } else {
                $request = $place->getGoogleRequest();
            }

            $data[] = [
                "id" => $place->getPlaceId(),
                "title" => "<h4><b>" . $place->getName() . "</b></h4>",
                "links" => [
                    "directions" => "<a href=\"javascript:PointOfSale.getDirections(" . $i . ")\">" . __("Get Directions") . "</a>",
                    "showOnMap" => "<a target=\"_blank\" href=\"http://maps.google.com/maps?q=" . $request . "\">" . __("Show on Google Map") . "</a>"
                ],
                "name" => $place->getName(),
                "lat" => $place->getLatitude(),
                "lng" => $place->getlongitude(),
                "country" => $place->getCountryCode(),
                "duration" => ["text" => null, "value" => null],
                "distance" => ["text" => null, "value" => null]
            ];
            $i++;
        }
        return json_encode($data);
    }

    public function getPosHelper()
    {
        return $this->_posHelper;
    }

    public function getGoogleApiKey()
    {
        return $this->_posHelper->getGoogleApiKey();
    }
}
