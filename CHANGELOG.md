## 3.0.0 (in development)

- Renamed the namespace `davidhirtz\yii2\location\google\` to `Hirtz\Location\Google\` and the directories `behaviors` and `components` to `Behaviors` and `Components`; requires PHP 8.3 and `davidhirtz/yii2-location` 3.0
- Changed `Bootstrap` to wire nothing without `params.googleApiKey`: `Behaviors\LocationProviderIdBehavior`, the `autocomplete` component of the location admin module and the `Google Places ID` label are only registered when the key is set
- Changed `Components\GoogleMapsApi::$apiKey` to `?string`; `init()` throws an `InvalidConfigException` when it is empty
- Changed `Components\Autocomplete::getResults()` to key each suggestion by `text` instead of `label`, as `Hirtz\Location\Modules\Admin\Interfaces\AutocompleteInterface` declares
- Changed `Bootstrap::attachLocationProviderIdBehavior()` to take the `Hirtz\Location\Models\Location` instead of the `Event`
- Changed `Bootstrap::setAutocompleteInputLabel()` to set the label through `Widget::EVENT_CONFIGURE` on `Hirtz\Location\Modules\Admin\Widgets\Forms\LocationProviderIdField` instead of a container definition for `AutocompleteInputWidget`
- Changed `Components\PlaceDetails::getAttributes()` to skip the address components when the response carries none

## 1.0.2 (Aug 1, 2024)

- Added `unique` validation rule to `provider_id`

## 1.0.1 (Jul 29, 2024)

- Automatically set Google Maps API key if `googleApiKey` is set in `config/params.php`
