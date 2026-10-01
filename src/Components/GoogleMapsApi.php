<?php

declare(strict_types=1);

namespace Hirtz\Location\Google\Components;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Override;
use Psr\Http\Message\RequestInterface;
use Ramsey\Uuid\Uuid;
use Yii;
use yii\base\BaseObject;
use yii\base\InvalidConfigException;
use yii\web\HttpException;
use yii\web\Session;

class GoogleMapsApi extends BaseObject
{
    final public const string SESSION_TOKEN_KEY = 'google_maps_api_session_token';

    public ?string $apiKey = null;
    public ?string $languageCode = null;
    public ?HandlerStack $handlerStack = null;
    public float $timeout = 10;

    /** @var array<string, string> */
    public array $supportedLanguageCodes = [
        'en-US' => 'en',
        'de' => 'de',
        'fr' => 'fr',
        'pt' => 'pt',
        'zh-CN' => 'zh-CN',
        'zh-TW' => 'zh-TW',
    ];

    protected ?string $sessionToken = null;

    #[Override]
    public function init(): void
    {
        if (!$this->apiKey) {
            throw new InvalidConfigException('The Google Maps API needs an API key. Set `googleApiKey` in `config/params.php`.');
        }

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

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function autocomplete(string $input, array $options = []): array
    {
        $options['json']['input'] = $input;
        $options['json']['languageCode'] ??= $this->languageCode;
        $options['json']['sessionToken'] = $this->getOrCreateSessionToken();

        return $this->request('POST', 'https://places.googleapis.com/v1/places:autocomplete', $options);
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     * @throws HttpException
     */
    public function getPlace(string $placeId, array $options = []): array
    {
        $options['query']['languageCode'] ??= $this->languageCode;
        $options['query']['sessionToken'] = $this->getSessionToken();

        $this->removeSessionToken();

        return $this->request('GET', "https://places.googleapis.com/v1/places/$placeId", $options);
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    protected function request(string $method, string $uri = '', array $options = []): array
    {
        $this->prepareOptions($options);

        try {
            $response = $this->getClient()->request($method, $uri, $options);
            $data = json_decode($response->getBody()->getContents(), true);
        } catch (ClientException $exception) {
            $contents = $exception->getResponse()->getBody()->getContents();
            $body = json_decode($contents, true);

            $code = $body['error']['code'] ?? $exception->getCode();
            $message = $body['error']['message'] ?? $exception->getMessage();

            throw new HttpException($code, $message);
        } catch (GuzzleException $exception) {
            Yii::error($exception->getMessage(), __METHOD__);
            throw new HttpException(502, $exception->getMessage());
        }

        if (!is_array($data)) {
            throw new HttpException(502, 'The Google Maps API answered without JSON.');
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $options
     */
    protected function prepareOptions(array &$options): void
    {
        $options['headers']['X-Goog-Api-Key'] = $this->apiKey;
    }

    private function getWebSession(): ?Session
    {
        $session = Yii::$app->has('session') ? Yii::$app->get('session') : null;
        return $session instanceof Session ? $session : null;
    }

    protected function getSessionToken(): ?string
    {
        $this->sessionToken ??= $this->getWebSession()?->get(self::SESSION_TOKEN_KEY);
        return $this->sessionToken;
    }

    protected function getOrCreateSessionToken(): ?string
    {
        $this->sessionToken = $this->getSessionToken();

        if (!$this->sessionToken) {
            $session = $this->getWebSession();

            if (!$session) {
                return null;
            }

            Yii::debug('Generating new Google Maps API session token', __METHOD__);

            $this->sessionToken = Uuid::uuid4()->toString();
            $session->set(self::SESSION_TOKEN_KEY, $this->sessionToken);
        }

        return $this->sessionToken;
    }

    protected function removeSessionToken(): void
    {
        $token = $this->getWebSession()?->remove(self::SESSION_TOKEN_KEY);
        $this->sessionToken = null;

        if ($token) {
            Yii::debug('Cleared Google Maps API session token', __METHOD__);
        }
    }

    protected function getClient(): Client
    {
        return new Client([
            'handler' => $this->handlerStack,
            'connect_timeout' => $this->timeout,
            'timeout' => $this->timeout,
        ]);
    }

    public static function create(): self
    {
        return Yii::$container->get(static::class);
    }
}
