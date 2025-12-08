<?php

namespace Hirtz\Location\google\components;

use Hirtz\Location\Modules\Admin\Interfaces\AutocompleteInterface;
use GuzzleHttp\RequestOptions;
use yii\base\BaseObject;

class Autocomplete extends BaseObject implements AutocompleteInterface
{
    /**
     * @see RequestOptions
     */
    public array $options = [];

    public function getResults(string $input): array
    {
        $data = GoogleMapsApi::create()->autocomplete($input, $this->options);
        return $this->getFormattedApiResponse($data);
    }

    protected function getFormattedApiResponse(array $data): array
    {
        $suggestions = $data['suggestions'] ?? [];
        $results = [];

        foreach ($suggestions as $suggestion) {
            $results[] = [
                'label' => $suggestion['placePrediction']['text']['text'],
                'value' => $suggestion['placePrediction']['placeId'],
            ];
        }

        return $results;
    }
}
