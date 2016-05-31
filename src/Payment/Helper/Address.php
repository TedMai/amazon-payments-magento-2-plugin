<?php

namespace Amazon\Payment\Helper;

use Amazon\Core\Domain\AmazonAddress;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Api\Data\RegionInterfaceFactory;
use Magento\Directory\Model\RegionFactory;

class Address
{
    /**
     * @var AddressInterfaceFactory
     */
    protected $addressFactory;

    /**
     * @var RegionFactory
     */
    protected $regionFactory;

    /**
     * @var RegionInterfaceFactory
     */
    protected $regionDataFactory;

    public function __construct(
        AddressInterfaceFactory $addressFactory,
        RegionFactory $regionFactory,
        RegionInterfaceFactory $regionDataFactory
    ) {
        $this->addressFactory    = $addressFactory;
        $this->regionFactory     = $regionFactory;
        $this->regionDataFactory = $regionDataFactory;
    }

    /**
     * Convert Amazon Address to Magento Address
     *
     * @param AmazonAddress $amazonAddress
     *
     * @return AddressInterface
     */
    public function convertToMagentoEntity(AmazonAddress $amazonAddress)
    {
        $address = $this->addressFactory->create();
        $address->setFirstname($amazonAddress->getFirstName());
        $address->setLastname($amazonAddress->getLastName());
        $address->setCity($amazonAddress->getCity());
        $address->setStreet(array_values($amazonAddress->getLines()));
        $address->setPostcode($amazonAddress->getPostCode());
        $address->setTelephone($amazonAddress->getTelephone());
        $address->setCountryId($this->getCountryId($amazonAddress));

        if ( ! empty($company = $amazonAddress->getCompany())) {
            $address->setCompany($company);
        }

        if ($amazonAddress->getState()) {
            $address->setRegion($this->getRegionData($amazonAddress, $address->getCountryId()));
        }

        return $address;
    }

    protected function getCountryId(AmazonAddress $amazonAddress)
    {
        return strtoupper($amazonAddress->getCountryCode());
    }

    protected function getRegionData(AmazonAddress $amazonAddress, $countryId)
    {
        $region     = $this->regionFactory->create();
        $regionData = $this->regionDataFactory->create();

        $region->loadByCode($amazonAddress->getState(), $countryId);

        if ( ! $region->getId()) {
            $region->loadByName($amazonAddress->getState(), $countryId);
        }

        if ($region->getId()) {
            $regionData
                ->setRegionId($region->getId())
                ->setRegionCode($region->getCode())
                ->setRegion($region->getDefaultName());
        } else {
            $regionData->setRegion($amazonAddress->getState());
        }

        return $regionData;
    }

    /**
     * Convert Magento address to array for json encode
     *
     * @param AddressInterface $address
     *
     * @return array
     */
    public function convertToArray(AddressInterface $address)
    {
        $data = [
            AddressInterface::CITY       => $address->getCity(),
            AddressInterface::FIRSTNAME  => $address->getFirstname(),
            AddressInterface::LASTNAME   => $address->getLastname(),
            AddressInterface::COUNTRY_ID => $address->getCountryId(),
            AddressInterface::STREET     => $address->getStreet(),
            AddressInterface::POSTCODE   => $address->getPostcode(),
            AddressInterface::TELEPHONE  => null,
            AddressInterface::REGION     => null,
            AddressInterface::REGION_ID  => null,
            'region_code'                => null
        ];

        if ($address->getTelephone()) {
            $data[AddressInterface::TELEPHONE] = $address->getTelephone();
        }

        if ($address->getRegion()) {
            $data[AddressInterface::REGION] = $address->getRegion()->getRegion();

            if ($address->getRegion()->getRegionId()) {
                $data[AddressInterface::REGION_ID] = $address->getRegion()->getRegionId();
                $data['region_code']               = $address->getRegion()->getRegionCode();
            }
        }

        return $data;
    }
}