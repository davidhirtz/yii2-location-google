<?php

declare(strict_types=1);

namespace Hirtz\Location\Google;

use Hirtz\Location\Google\Behaviors\LocationProviderIdBehavior;
use Hirtz\Location\Google\Components\Autocomplete;
use Hirtz\Location\Google\Components\GoogleMapsApi;
use Hirtz\Location\Models\Location;
use Hirtz\Location\Modules\Admin\Widgets\Forms\LocationProviderIdField;
use Hirtz\Skeleton\Helpers\EventHelper;
use Hirtz\Skeleton\Web\Application;
use Yii;
use yii\base\BootstrapInterface;
use yii\db\BaseActiveRecord;

/**
 * Nothing here answers without an API key, so an installation that has none keeps the plain `provider_id` field
 * the location bundle renders on its own, rather than a form that 500s on the first keystroke.
 */
class Bootstrap implements BootstrapInterface
{
    /**
     * @param Application<\Hirtz\Skeleton\Models\User> $app
     */
    public function bootstrap($app): void
    {
        Yii::setAlias('@location-google', __DIR__);

        $googleApiKey = $app->params['googleApiKey'] ?? null;

        if (!is_string($googleApiKey) || !$googleApiKey) {
            return;
        }

        EventHelper::on(Location::class, BaseActiveRecord::EVENT_INIT, $this->attachLocationProviderIdBehavior(...));

        $this->setGoogleApiKey($googleApiKey);
        $this->setAutocompleteComponent();
        $this->setAutocompleteInputLabel();
    }

    protected function attachLocationProviderIdBehavior(Location $location): void
    {
        $location->attachBehavior('LocationProviderIdBehavior', LocationProviderIdBehavior::class);
    }

    protected function setAutocompleteComponent(): void
    {
        Yii::$app->extendModule('admin', [
            'modules' => [
                'location' => [
                    'components' => [
                        'autocomplete' => Autocomplete::class,
                    ],
                ],
            ],
        ]);
    }

    protected function setAutocompleteInputLabel(): void
    {
        $definition = Yii::$container->getDefinitions()[LocationProviderIdField::class] ?? [];

        Yii::$container->set(LocationProviderIdField::class, $definition, [
            'label' => 'Google Places ID',
        ]);
    }

    protected function setGoogleApiKey(string $googleApiKey): void
    {
        $definition = Yii::$container->getDefinitions()[GoogleMapsApi::class] ?? [];
        $definition['apiKey'] ??= $googleApiKey;

        Yii::$container->set(GoogleMapsApi::class, $definition);
    }
}
