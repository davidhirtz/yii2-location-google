<?php

declare(strict_types=1);

namespace Hirtz\Location\Google\Components;

use GuzzleHttp\RequestOptions;
use yii\base\BaseObject;
use yii\web\HttpException;

class PlaceDetails extends BaseObject
{
    /**
     * @see RequestOptions
     * @var array<string, mixed>
     */
    public array $options = [];
    public string $placeId;

    /** @var string[] */
    public array $fields = [
        'id',
        'formattedAddress',
        'addressComponents',
        'location',
        'displayName',
    ];

    protected ?string $error = null;

    /** @var array<string, mixed> */
    protected array $data = [];

    public function load(): bool
    {
        $params = $this->options;
        $params['query']['fields'] = implode(',', $this->fields);

        try {
            $this->data = GoogleMapsApi::create()->getPlace($this->placeId, $params);
        } catch (HttpException $e) {
            $this->error = $e->getMessage();
        }

        return $this->data !== [];
    }

    /**
     * @return array<string, mixed>
     */
    public function getAttributes(): array
    {
        $attributes = [];

        if (in_array('id', $this->fields)) {
            $attributes['provider_id'] = $this->data['id'] ?? null;
        }

        if (in_array('formattedAddress', $this->fields)) {
            $attributes['formatted_address'] = $this->data['formattedAddress'] ?? null;
        }

        if (in_array('location', $this->fields)) {
            $attributes['lat'] = $this->data['location']['latitude'] ?? null;
            $attributes['lng'] = $this->data['location']['longitude'] ?? null;
        }

        if (in_array('displayName', $this->fields)) {
            $attributes['name'] = $this->data['displayName']['text'] ?? null;
        }

        if (in_array('addressComponents', $this->fields)) {
            foreach ($this->data['addressComponents'] as $component) {
                $attribute = match ($component['types'][0] ?? null) {
                    'street_number' => 'house_number',
                    'route' => 'street',
                    'sublocality_level_1' => 'district',
                    'locality' => 'locality',
                    'postal_code' => 'postal_code',
                    'administrative_area_level_1' => 'state',
                    'country' => 'country_code',
                    default => null,
                };

                if ($attribute) {
                    $attributes[$attribute] = $component[$attribute == 'country_code' ? 'shortText' : 'longText'] ?? null;
                }
            }
        }

        return $attributes;
    }

    public function getError(): ?string
    {
        return $this->error;
    }
}
