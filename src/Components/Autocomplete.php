<?php

declare(strict_types=1);

namespace Hirtz\Location\Google\Components;

use Hirtz\Location\Modules\Admin\Interfaces\AutocompleteInterface;
use GuzzleHttp\RequestOptions;
use Hirtz\Media\Helpers\Html;
use yii\base\BaseObject;

class Autocomplete extends BaseObject implements AutocompleteInterface
{
    /**
     * @see RequestOptions
     * @var array<string, mixed>
     */
    public array $options = [];

    /**
     * @return list<array{text: string, value: mixed}>
     */
    public function getResults(string $input): array
    {
        $data = GoogleMapsApi::create()->autocomplete($input, $this->options);
        return $this->getFormattedApiResponse($data);
    }

    /**
     * @param array<string, mixed> $data
     * @return list<array{text: string, value: mixed}>
     */
    protected function getFormattedApiResponse(array $data): array
    {
        $suggestions = $data['suggestions'] ?? [];
        $results = [];

        foreach ($suggestions as $suggestion) {
            $results[] = [
                'text' => Html::encode($suggestion['placePrediction']['text']['text']),
                'value' => $suggestion['placePrediction']['placeId'],
            ];
        }

        return $results;
    }
}
