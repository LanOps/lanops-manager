<?php

namespace Tests\Support;

use Stripe\HttpClient\ClientInterface;

/**
 * Stands in for api.stripe.com in tests: the real Stripe library builds and
 * parses every request, this only answers them and remembers what was sent.
 */
class FakeStripeApi implements ClientInterface
{
    /** @var array<int, array{method: string, path: string, params: array}> */
    public array $requests = [];

    /** @var array<string, array{0: array, 1: int}> "METHOD /path" => [body, status] */
    private array $responses = [];

    public function respond(string $method, string $path, array $body, int $status = 200): static
    {
        $this->responses[strtoupper($method) . ' ' . $path] = [$body, $status];

        return $this;
    }

    public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null)
    {
        $path = parse_url($absUrl, PHP_URL_PATH);
        $this->requests[] = ['method' => strtoupper($method), 'path' => $path, 'params' => $params];
        $key = strtoupper($method) . ' ' . $path;
        if (!isset($this->responses[$key])) {
            throw new \LogicException('Unexpected Stripe request: ' . $key);
        }
        [$body, $status] = $this->responses[$key];

        return [json_encode($body), $status, ['request-id' => 'req_test']];
    }

    /**
     * The requests sent to one endpoint
     */
    public function sent(string $method, string $path): array
    {
        return array_values(array_filter(
            $this->requests,
            fn ($request) => $request['method'] === strtoupper($method) && $request['path'] === $path
        ));
    }
}
