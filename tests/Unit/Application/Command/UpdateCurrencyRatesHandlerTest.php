<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Command;

use App\Application\Command\UpdateCurrencyRatesCommand;
use App\Application\Command\UpdateCurrencyRatesHandler;
use App\Application\Command\UpdateResult;
use App\Application\Service\CurrencyRateService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class UpdateCurrencyRatesHandlerTest extends TestCase
{
    private UpdateCurrencyRatesHandler $handler;
    private MockObject $mockCurrencyRateService;

    protected function setUp(): void
    {
        $this->mockCurrencyRateService = $this->createMock(CurrencyRateService::class);
        $this->handler = new UpdateCurrencyRatesHandler(
            $this->mockCurrencyRateService,
            new NullLogger()
        );
    }

    public function testHandleUpdateAllRatesCommand(): void
    {
        // Arrange
        $command = new UpdateCurrencyRatesCommand();
        $expectedResult = new UpdateResult();
        $expectedResult->addSuccess('EUR/BTC');
        $expectedResult->addSuccess('EUR/ETH');

        $this->mockCurrencyRateService
            ->expects($this->once())
            ->method('updateAllRates')
            ->with(false)
            ->willReturn($expectedResult);

        // Act
        $result = $this->handler->__invoke($command);

        // Assert
        $this->assertEquals($expectedResult, $result);
        $this->assertEquals(2, $result->getSuccessCount());
    }

    public function testHandleUpdateSpecificPairsCommand(): void
    {
        // Arrange
        $specificPairs = ['EUR/BTC'];
        $command = new UpdateCurrencyRatesCommand(true, $specificPairs);
        $expectedResult = new UpdateResult();

        $this->mockCurrencyRateService
            ->expects($this->once())
            ->method('updateSpecificRates')
            ->with($specificPairs, true)
            ->willReturn($expectedResult);

        // Act
        $result = $this->handler->__invoke($command);

        // Assert
        $this->assertEquals($expectedResult, $result);
    }
}
