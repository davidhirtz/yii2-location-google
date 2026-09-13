<?php

declare(strict_types=1);

namespace Hirtz\Location\Google\Tests\Components;

use Hirtz\Location\Google\Components\PlaceDetails;
use Hirtz\Skeleton\Test\TestCase;

/**
 * The place response is what an administrator's picked address becomes, so every component has to land on the
 * attribute the location stores it in.
 */
class PlaceDetailsTest extends TestCase
{
    public function testAPlaceBecomesTheLocationAttributes(): void
    {
        $attributes = (new TestPlaceDetails(['placeId' => 'abc']))
            ->withData([
                'id' => 'ChIJ123',
                'formattedAddress' => '1 Infinite Loop, Cupertino, CA 95014, USA',
                'location' => ['latitude' => 37.3318, 'longitude' => -122.0312],
                'displayName' => ['text' => 'Apple Park'],
                'addressComponents' => [
                    ['types' => ['street_number'], 'longText' => '1', 'shortText' => '1'],
                    ['types' => ['route'], 'longText' => 'Infinite Loop', 'shortText' => 'Infinite Loop'],
                    ['types' => ['locality'], 'longText' => 'Cupertino', 'shortText' => 'Cupertino'],
                    ['types' => ['postal_code'], 'longText' => '95014', 'shortText' => '95014'],
                    ['types' => ['administrative_area_level_1'], 'longText' => 'California', 'shortText' => 'CA'],
                    ['types' => ['country'], 'longText' => 'United States', 'shortText' => 'US'],
                ],
            ])
            ->getAttributes();

        self::assertSame('ChIJ123', $attributes['provider_id']);
        self::assertSame('Apple Park', $attributes['name']);
        self::assertSame(37.3318, $attributes['lat']);
        self::assertSame(-122.0312, $attributes['lng']);

        self::assertSame('1', $attributes['house_number']);
        self::assertSame('Infinite Loop', $attributes['street']);
        self::assertSame('Cupertino', $attributes['locality']);
        self::assertSame('95014', $attributes['postal_code']);

        // The state is stored by its long name, the country by its two-letter code.
        self::assertSame('California', $attributes['state']);
        self::assertSame('US', $attributes['country_code']);
    }

    /**
     * A lookup that failed leaves no data at all, and `getAttributes()` is called either way.
     */
    public function testAPlaceWithoutAddressComponents(): void
    {
        $attributes = (new TestPlaceDetails(['placeId' => 'abc']))->getAttributes();

        self::assertNull($attributes['provider_id']);
        self::assertNull($attributes['lat']);
        self::assertArrayNotHasKey('country_code', $attributes);
    }

    /**
     * A field that was not asked for is not reported as an empty attribute, which would overwrite a stored one.
     */
    public function testOnlyTheFieldsThatWereAskedForAreReturned(): void
    {
        $attributes = (new TestPlaceDetails(['placeId' => 'abc', 'fields' => ['location']]))
            ->withData(['location' => ['latitude' => 1.0, 'longitude' => 2.0]])
            ->getAttributes();

        self::assertSame(['lat' => 1.0, 'lng' => 2.0], $attributes);
    }

    /**
     * Google names a component by its first type, and an unknown one belongs to no attribute.
     */
    public function testAComponentOfAnUnknownTypeIsIgnored(): void
    {
        $attributes = (new TestPlaceDetails(['placeId' => 'abc', 'fields' => ['addressComponents']]))
            ->withData([
                'addressComponents' => [
                    ['types' => ['plus_code'], 'longText' => '849VCWC8+R9', 'shortText' => 'CWC8+R9'],
                    ['types' => [], 'longText' => 'Nothing'],
                ],
            ])
            ->getAttributes();

        self::assertSame([], $attributes);
    }
}

class TestPlaceDetails extends PlaceDetails
{
    /**
     * @param array<string, mixed> $data
     */
    public function withData(array $data): static
    {
        $this->data = $data;
        return $this;
    }
}
