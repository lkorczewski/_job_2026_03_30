<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\PackagingApi;

use App\Domain\Packaging;
use App\Domain\Packagings;
use App\Domain\Product;
use App\Domain\Products;
use App\Infrastructure\Janedbal\JanedbalApiConnector;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class JanedbalApiConnectorTest extends TestCase
{
    public function testPackagingFound(): void
    {
        $guzzle = $this->createMock(Client::class);
        $guzzle
            ->expects(self::once())
            ->method('post')
            ->willReturn(new Response(200, body: '{"packedContainers":[{"containerId":"2"}],"unpackedItems":[]}'));

        $connector = new JanedbalApiConnector($guzzle, 'http://example.test');

        $packagings = new Packagings(
            new Packaging(id: 1, width: 10, height: 10, length: 10, maxWeight: 10),
            new Packaging(id: 2, width: 20, height: 20, length: 20, maxWeight: 20),
        );
        $products = new Products(
            new Product(width: 1, height: 2, length: 3, weight: 4),
            new Product(width: 5, height: 6, length: 7, weight: 8),
        );

        self::assertEquals(
            new Packaging(id: 2, width: 20, height: 20, length: 20, maxWeight: 20),
            $connector->postPackagingRequest($packagings, $products),
        );
    }

    #[DataProvider('providePackagingNotFound')]
    public function testPackagingNotFound(string $responseBody): void
    {
        $guzzle = $this->createMock(Client::class);
        $guzzle
            ->expects(self::once())
            ->method('post')
            ->willReturn(new Response(200, body: $responseBody));

        $connector = new JanedbalApiConnector($guzzle, 'http://example.test');

        self::assertNull($connector->postPackagingRequest(new Packagings(), new Products()));
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function providePackagingNotFound(): iterable
    {
        yield 'empty packedContainers' => [
            '{"packedContainers":[],"unpackedItems":[]}',
        ];

        yield 'two packedContainers' => [
            '{"packedContainers":[{"containerId":"1"},{"containerId":"2"}],"unpackedItems":[]}',
        ];

        yield 'single packed container with unpacked items' => [
            '{"packedContainers":[{"containerId":"1"}],"unpackedItems":["item-1"]}',
        ];
    }

    // failures

    public function testConnectionFailure(): void
    {
        $guzzle = $this->createMock(Client::class);
        $guzzle
            ->expects(self::once())
            ->method('post')
            ->willThrowException(
                new ConnectException(
                    'Connection refused',
                    new Request('POST', 'http://example.test')
                )
            );

        $connector = new JanedbalApiConnector($guzzle, 'http://example.test');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Upstream connection failed');

        $connector->postPackagingRequest(new Packagings(), new Products());
    }

    #[DataProvider('provideNonOkResponses')]
    public function testNonOkResponse(int $statusCode, string $responseBody): void
    {
        $guzzle = $this->createMock(Client::class);
        $guzzle
            ->expects(self::once())
            ->method('post')
            ->willReturn(new Response($statusCode, body: $responseBody));

        $connector = new JanedbalApiConnector($guzzle, 'http://example.test');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Upstream request failed');

        try {
            $connector->postPackagingRequest(new Packagings(), new Products());
        } catch (RuntimeException $exception) {
            self::assertSame($statusCode, $exception->getCode());
            throw $exception;
        }
    }

    /**
     * @return iterable<string, array{0: int, 1: string}>
     */
    public static function provideNonOkResponses(): iterable
    {
        yield '400 bad request' => [
            400,
            json_encode([
                'error' => 'bad_request',
                'message' => 'Invalid JSON: Syntax error',
            ]),
        ];

        yield '404 not found' => [
            404,
            json_encode([
                'error' => 'http_error',
                'message' => 'No route found for "GET /invalid"',
            ]),
        ];

        yield '405 method not allowed' => [
            405,
            json_encode([
                'error' => 'http_error',
                'message' => 'No route found for "GET /api/v1/pack": Method Not Allowed (Allow: POST)',
            ]),
        ];

        yield '422 validation failed' => [
            422,
            json_encode([
                'error' => 'validation_failed',
                'message' => 'Input validation failed',
                'violations' => [[
                    'path' => '/containers/0/width',
                    'message' => 'Failed to map data at path /containers/0/width: Expected positive integer, got -5',
                ]],
            ]),
        ];

        yield '429 rate limit exceeded' => [
            429,
            json_encode([
                'error' => 'rate_limit_exceeded',
                'message' => 'Too many requests. Try again later.',
            ]),
        ];

        yield '500 internal error' => [
            500,
            json_encode([
                'error' => 'internal_error',
                'message' => 'An unexpected error occurred',
            ]),
        ];
    }

    public function testInvalidJsonResponse(): void
    {
        $guzzle = $this->createMock(Client::class);
        $guzzle
            ->expects(self::once())
            ->method('post')
            ->willReturn(new Response(200, body: '{"invalid_json"'));

        $connector = new JanedbalApiConnector($guzzle, 'http://example.test');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Upstream response decoding failed');

        $connector->postPackagingRequest(new Packagings(), new Products());
    }

    #[DataProvider('provideInvalidResponseStructures')]
    public function testInvalidResponseStructure(string $responseBody, string $expectedPreviousMessage): void
    {
        $guzzle = $this->createMock(Client::class);
        $guzzle
            ->expects(self::once())
            ->method('post')
            ->willReturn(new Response(200, body: $responseBody));

        $connector = new JanedbalApiConnector($guzzle, 'http://example.test');

        $this->expectException(RuntimeException::class);

        try {
            $connector->postPackagingRequest(new Packagings(), new Products());
        } catch (RuntimeException $exception) {
            self::assertSame($expectedPreviousMessage, $exception->getMessage());
            throw $exception;
        }
    }

    /**
     * @return iterable<string, array{0: string, 1: string}>
     */
    public static function provideInvalidResponseStructures(): iterable
    {
        yield 'missing packedContainers' => [
            '{"unpackedItems":[]}',
            'Missing or invalid packedContainers',
        ];

        yield 'missing unpackedItems' => [
            '{"packedContainers":[{"containerId":"1"}]}',
            'Missing or invalid unpackedItems',
        ];

        yield 'packedContainers is not an array' => [
            '{"packedContainers":"invalid","unpackedItems":[]}',
            'Missing or invalid packedContainers',
        ];

        yield 'missing containerId' => [
            '{"packedContainers":[{}],"unpackedItems":[]}',
            'Missing or invalid containerId',
        ];
    }
}
