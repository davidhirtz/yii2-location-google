# yii2-location-google

Google Places provider for the [Yii 2](https://www.yiiframework.com/) extension
[yii2-location](https://github.com/davidhirtz/yii2-location): the admin's location form searches the
[Places API (New)](https://console.cloud.google.com/marketplace/product/google/places.googleapis.com) for a place
and fills the location's address, name and coordinates from the place a user picks. Requires
`davidhirtz/yii2-location` and `davidhirtz/yii2-skeleton`, plus Guzzle and `ramsey/uuid`.

## Installation

```bash
composer require davidhirtz/yii2-location-google
```

The bundle bootstraps itself through `extra.bootstrap` and ships no migrations, no module and no message files.
Enable the Places API (New) in the Google Cloud Console, create an API key for it and set `googleApiKey` in
`config/params.php`; then nothing else has to run.

## Configuration

### `params.googleApiKey`

**The bundle does nothing without it.** `Bootstrap` registers the `@location-google` alias and returns when
`params.googleApiKey` is missing or empty, so the location form keeps the plain `provider_id` input of
`yii2-location`. With a key set it wires three things:

- `Behaviors\LocationProviderIdBehavior` on `Hirtz\Location\Models\Location`. Before validation, a changed
  `provider_id` is checked for uniqueness, then loaded through `Components\PlaceDetails`, and the place's
  attributes are written onto the record. A place Google cannot find is an error on `provider_id`.
- `Components\Autocomplete` as the `autocomplete` component of the location admin module
  (`modules.admin.modules.location.components.autocomplete`). `yii2-location`'s
  `LocationProviderIdField` turns into a search input answered by its own `admin/location/location/autocomplete`
  route, which asks the component for suggestions.
- The label `Google Places ID` on `Hirtz\Location\Modules\Admin\Widgets\Forms\LocationProviderIdField`, set
  through `Hirtz\Skeleton\Widgets\Widget::EVENT_CONFIGURE`.

```php
// config/params.php
return [
    'googleApiKey' => '...',
];
```

### `Components\GoogleMapsApi`

The API client is built through the container (`GoogleMapsApi::create()`), and `Bootstrap` merges `apiKey`
into whatever definition a project registered, without overwriting one it names itself.

| Property                 | Default                        | Meaning                                                                                          |
|--------------------------|--------------------------------|--------------------------------------------------------------------------------------------------|
| `apiKey`                 | `params.googleApiKey`          | Sent as `X-Goog-Api-Key`; `init()` throws an `InvalidConfigException` without one                 |
| `languageCode`           | `Yii::$app->language`          | Language of the suggestions and place details, mapped through `supportedLanguageCodes`             |
| `supportedLanguageCodes` | `en-US`, `de`, `fr`, `pt`, `zh-CN`, `zh-TW` | Map of application language to Places API language code; an unmapped language falls back to `en` |
| `handlerStack`           | `HandlerStack::create()`       | Guzzle handler stack; in debug mode every request is logged through `Yii::info()`                 |

```php
// config/web.php
'container' => [
    'definitions' => [
        \Hirtz\Location\Google\Components\GoogleMapsApi::class => [
            'supportedLanguageCodes' => ['de' => 'de', 'en-US' => 'en', 'it' => 'it'],
        ],
    ],
],
```

An autocomplete request opens a Places session token, kept in the web session under
`GoogleMapsApi::SESSION_TOKEN_KEY`, and the place details request that follows closes it, so the two are billed
as one session. Under the console application there is no session and no token.

### `Components\Autocomplete`

`Autocomplete::$options` are Guzzle request options merged into every autocomplete call; the request body is
the `json` key, so a project restricts the suggestions there:

```php
'modules' => [
    'admin' => [
        'modules' => [
            'location' => [
                'components' => [
                    'autocomplete' => [
                        'class' => \Hirtz\Location\Google\Components\Autocomplete::class,
                        'options' => ['json' => ['includedRegionCodes' => ['de', 'at', 'ch']]],
                    ],
                ],
            ],
        ],
    ],
],
```

### `Components\PlaceDetails`

`PlaceDetails::$fields` names the Places API fields requested for a picked place and decides which location
attributes are written. The default is `id`, `formattedAddress`, `addressComponents`, `location` and
`displayName`; `PlaceDetails::$options` are Guzzle request options for that call. A field left out of the list is
neither requested nor written, so a project that keeps the name a user typed drops `displayName`:

```php
'container' => [
    'definitions' => [
        \Hirtz\Location\Google\Components\PlaceDetails::class => [
            'fields' => ['id', 'formattedAddress', 'addressComponents', 'location'],
        ],
    ],
],
```

| Places API field                          | Location attribute  |
|-------------------------------------------|---------------------|
| `id`                                      | `provider_id`       |
| `formattedAddress`                        | `formatted_address` |
| `location.latitude` / `location.longitude` | `lat` / `lng`      |
| `displayName.text`                        | `name`              |
| `addressComponents` `street_number`       | `house_number`      |
| `addressComponents` `route`               | `street`            |
| `addressComponents` `sublocality_level_1` | `district`          |
| `addressComponents` `locality`            | `locality`          |
| `addressComponents` `postal_code`         | `postal_code`       |
| `addressComponents` `administrative_area_level_1` | `state`     |
| `addressComponents` `country` (short text) | `country_code`     |

A component of any other type is ignored.
