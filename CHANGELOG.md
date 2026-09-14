## 3.0.0 (in development)

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