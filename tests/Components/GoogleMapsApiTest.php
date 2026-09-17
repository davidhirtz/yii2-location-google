<?php

declare(strict_types=1);

namespace Hirtz\Location\Google\Tests\Components;

use Hirtz\Location\Google\Components\GoogleMapsApi;
use Hirtz\Skeleton\Test\TestCase;
use yii\base\InvalidConfigException;

class GoogleMapsApiTest extends TestCase
{
    /**
     * A typed property read before initialization names the property rather than the parameter an installation
     * has to set, and it does so from inside the request the field made.
     */
    public function testAComponentWithoutAnApiKeyIsRefused(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('googleApiKey');

        new GoogleMapsApi();
    }
}
