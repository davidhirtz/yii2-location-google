<?php

namespace davidhirtz\yii2\location\google;

use davidhirtz\yii2\location\google\behaviors\LocationProviderIdBehavior;
use davidhirtz\yii2\location\google\components\Autocomplete;
use davidhirtz\yii2\location\google\components\GoogleMapsApi;
use davidhirtz\yii2\location\models\Location;
use davidhirtz\yii2\location\modules\admin\widgets\forms\AutocompleteInputWidget;
use davidhirtz\yii2\skeleton\web\Application;
use Yii;
use yii\base\BootstrapInterface;
use yii\base\Event;

class Bootstrap implements BootstrapInterface
{
    /**
     * @param Application $app
     */
    public function bootstrap($app): void
    {
        Yii::setAlias('@location-google', __DIR__);

        Event::on(Location::class, Location::EVENT_INIT, $this->attachLocationProviderIdBehavior(...));

        $googleApiKey = $app->params['googleApiKey'] ?? null;

        if ($googleApiKey) {
            $this->setGoogleApiKey($googleApiKey);
        }

        $this->setAutocompleteComponent();
        $this->setAutocompleteInputLabel();
    }

    protected function attachLocationProviderIdBehavior(Event $event): void
    {
        /** @var Location $location */
        $location = $event->sender;
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
        $definition = Yii::$container->getDefinitions()[AutocompleteInputWidget::class] ?? [];
        $definition['label'] ??= 'Google Places ID';

        Yii::$container->set(AutocompleteInputWidget::class, $definition);
    }

    protected function setGoogleApiKey(string $googleApiKey): void
    {
        $definition = Yii::$container->getDefinitions()[GoogleMapsApi::class] ?? [];
        $definition['apiKey'] ??= $googleApiKey;

        Yii::$container->set(GoogleMapsApi::class, $definition);
    }
}
