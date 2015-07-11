<?php

namespace rockunit;


use rock\exception\ErrorHandler;
use rock\log\Log;
use rock\response\Response;

class ErrorHandlerTest extends \PHPUnit_Framework_TestCase
{
    public function testDisplay()
    {
        $response = new Response();
        $this->assertSame(200, $response->statusCode);
        ErrorHandler::display(new \Exception('error test'), Log::CRITICAL, $response);
        $this->assertSame(500, $response->statusCode);

        // fatal
        $response = new Response();
        $response->format = Response::FORMAT_JSON;
        $this->assertSame(200, $response->statusCode);
        ErrorHandler::displayFatal($response);
        $this->assertSame(500, $response->statusCode);
        $this->expectOutputString('0');
    }
}
