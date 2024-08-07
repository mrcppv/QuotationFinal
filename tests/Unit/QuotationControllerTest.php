<?php

namespace Tests\Unit;

use App\Http\Controllers\QuotationController;
use App\Services\QuotationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Mockery;
use Tests\TestCase;

class QuotationControllerTest extends TestCase
{
    private QuotationController $controller;
    private $mockQuotationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockQuotationService = Mockery::mock(QuotationService::class);
        $this->controller = new QuotationController($this->mockQuotationService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testGetQuotationSuccessfully()
    {
        $request = new Request([   //set up with valid data
            'age' => '25,35',
            'currency_id' => 'EUR',
            'start_date' => '2024-08-01',
            'end_date' => '2024-08-10',
        ]);

        $expectedQuotation = [
            'total' => 117.0,
            'currency_id' => 'EUR',
            'quotation_id' => 'qid123456',
        ];

        $this->mockQuotationService->shouldReceive('calculateQuotation') //call method once
            ->once()
            ->with([25, 35], 'EUR', '2024-08-01', '2024-08-10')
            ->andReturn($expectedQuotation);

        $response = $this->controller->getQuotation($request);

        $this->assertInstanceOf(JsonResponse::class, $response);  //confirm json response
        $this->assertEquals(200, $response->getStatusCode());   //confirm 200 status
        $this->assertEquals($expectedQuotation, $response->getData(true));  //decode json to array for comparison with expected quotation
    }

    public function testGetQuotationWithInvalidInput()
    {
        $request = new Request([
            'age' => '25,35',
            'currency_id' => 'EUR',
            'start_date' => '2024-08-01',
            'end_date' => '2024-08-10',
        ]);

        $this->mockQuotationService->shouldReceive('calculateQuotation')    // set mock serivce to throw exception when calculatequestion is called
            ->once()
            ->andThrow(new InvalidArgumentException('Invalid input'));

        $response = $this->controller->getQuotation($request);

        $this->assertInstanceOf(JsonResponse::class, $response);  //confirm json response
        $this->assertEquals(422, $response->getStatusCode());   //confirm 422 response
        $this->assertEquals(['error' => 'Invalid input'], $response->getData(true)); //compare response to expected error
    }

    public function testGetQuotationWithMissingRequiredFields()
    {
        $request = new Request([
            'age' => '25,35',
            'currency_id' => 'EUR',
            // Missing start_date and end_date
        ]);

        $response = $this->controller->getQuotation($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(422, $response->getStatusCode());
        $responseData = $response->getData(true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertArrayHasKey('start_date', $responseData['error']);
        $this->assertArrayHasKey('end_date', $responseData['error']);
    }
}

