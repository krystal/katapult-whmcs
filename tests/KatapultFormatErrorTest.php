<?php

declare(strict_types=1);

namespace Krystal\KatapultTest;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Http\Client\Common\Exception\ClientErrorException;
use Krystal\Katapult\KatapultAPI\Model\VirtualMachinesVirtualMachineGetResponse200;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WHMCS\Module\Server\Katapult\Katapult\API\APIException;

class KatapultFormatErrorTest extends TestCase
{
    #[Test]
    public function general_exception_results_in_general_message(): void
    {
        $e = new \RuntimeException('Foo', 42);
        $errorMessage = \Katapult\formatError('KATAPULT', $e);
        $this->assertEquals('KATAPULT [42]: Foo', $errorMessage);

        $e = new \RuntimeException('Foo');
        $errorMessage = \Katapult\formatError('KATAPULT', $e);
        $this->assertEquals('KATAPULT: Foo', $errorMessage);
    }

    #[Test]
    public function handles_API_exception(): void
    {
        $e = APIException::new(null, VirtualMachinesVirtualMachineGetResponse200::class);

        $this->assertEquals('K [http: 0]: Response is type NULL but was expected to be Krystal\Katapult\KatapultAPI\Model\VirtualMachinesVirtualMachineGetResponse200.', \Katapult\formatError('K', $e));
    }

    #[Test]
    public function handles_API_exception_with_response(): void
    {
        $json = <<<JSON
{
  "error": {
    "code": "validation_error",
    "description": "A validation error occurred with the object that was being created/updated/deleted",
    "detail": {
      "errors": [
        "First system disk must be greater than 50GB (required for Ubuntu 20.04)"
      ]
    }
  }
}
JSON;

        $response = new Response(400, [], $json);

        $e = APIException::new($response, VirtualMachinesVirtualMachineGetResponse200::class);

        $this->assertEquals(
            'K [http: 400]: validation_error: A validation error occurred with the object that was being created/updated/deleted - First system disk must be greater than 50GB (required for Ubuntu 20.04)',
            \Katapult\formatError('K', $e)
        );
    }

    #[Test]
    public function handles_client_error_exception_with_response(): void
    {
        $json = <<<JSON
{
  "error": {
    "code": "validation_error",
    "description": "A validation error occurred with the object that was being created/updated/deleted",
    "detail": {
      "errors": [
        "First system disk must be greater than 50GB (required for Ubuntu 20.04)"
      ]
    }
  }
}
JSON;

        $request = new Request('GET', '/foobar');
        $response = new Response(400, [], $json);

        $e = new ClientErrorException('', $request, $response);

        $this->assertEquals(
            'K [http: 400 GET /foobar]: validation_error: A validation error occurred with the object that was being created/updated/deleted - First system disk must be greater than 50GB (required for Ubuntu 20.04)',
            \Katapult\formatError('K', $e)
        );
    }

    #[Test]
    public function handles_client_error_exception_errors_string_with_response(): void
    {
        $json = <<<JSON
{
  "error": {
    "code": "validation_error",
    "description": "A validation error occurred with the object that was being created/updated/deleted",
    "detail": {
      "errors": "First system disk must be greater than 50GB (required for Ubuntu 20.04)",
      "details": "Some additional details"
    }
  }
}
JSON;

        $request = new Request('GET', '/foobar');
        $response = new Response(400, [], $json);

        $e = new ClientErrorException('', $request, $response);

        $this->assertEquals(
            'K [http: 400 GET /foobar]: validation_error: A validation error occurred with the object that was being created/updated/deleted - Some additional details - First system disk must be greater than 50GB (required for Ubuntu 20.04)',
            \Katapult\formatError('K', $e)
        );
    }
}
