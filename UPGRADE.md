# Upgrading to 3.0

The bundle has no module, no migrations and no message files. The upgrade is the namespace rename, one
configuration rule and three small API changes.

## Requirements

- PHP `^8.3`
- `davidhirtz/yii2-location` `^3.0`, which brings `davidhirtz/yii2-skeleton` `^3.0` and Guzzle
- `ramsey/uuid` `^4.7` (unchanged)

The bundle bootstraps itself through `extra.bootstrap`; nothing has to be listed in `config/*.php`.

## Renames

### Namespaces and directories

| 1.x                                            | 3.0                                   |
|------------------------------------------------|---------------------------------------|
| `davidhirtz\yii2\location\google\`             | `Hirtz\Location\Google\`              |
| `davidhirtz\yii2\location\google\behaviors\`   | `Hirtz\Location\Google\Behaviors\`    |
| `davidhirtz\yii2\location\google\components\`  | `Hirtz\Location\Google\Components\`   |

The class names (`Bootstrap`, `LocationProviderIdBehavior`, `Autocomplete`, `GoogleMapsApi`, `PlaceDetails`) are
unchanged.

### Classes of the location bundle this bundle keys on

| 1.x                                                                        | 3.0                                                              |
|----------------------------------------------------------------------------|------------------------------------------------------------------|
| `davidhirtz\yii2\location\modules\admin\widgets\forms\AutocompleteInputWidget` | `Hirtz\Location\Modules\Admin\Widgets\Forms\LocationProviderIdField` |
| `davidhirtz\yii2\location\modules\admin\interfaces\AutocompleteInterface`  | `Hirtz\Location\Modules\Admin\Interfaces\AutocompleteInterface`  |

### Methods and return shapes

| 1.x                                                        | 3.0                                                              |
|------------------------------------------------------------|------------------------------------------------------------------|
| `Bootstrap::attachLocationProviderIdBehavior(Event $event)` | `Bootstrap::attachLocationProviderIdBehavior(Location $location)` |
| `Autocomplete::getResults()` item key `label`              | `text`                                                           |
| `GoogleMapsApi::$apiKey` (`string`)                         | `?string`, `init()` throws without one                          |

## Configuration

**`params.googleApiKey` is now the switch for the whole bundle.** In 1.x the `LocationProviderIdBehavior` and the
location admin module's `autocomplete` component were registered whether or not a key was configured, and the
first keystroke in the field failed against an API client without a key. In 3.0 `Bootstrap` returns after
registering the `@location-google` alias when the parameter is missing or empty, and the location form keeps
the plain `provider_id` input of `yii2-location`.

```php
// config/params.php
return [
    'googleApiKey' => '...',
];
```

Nothing else changes: `Components\GoogleMapsApi` is still configured through the container, and a definition a
project set (`languageCode`, `supportedLanguageCodes`, `handlerStack`) is kept, with `apiKey` filled in from the
parameter only when the definition does not name it.

### The field label

The `Google Places ID` label was a container definition for `AutocompleteInputWidget`. It is now set through
`Hirtz\Skeleton\Widgets\Widget::EVENT_CONFIGURE` on `LocationProviderIdField`. A project that overrode the
label through the container registers a listener of its own, which runs after the bundle's:

```php
// 1.x
Yii::$container->set(AutocompleteInputWidget::class, ['label' => 'Place']);

// 3.0
use Hirtz\Location\Modules\Admin\Widgets\Forms\LocationProviderIdField;
use Hirtz\Skeleton\Helpers\EventHelper;
use Hirtz\Skeleton\Widgets\Widget;

EventHelper::on(LocationProviderIdField::class, Widget::EVENT_CONFIGURE, fn (LocationProviderIdField $field) => $field->label('Place'));
```

## Code changes

### `Autocomplete` results are keyed by `text`

`Hirtz\Location\Modules\Admin\Interfaces\AutocompleteInterface::getResults()` returns
`list<array{text: string, value: mixed}>`. A subclass overriding `Components\Autocomplete::getFormattedApiResponse()`,
or anything reading the results, renames the key:

```php
// 1.x
$results[] = ['label' => $text, 'value' => $placeId];

// 3.0
$results[] = ['text' => $text, 'value' => $placeId];
```

### `Bootstrap::attachLocationProviderIdBehavior()` takes the record

The handler is registered through `Hirtz\Skeleton\Helpers\EventHelper::on()`, which hands it the narrowed
sender. A subclass of `Bootstrap` overriding it changes the signature:

```php
// 1.x
protected function attachLocationProviderIdBehavior(Event $event): void
{
    $event->sender->attachBehavior(...);
}

// 3.0
protected function attachLocationProviderIdBehavior(Location $location): void
{
    $location->attachBehavior(...);
}
```

### `GoogleMapsApi` refuses to start without a key

`Components\GoogleMapsApi::$apiKey` is `?string` and `init()` throws an `InvalidConfigException` naming
`googleApiKey` when it is empty. Code building the client by hand (`GoogleMapsApi::create()` or
`Yii::createObject()`) outside the bootstrap's guard now fails at construction instead of on the first request.

### `PlaceDetails::getAttributes()` tolerates a response without address components

A place Google returns without `addressComponents` yields the other attributes and no address fields; in 1.x the
same response raised a warning. No call site changes.

## Data and schema

The bundle ships no migrations and `davidhirtz/yii2-upgrade` carries none for it. The `location.provider_id`
column belongs to `yii2-location`; see that bundle's `UPGRADE.md`.
