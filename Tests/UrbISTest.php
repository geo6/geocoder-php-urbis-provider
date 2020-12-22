<?php

declare(strict_types=1);

/*
 * This file is part of the Geocoder package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Geocoder\Provider\UrbIS\Tests;

use Geocoder\IntegrationTest\BaseTestCase;
use Geocoder\Provider\UrbIS\UrbIS;
use Geocoder\Query\GeocodeQuery;
use Geocoder\Query\ReverseQuery;

class UrbISTest extends BaseTestCase
{
    protected function getCacheDir()
    {
        return __DIR__.'/.cached_responses';
    }

    public function testGeocodeWithLocalhostIPv4()
    {
        $this->expectException(\Geocoder\Exception\UnsupportedOperation::class);
        $this->expectExceptionMessage('The UrbIS provider does not support IP addresses, only street addresses.');

        $provider = new UrbIS($this->getMockedHttpClient());
        $provider->geocodeQuery(GeocodeQuery::create('127.0.0.1'));
    }

    public function testGeocodeWithLocalhostIPv6()
    {
        $this->expectException(\Geocoder\Exception\UnsupportedOperation::class);
        $this->expectExceptionMessage('The UrbIS provider does not support IP addresses, only street addresses.');

        $provider = new UrbIS($this->getMockedHttpClient());
        $provider->geocodeQuery(GeocodeQuery::create('::1'));
    }

    public function testGeocodeWithRealIPv6()
    {
        $this->expectException(\Geocoder\Exception\UnsupportedOperation::class);
        $this->expectExceptionMessage('The UrbIS provider does not support IP addresses, only street addresses.');

        $provider = new UrbIS($this->getMockedHttpClient());
        $provider->geocodeQuery(GeocodeQuery::create('::ffff:88.188.221.14'));
    }

    public function testReverseQuery()
    {
        $provider = new UrbIS($this->getHttpClient());
        $results = $provider->reverseQuery(ReverseQuery::fromCoordinates(50.841973, 4.362288)->withLocale('fr'));

        $this->assertInstanceOf('Geocoder\Model\AddressCollection', $results);
        $this->assertCount(1, $results);

        /** @var \Geocoder\Model\Address $result */
        $result = $results->first();
        $this->assertInstanceOf('\Geocoder\Model\Address', $result);
        $this->assertEquals('1', $result->getStreetNumber());
        $this->assertEquals('Place des Palais', $result->getStreetName());
        $this->assertEquals('1000', $result->getPostalCode());
        $this->assertEquals('Bruxelles', $result->getLocality());
    }

    public function testGeocodeQuery()
    {
        $provider = new UrbIS($this->getHttpClient());
        $results = $provider->geocodeQuery(GeocodeQuery::create('1 Place des Palais 1000 Bruxelles')->withLocale('fr'));

        $this->assertInstanceOf('Geocoder\Model\AddressCollection', $results);
        $this->assertCount(1, $results);

        /** @var \Geocoder\Model\Address $result */
        $result = $results->first();
        $this->assertInstanceOf('\Geocoder\Model\Address', $result);
        $this->assertEqualsWithDelta(50.841973, $result->getCoordinates()->getLatitude(), 0.00001);
        $this->assertEqualsWithDelta(4.362288, $result->getCoordinates()->getLongitude(), 0.00001);
        $this->assertEquals('1', $result->getStreetNumber());
        $this->assertEquals('Place des Palais', $result->getStreetName());
        $this->assertEquals('1000', $result->getPostalCode());
        $this->assertEquals('Bruxelles', $result->getLocality());
    }
}
