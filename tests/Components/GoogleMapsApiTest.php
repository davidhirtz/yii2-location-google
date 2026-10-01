<?php

declare(strict_types=1);

namespace Hirtz\Location\Google\Tests\Components;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Hirtz\Location\Google\Components\GoogleMapsApi;
use Hirtz\Skeleton\Test\TestCase;
use yii\base\InvalidConfigException;
use yii\web\HttpException;

class GoogleMapsApiTest extends TestCase
{
    /**
     * A typed property read before initialization names the property rather than the parameter an installation
     * has to set, and it does so from inside the request the field made.
     */
    public function testAComponentWithoutAnApiKeyIsRefused(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('googleApiKey');

        new GoogleMapsApi();
    }

    public function testTheDataOfASuccessfulRequest(): void
    {
        $api = $this->createApi(new Response(200, [], '{"suggestions":[]}'));
        self::assertSame(['suggestions' => []], $api->autocomplete('Berlin'));
    }

    public function testAConnectionFailureIsABadGateway(): void
    {
        $api = $this->createApi(new ConnectException('Could not resolve host', new Request('POST', 'places')));

        try {
            $api->autocomplete('Berlin');
            self::fail('A connection failure must throw.');
        } catch (HttpException $exception) {
            self::assertSame(502, $exception->statusCode);
        }
    }

    public function testAServerErrorIsABadGateway(): void
    {
        $this->expectException(HttpException::class);
        $this->createApi(new Response(503))->autocomplete('Berlin');
    }

    public function testAnAnswerWithoutJsonIsABadGateway(): void
    {
        $this->expectException(HttpException::class);
        $this->createApi(new Response(200, [], '<html></html>'))->autocomplete('Berlin');
    }

    private function createApi(Response|ConnectException $response): GoogleMapsApi
    {
        return new GoogleMapsApi([
            'apiKey' => 'api-key',
            'handlerStack' => HandlerStack::create(new MockHandler([$response])),
        ]);
    }
}
