<?php

declare(strict_types=1);

namespace App;

use App\Domain\Exception\TooManyProducts;
use App\Domain\PackageFinder\ApiPackageFinder;
use App\Domain\PackageFinder\FallbackPackageFinder;
use App\Domain\PackageFinder\PackageFinder;
use App\Domain\PackageFinder\PackageFinderResult;
use App\Domain\PackageFinder\RepositoryPackageFinder;
use App\Domain\Product;
use App\Domain\Products;
use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use JsonException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final readonly class Application
{
    public function __construct(
        private PackageFinder $packageFinder,
    ) {
    }

    public function run(RequestInterface $request): ResponseInterface
    {
        try {
            $requestBody = $this->decodeRequest($request);
        } catch (JsonException) {
            return $this->getInvalidRequestFormatResponse();
        }

        try {
            $products = $this->extractProducts($requestBody);
        } catch (InvalidArgumentException) {
            return $this->getInvalidRequestContentResponse();
        }

        try {
            $result = $this->packageFinder->findPackage($products);
        } catch (TooManyProducts) {
            return $this->getTooManyProductsResponse();
        }

        if ($result->status !== PackageFinderResult::HIT) {
            return $this->getServiceUnavailableResponse();
        }

        return new Response(body: json_encode([
            'products' => iterator_to_array($products),
            'packaging' => $result->packaging,
            'source' => match ($result->source) {
                RepositoryPackageFinder::class => 'cache',
                ApiPackageFinder::class => 'api',
                FallbackPackageFinder::class => 'fallback',
                default => 'misconfigured',
            },
        ], JSON_PRETTY_PRINT));
    }

    private function getInvalidRequestFormatResponse(): Response
    {
        return new Response(
            status: 400,
            body: json_encode([
                'error' => 'invalid_request_format',
                'message' => 'Request body is not a valid JSON',
            ])
        );
    }

    private function getInvalidRequestContentResponse(): Response
    {
        return new Response(
            status: 400,
            body: json_encode([
                'error' => 'invalid_request_content',
                'message' => 'Request body has invalid structure',
            ])
        );
    }

    /**
     * @throws JsonException
     */
    private function decodeRequest(RequestInterface $request): mixed
    {
        return json_decode(
            $request->getBody()->getContents(),
            associative: true,
            flags: JSON_THROW_ON_ERROR,
        );
    }

    private function getTooManyProductsResponse(): Response
    {
        return new Response(
            status: 422,
            body: json_encode([
                'error' => 'too_many_products',
                'message' => 'Too many products to determine packaging',
            ])
        );
    }

    public function getServiceUnavailableResponse(): Response
    {
        return new Response(status: 503, body: json_encode([
            'error' => 'service_unavailable',
            'message' => 'Unable to determine packaging at this time',
        ]));
    }

    /**
     * @param array<string, mixed> $responseBody
     */
    private function extractProducts(array $responseBody): Products
    {
        if (!array_key_exists('products', $responseBody) || !is_array($responseBody['products'])) {
            throw new InvalidArgumentException('Missing or invalid products');
        }

        $products = [];

        foreach ($responseBody['products'] as $product) {
            if (!is_array($product)) {
                throw new InvalidArgumentException('Invalid product entry');
            }

            foreach (['width', 'height', 'length', 'weight'] as $field) {
                if (!array_key_exists($field, $product)) {
                    throw new InvalidArgumentException(sprintf('Missing %s', $field));
                }

                if (!is_int($product[$field]) && !is_float($product[$field])) {
                    throw new InvalidArgumentException(sprintf('Invalid %s', $field));
                }
            }

            $products[] = new Product(
                $product['width'],
                $product['height'],
                $product['length'],
                $product['weight'],
            );
        }

        return new Products(...$products);
    }
}
