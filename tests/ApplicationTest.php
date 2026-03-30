<?php

declare(strict_types=1);

namespace App\Tests;

use App\Application;
use App\Domain\PackageFinder\ApiPackageFinder;
use App\Domain\PackageFinder\FallbackPackageFinder;
use App\Domain\PackageFinder\PackageFinder;
use App\Domain\PackageFinder\PackageFinderResult;
use App\Domain\PackageFinder\RepositoryPackageFinder;
use App\Domain\Packaging;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ApplicationTest extends TestCase
{
    private PackageFinder & MockObject $packageFinder;
    private Application $application;

    protected function setUp(): void
    {
        $this->packageFinder = $this->createMock(PackageFinder::class);
        $this->application = new Application($this->packageFinder);
    }

    public function testSuccess(): void
    {
        $this->packageFinder
            ->expects(self::once())
            ->method('findPackage')
            ->willReturn(
                PackageFinderResult::createHit(
                    new Packaging(id: 1, width: 10, height: 20, length: 30, maxWeight: 40),
                    ApiPackageFinder::class,
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
        $this->packageFinder->expects(self::never())->method('findPackage');

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
        $this->packageFinder->expects(self::never())->method('findPackage');

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
        $this->packageFinder->expects(self::never())->method('findPackage');

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
        $this->packageFinder
            ->expects(self::once())
            ->method('findPackage')
            ->willReturn(PackageFinderResult::createMiss(RepositoryPackageFinder::class));

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
