<?php

declare(strict_types=1);

namespace App\Infrastructure\Janedbal;

use App\Domain\ApiConnector;
use App\Domain\Packaging;
use App\Domain\Packagings;
use App\Domain\Products;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use JsonException;
use RuntimeException;

final readonly class JanedbalApiConnector implements ApiConnector
{
    public function __construct(
        private Client $guzzle,
        private string $uri,
    ) {
    }

    /** @throws RuntimeException */
    public function postPackagingRequest(Packagings $packagings, Products $products): ?Packaging
    {
        $requestBody = $this->buildRequestBody($packagings, $products);

        try {
            $response = $this->guzzle->post($this->uri, ['json' => $requestBody, 'http_errors' => false]);
        } catch (GuzzleException $exception) {
            throw new RuntimeException('Upstream connection failed', previous: $exception);
        }

        if ($response->getStatusCode() !== 200) {
            throw new RuntimeException('Upstream request failed', $response->getStatusCode());
        }

        try {
            $data = json_decode(
                $response->getBody()->getContents(),
                associative: true,
                flags: JSON_THROW_ON_ERROR,
            );
        } catch (JsonException $exception) {
            throw new RuntimeException('Upstream response decoding failed', $exception->getCode());
        }

        $packagingId = $this->extractPackagingId($data);

        return $packagingId === null ? null : $packagings->getById((int) $packagingId);
    }

    /**
     * @return array{
     *     containers: list<array{id: string, width: int, length: int, depth: int, maxWeight: int}>,
     *     items: list<array{id: string, width: int, length: int, depth: int, weight: int}>
     * }
     */
    private function buildRequestBody(Packagings $packagings, Products $products): array
    {
        $requestBody = ['containers' => [], 'items' => []];

        foreach ($packagings as $packaging) {
            $requestBody['containers'][] = [
                'id' => (string) $packaging->id,
                'width' => (int) (10 * $packaging->width),
                'length' => (int) (10 * $packaging->length),
                'depth' => (int) (10 * $packaging->height),
                'maxWeight' => (int) (10 * $packaging->maxWeight),
            ];
        }

        foreach ($products as $key => $product) {
            $requestBody['items'][] = [
                'id' => (string) $key,
                'width' => (int) (10 * $product->width),
                'length' => (int) (10 * $product->length),
                'depth' => (int) (10 * $product->height),
                'weight' => (int) (10 * $product->weight),
            ];
        }

        return $requestBody;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function extractPackagingId(array $data): ?string
    {
        if (!array_key_exists('packedContainers', $data) || !is_array($data['packedContainers'])) {
            throw new RuntimeException('Missing or invalid packedContainers');
        }

        if (!array_key_exists('unpackedItems', $data) || !is_array($data['unpackedItems'])) {
            throw new RuntimeException('Missing or invalid unpackedItems');
        }

        if (count($data['packedContainers']) !== 1) {
            return null;
        }

        if (count($data['unpackedItems']) > 0) {
            return null;
        }

        if (
            !array_key_exists(0, $data['packedContainers'])
            || !is_array($data['packedContainers'][0])
            || !array_key_exists('containerId', $data['packedContainers'][0])
            || !is_string($data['packedContainers'][0]['containerId'])
        ) {
            throw new RuntimeException('Missing or invalid containerId');
        }

        return $data['packedContainers'][0]['containerId'];
    }
}
