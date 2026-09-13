## 3.0.0 (in development)

- `Components\PlaceDetails::getAttributes()` iterates the address components only when the response carried
  them: a lookup that failed, and a place Google returns without them, both left the key unset

## 1.0.2 (Aug 1, 2024)

- Added `unique` validation rule to `provider_id`

## 1.0.1 (Jul 29, 2024)

- Automatically set Google Maps API key if `googleApiKey` is set in `config/params.php`