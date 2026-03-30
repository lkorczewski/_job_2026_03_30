<?php

declare(strict_types=1);

namespace App\Tests;

use App\Application;
use App\Domain\PackagingFinder\ApiPackagingFinder;
use App\Domain\PackagingFinder\FallbackPackagingFinder;
use App\Domain\PackagingFinder\PackagingFinder;
use App\Domain\PackagingFinder\PackagingFinderResult;
use App\Domain\PackagingFinder\RepositoryPackagingFinder;
use App\Domain\Packaging;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ApplicationTest extends TestCase
{
    private PackagingFinder & MockObject $packagingFinder;
    private Application $application;

    protected function setUp(): void
    {
        $this->packagingFinder = $this->createMock(PackagingFinder::class);
        $this->application = new Application($this->packagingFinder);
    }

    public function testSuccess(): void
    {
        $this->packagingFinder
            ->expects(self::once())
            ->method('findPackage')
            ->willReturn(
                PackagingFinderResult::createHit(
                    new Packaging(id: 1, width: 10, height: 20, length: 30, maxWeight: 40),
                    ApiPackagingFinder::class,
                )
            );

        $response = $this->application->run(
            new Request(
                'POST',
                '/pack',
                ['Content-Type' => 'application/json'],
                '{"products":[{"width":1,"height":2,"length":3,"weight":4}]}',
            )
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(
            <<<JSON
            {
                "products": [
                    {
                        "width": 1,
                        "height": 2,
                        "length": 3,
                        "weight": 4
                    }
                ],
                "packaging": {
                    "id": 1,
                    "width": 10,
                    "height": 20,
                    "length": 30,
                    "maxWeight": 40
                },
                "source": "api"
            }
            JSON,
            $response->getBody()->getContents(),
        );
    }

    public function testInvalidJson(): void
    {
        $this->packagingFinder->expects(self::never())->method('findPackage');

        $response = $this->application->run(
            new Request('POST', '/pack', ['Content-Type' => 'application/json'], '{"products":')
        );

        self::assertSame(400, $response->getStatusCode());
        self::assertSame(
            '{"error":"invalid_request_format","message":"Request body is not a valid JSON"}',
            $response->getBody()->getContents(),
        );
    }

    public function testInvalidRequestStructure(): void
    {
        $this->packagingFinder->expects(self::never())->method('findPackage');

        $response = $this->application->run(
            new Request('POST', '/pack', ['Content-Type' => 'application/json'], '{"foo":"bar"}')
        );

        self::assertSame(400, $response->getStatusCode());
        self::assertSame(
            '{"error":"invalid_request_content","message":"Request body has invalid structure"}',
            $response->getBody()->getContents(),
        );
    }

    public function testTooManyProducts(): void
    {
        $this->packagingFinder->expects(self::never())->method('findPackage');

        $products = array_fill(0, 101, [
            'width' => 1,
            'height' => 2,
            'length' => 3,
            'weight' => 4,
        ]);

        $response = $this->application->run(
            new Request(
                'POST',
                '/pack',
                ['Content-Type' => 'application/json'],
                json_encode(['products' => $products]),
            )
        );

        self::assertSame(422, $response->getStatusCode());
        self::assertSame(
            '{"error":"too_many_products","message":"Too many products to determine packaging"}',
            $response->getBody()->getContents(),
        );
    }

    public function testUnableToDetermineResponse(): void
    {
        $this->packagingFinder
            ->expects(self::once())
            ->method('findPackage')
            ->willReturn(PackagingFinderResult::createMiss(RepositoryPackagingFinder::class));

        $response = $this->application->run(
            new Request(
                'POST',
                '/pack',
                ['Content-Type' => 'application/json'],
                '{"products":[{"width":1,"height":2,"length":3,"weight":4}]}',
            )
        );

        self::assertSame(503, $response->getStatusCode());
        self::assertSame(
            '{"error":"service_unavailable","message":"Unable to determine packaging at this time"}',
            $response->getBody()->getContents(),
        );
    }
}
