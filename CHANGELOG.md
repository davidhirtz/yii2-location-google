## 3.0.0 (in development)

- **The `Google Places ID` label reaches the field** (monorepo issue #165). `Bootstrap` passed it as the third
  argument of `Yii::$container->set()`, which is the list of constructor *parameters* — `Container::build()`
  flattened the string-keyed entry into a second positional argument and `Widget::__construct(array $config = [])`
  dropped it without a word, so the field had always rendered the location bundle's own `Provider ID`. It goes
  through `Widget::EVENT_CONFIGURE` now, which is the documented way to extend a widget from the outside.

- **The bundle wires nothing without a `googleApiKey`** (monorepo issue #162). `Bootstrap` registered the
  `autocomplete` component and the `provider_id` behavior whether or not the parameter was set, so the field's
  endpoint reached `Components\GoogleMapsApi` with no key and answered `Typed property … $apiKey must not be
  accessed before initialization` — a 500 on the location form of every installation that has no key, a fresh
  checkout among them. It returns after the alias now, and `Location\Modules\Admin\Widgets\Forms\LocationProviderIdField`
  falls back to the plain input it inherits. `GoogleMapsApi::$apiKey` is `?string` and `init()` refuses a
  component built without one, naming the parameter.

- `Bootstrap::attachLocationProviderIdBehavior()` takes the `Models\Location` it attaches to, not the `Event`:
  the registration goes through `Skeleton\Helpers\EventHelper::on()`, which narrows the sender.

- `Components\Autocomplete` no longer HTML-encodes the suggestion text: the widget rendering it does, and the two
  together double-encoded every ampersand.

- `Components\PlaceDetails::getAttributes()` iterates the address components only when the response carried
  them: a lookup that failed, and a place Google returns without them, both left the key unset

## 1.0.2 (Aug 1, 2024)

- Added `unique` validation rule to `provider_id`

## 1.0.1 (Jul 29, 2024)

- Automatically set Google Maps API key if `googleApiKey` is set in `config/params.php`