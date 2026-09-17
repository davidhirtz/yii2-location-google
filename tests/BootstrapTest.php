<?php

declare(strict_types=1);

namespace Hirtz\Location\Google\Tests;

use Hirtz\Location\Google\Components\Autocomplete;
use Hirtz\Location\Google\Components\GoogleMapsApi;
use Hirtz\Location\Modules\Admin\Module;
use Hirtz\Location\Modules\Admin\Widgets\Forms\LocationProviderIdField;
use Hirtz\Location\Models\Location;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Hirtz\Skeleton\Widgets\Forms\ActiveForm;
use Yii;

/**
 * The API key is the whole of this bundle's configuration: without one every request it could make is a 500, so it
 * wires nothing at all and the location bundle keeps the plain `provider_id` field it renders on its own.
 */
class BootstrapTest extends TestCase
{
    use UserFixtureTrait;

    public function testNothingIsWiredWithoutAnApiKey(): void
    {
        self::assertNull($this->getLocationModule()->getAutocomplete());
        self::assertFalse(Location::create()->hasMethod('onBeforeValidate'));

        $html = $this->renderProviderIdField();

        self::assertStringNotContainsString('hx-get', $html);
        self::assertStringNotContainsString('Google Places ID', $html);
    }

    public function testAnApiKeyWiresTheAutocompleteAndTheBehavior(): void
    {
        $this->reloadApplicationWithApiKey('test-api-key');

        self::assertInstanceOf(Autocomplete::class, $this->getLocationModule()->getAutocomplete());
        self::assertTrue(Location::create()->hasMethod('onBeforeValidate'));
        self::assertSame('test-api-key', GoogleMapsApi::create()->apiKey);

        $html = $this->renderProviderIdField();

        self::assertStringContainsString('hx-get="/admin/location/location/autocomplete"', $html);

        // The field names the provider, which is the Google bundle's to say and only once one is wired.
        self::assertStringContainsString('Google Places ID', $html);
    }

    /**
     * The reproduction of monorepo issue #162: with the component registered but no key behind it, the request the
     * field makes on every keystroke was a 500 naming an uninitialized property.
     */
    public function testTheAutocompleteEndpointIsNotReachableWithoutAnApiKey(): void
    {
        $user = $this->getUserFromFixture('admin');
        $this->assignPermission($user->id, Location::AUTH_LOCATION);
        $this->getWebUser()->setIdentity($user);

        $html = Yii::$app->runAction('admin/location/location/autocomplete', ['q' => 'Zurich']);

        self::assertIsString($html);
        self::assertStringNotContainsString('data-autocomplete-value', $html);
    }

    private function renderProviderIdField(): string
    {
        $location = Location::create();
        $location->loadDefaultValues();

        return (string)LocationProviderIdField::make()
            ->form(ActiveForm::make()->model($location));
    }

    private function reloadApplicationWithApiKey(string $apiKey): void
    {
        $this->config['params']['googleApiKey'] = $apiKey;
        $this->reloadApplication();
    }

    private function getLocationModule(): Module
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('admin')->getModule('location');
        return $module;
    }
}
