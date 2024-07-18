<?php

namespace davidhirtz\yii2\location\google;

use davidhirtz\yii2\location\google\behaviors\LocationProviderIdBehavior;
use davidhirtz\yii2\location\google\components\Autocomplete;
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

        Event::on(Location::class, Location::EVENT_INIT, function (Event $event) {
            /** @var Location $location */
            $location = $event->sender;
            $location->attachBehavior('LocationProviderIdBehavior', LocationProviderIdBehavior::class);
        });

        Yii::$app->extendModule('admin', [
            'modules' => [
                'location' => [
                    'components' => [
                        'autocomplete' => Autocomplete::class,
                    ],
                ],
            ],
        ]);

        if (!Yii::$container->has(AutocompleteInputWidget::class)) {
            Yii::$container->set(AutocompleteInputWidget::class, [
                'label' => 'Google Places ID',
            ]);
        }
    }
}
