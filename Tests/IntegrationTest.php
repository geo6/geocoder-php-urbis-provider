<?php

/*
 * This file is part of the Geocoder package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Geocoder\Provider\UrbIS\Tests;

use Geocoder\IntegrationTest\ProviderIntegrationTest;
use Geocoder\Provider\UrbIS\UrbIS;
use Psr\Http\Client\ClientInterface;

class IntegrationTest extends ProviderIntegrationTest
{
    protected $testAddress = true;

    protected $testReverse = true;

    protected $testIpv4 = false;

    protected $testIpv6 = false;

    protected $skippedTests = [
        'testGeocodeQuery'              => 'UrbIS provider supports Brussels (Belgium) only.',
        'testReverseQuery'              => 'UrbIS provider supports Brussels (Belgium) only.',
        'testGeocodeQueryWithNoResults' => 'UrbIS provider returns "wrong" results!',
        'testReverseQueryWithNoResults' => 'UrbIS provider returns "wrong" results!',
    ];

    protected function createProvider(ClientInterface $httpClient)
    {
        return new UrbIS($httpClient);
    }

    protected function getCacheDir()
    {
        return __DIR__.'/.cached_responses';
    }

    protected function getApiKey()
    {
    }
}
