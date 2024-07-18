<?php

namespace davidhirtz\yii2\location\google\components;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;
use Ramsey\Uuid\Uuid;
use Yii;
use yii\base\BaseObject;
use yii\web\HttpException;

class GoogleMapsApi extends BaseObject
{
    final public const SESSION_TOKEN_KEY = 'google_maps_api_session_token';

    public string $apiKey;
    public ?string $languageCode = null;
    public ?HandlerStack $handlerStack = null;

    public array $supportedLanguageCodes = [
        'en-US' => 'en',
        'de' => 'de',
        'fr' => 'fr',
        'pt' => 'pt',
        'zh-CN' => 'zh-CN',
        'zh-TW' => 'zh-TW',
    ];

    protected ?string $sessionToken = null;

    public function init(): void
    {
        $this->languageCode ??= Yii::$app->language;
        $this->languageCode = $this->supportedLanguageCodes[$this->languageCode] ?? 'en';

        $this->handlerStack ??= HandlerStack::create();

        if (YII_DEBUG) {
            $this->handlerStack->push(Middleware::mapRequest(function (RequestInterface $request) {
                $message = "Requesting Google Places API endpoint '{$request->getUri()}'";

                if ($request->getMethod() === 'POST') {
                    $message .= "\n" . json_encode(json_decode($request->getBody()->getContents()), JSON_PRETTY_PRINT);
                }

                Yii::info($message, __METHOD__);

                return $request;
            }));
        }

        parent::init();
    }

    public function autocomplete(string $input, array $options = []): array
    {
        $options['json']['input'] = $input;
        $options['json']['languageCode'] ??= $this->languageCode;
        $options['json']['sessionToken'] = $this->getOrCreateSessionToken();

        return $this->request('POST', 'https://places.googleapis.com/v1/places:autocomplete', $options);
    }

    /**
     * @throws HttpException
     */
    public function getPlace(string $placeId, array $options = []): array
    {
        $options['query']['languageCode'] ??= $this->languageCode;
        $options['query']['sessionToken'] = $this->getSessionToken();

        $this->removeSessionToken();

        return $this->request('GET', "https://places.googleapis.com/v1/places/$placeId", $options);
    }

    protected function request(string $method, $uri = '', array $options = []): array
    {
        $this->prepareOptions($options);

        try {
            $response = $this->getClient()->request($method, $uri, $options);
            $contents = $response->getBody()->getContents();

            return json_decode($contents, true);
        } catch (ClientException $exception) {
            $contents = $exception->getResponse()->getBody()->getContents();
            $body = json_decode($contents, true);

            $code = $body['error']['code'] ?? $exception->getCode();
            $message = $body['error']['message'] ?? $exception->getMessage();

            throw new HttpException($code, $message);
        }
    }

    protected function prepareOptions(array &$options): void
    {
        $options['headers']['X-Goog-Api-Key'] = $this->apiKey;
    }

    protected function getSessionToken(): ?string
    {
        $this->sessionToken ??= Yii::$app->has('session')
            ? Yii::$app->getSession()->get(self::SESSION_TOKEN_KEY)
            : null;

        return $this->sessionToken;
    }

    protected function getOrCreateSessionToken(): ?string
    {
        $this->sessionToken = $this->getSessionToken();

        if (!$this->sessionToken) {
            if (!Yii::$app->has('session')) {
                return null;
            }

            Yii::debug('Generating new Google Maps API session token', __METHOD__);

            $this->sessionToken = Uuid::uuid4()->toString();
            Yii::$app->getSession()->set(self::SESSION_TOKEN_KEY, $this->sessionToken);
        }

        return $this->sessionToken;
    }

    protected function removeSessionToken(): void
    {
        $token = Yii::$app->getSession()->remove(self::SESSION_TOKEN_KEY);
        $this->sessionToken = null;

        if ($token) {
            Yii::debug('Cleared Google Maps API session token', __METHOD__);
        }
    }

    protected function getClient(): Client
    {
        return new Client(['handler' => $this->handlerStack]);
    }

    public static function create(): self
    {
        return Yii::$container->get(static::class);
    }
}