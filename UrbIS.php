<?php

declare(strict_types=1);

/*
 * This file is part of the Geocoder package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Geocoder\Provider\UrbIS;

use Geocoder\Collection;
use Geocoder\Exception\InvalidArgument;
use Geocoder\Exception\InvalidServerResponse;
use Geocoder\Exception\UnsupportedOperation;
use Geocoder\Http\Provider\AbstractHttpProvider;
use Geocoder\Model\AddressBuilder;
use Geocoder\Model\AddressCollection;
use Geocoder\Provider\Provider;
use Geocoder\Query\GeocodeQuery;
use Geocoder\Query\ReverseQuery;
use Psr\Http\Client\ClientInterface;

/**
 * @author Jonathan Beliën <jbe@geo6.be>
 */
final class UrbIS extends AbstractHttpProvider implements Provider
{
    /**
     * @var string
     */
    const GEOCODE_ENDPOINT_URL = 'https://geoservices.irisnet.be/localization/Rest/Localize/getaddresses?spatialReference=4326&language=%s&address=%s';

    /**
     * @var string
     */
    const REVERSE_ENDPOINT_URL = 'https://geoservices.irisnet.be/localization/Rest/Localize/getaddressfromxy?json=%s';

    /**
     * @param ClientInterface $client an HTTP adapter
     */
    public function __construct(ClientInterface $client)
    {
        parent::__construct($client);
    }

    /**
     * {@inheritdoc}
     */
    public function geocodeQuery(GeocodeQuery $query): Collection
    {
        $address = $query->getText();
        // This API does not support IP
        if (filter_var($address, FILTER_VALIDATE_IP)) {
            throw new UnsupportedOperation('The UrbIS provider does not support IP addresses, only street addresses.');
        }

        // Save a request if no valid address entered
        if (empty($address)) {
            throw new InvalidArgument('Address cannot be empty.');
        }

        $language = '';
        if (!is_null($query->getLocale()) && preg_match('/^(fr|nl).*$/', $query->getLocale(), $matches) === 1) {
            $language = $matches[1];
        }

        $url = sprintf(self::GEOCODE_ENDPOINT_URL, urlencode($language), urlencode($address));
        $json = $this->executeQuery($url);

        // no result
        if (empty($json->result)) {
            return new AddressCollection([]);
        }

        $results = [];
        foreach ($json->result as $location) {
            $streetName = !empty($location->address->street->name) ? $location->address->street->name : null;
            $number = !empty($location->address->number) ? $location->address->number : null;
            $municipality = !empty($location->address->street->municipality) ? $location->address->street->municipality : null;
            $postCode = !empty($location->address->street->postCode) ? $location->address->street->postCode : null;

            $builder = new AddressBuilder($this->getName());
            $builder->setCoordinates($location->point->y, $location->point->x)
                ->setStreetNumber($number)
                ->setStreetName($streetName)
                ->setLocality($municipality)
                ->setPostalCode($postCode)
                ->setBounds($location->extent->ymin, $location->extent->xmin, $location->extent->ymax, $location->extent->xmax);

            $results[] = $builder->build();
        }

        return new AddressCollection($results);
    }

    /**
     * {@inheritdoc}
     */
    public function reverseQuery(ReverseQuery $query): Collection
    {
        $coordinates = $query->getCoordinates();
        $language = $query->getLocale() ?? '';

        $jsonQuery = [
            'language' => $language,
            'point'    => [
                // x, y are switched in the API
                'y' => $coordinates->getLongitude(),
                'x' => $coordinates->getLatitude(),
            ],
            'SRS_In' => 4326,
        ];

        $url = sprintf(self::REVERSE_ENDPOINT_URL, urlencode(json_encode($jsonQuery)));
        $json = $this->executeQuery($url);

        // no result
        if (empty($json->result)) {
            return new AddressCollection([]);
        }

        $results = [];
        $location = $json->result;

        $streetName = !empty($location->address->street->name) ? $location->address->street->name : null;
        $number = !empty($location->address->number) ? $location->address->number : null;
        $municipality = !empty($location->address->street->municipality) ? $location->address->street->municipality : null;
        $postCode = !empty($location->address->street->postCode) ? $location->address->street->postCode : null;

        $builder = new AddressBuilder($this->getName());
        $builder->setCoordinates($location->point->y, $location->point->x)
            ->setStreetNumber($number)
            ->setStreetName($streetName)
            ->setLocality($municipality)
            ->setPostalCode($postCode);

        $results[] = $builder->build();

        return new AddressCollection($results);
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'urbis';
    }

    /**
     * @param string $url
     *
     * @return \stdClass
     */
    private function executeQuery(string $url): \stdClass
    {
        $content = $this->getUrlContents($url);
        $json = json_decode($content);
        // API error
        if (!isset($json)) {
            throw InvalidServerResponse::create($url);
        }

        return $json;
    }
}
