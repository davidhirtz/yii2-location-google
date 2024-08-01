<?php

namespace davidhirtz\yii2\location\google\behaviors;

use davidhirtz\yii2\location\google\components\PlaceDetails;
use davidhirtz\yii2\location\models\Location;
use Yii;
use yii\behaviors\AttributeBehavior;

/**
 * @property Location $owner
 */
class LocationProviderIdBehavior extends AttributeBehavior
{
    public function events(): array
    {
        return [
            Location::EVENT_BEFORE_VALIDATE => $this->onBeforeValidate(...),
        ];
    }

    public function onBeforeValidate(): void
    {
        if ($this->owner->isAttributeChanged('provider_id')) {
            if ($this->owner->provider_id) {
                $exists = Location::find()
                    ->andWhere(['provider_id' => $this->owner->provider_id])
                    ->andFilterWhere(['<>', 'id', $this->owner->id])
                    ->exists();

                if($exists) {
                    $this->owner->addError('provider_id', Yii::t('yii', '{attribute} "{value}" has already been taken.', [
                        'attribute' => $this->owner->getAttributeLabel('provider_id'),
                        'value' => $this->owner->provider_id,
                    ]));

                    return;
                }

                $place = Yii::$container->get(PlaceDetails::class, [], [
                    'placeId' => $this->owner->provider_id,
                ]);

                if ($place->load()) {
                    $this->owner->setAttributes($place->getAttributes(), false);
                    return;
                }

                if ($error = $place->getError()) {
                    $this->owner->addError('provider_id', $error);
                    return;
                }

                $this->owner->addInvalidAttributeError('provider_id');
            }
        }
    }
}
