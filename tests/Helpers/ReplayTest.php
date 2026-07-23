<?php

declare(strict_types=1);

namespace Krystal\KatapultTest\Helpers;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WHMCS\Module\Server\Katapult\Helpers\Replay;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class ReplayTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Replay reads these superglobals directly; start each test clean.
        $_SESSION = [];
        $_REQUEST = [];
    }

    #[Test]
    public function get_token_issues_a_token_when_none_exists(): void
    {
        $this->assertArrayNotHasKey('knrp_token', $_SESSION);

        $token = Replay::getToken();

        $this->assertNotEmpty($token);
        $this->assertSame($token, $_SESSION['knrp_token']);
    }

    #[Test]
    public function get_token_is_stable_across_renders(): void
    {
        // Rendering the token repeatedly (head hook + template, reloads, other
        // requests) must not rotate it.
        $first = Replay::getToken();
        $second = Replay::getToken();
        $third = Replay::getToken();

        $this->assertSame($first, $second);
        $this->assertSame($second, $third);
    }

    #[Test]
    public function valid_token_is_accepted(): void
    {
        $token = Replay::getToken();

        $this->assertTrue(Replay::tokenIsValid($token));
    }

    #[Test]
    public function valid_token_is_consumed_and_cannot_be_reused(): void
    {
        $token = Replay::getToken();

        $this->assertTrue(Replay::tokenIsValid($token), 'first use should succeed');
        $this->assertFalse(Replay::tokenIsValid($token), 'replaying the same token must fail');
    }

    #[Test]
    public function consuming_a_token_rotates_it(): void
    {
        $token = Replay::getToken();

        Replay::tokenIsValid($token);

        $this->assertNotSame($token, $_SESSION['knrp_token']);
        $this->assertNotEmpty($_SESSION['knrp_token']);
    }

    #[Test]
    public function a_wrong_token_is_rejected_without_consuming(): void
    {
        $token = Replay::getToken();

        $this->assertFalse(Replay::tokenIsValid('not-the-token'));
        // The genuine token must survive an invalid attempt.
        $this->assertSame($token, $_SESSION['knrp_token']);
        $this->assertTrue(Replay::tokenIsValid($token));
    }

    #[Test]
    public function an_empty_token_is_rejected(): void
    {
        Replay::getToken();

        $this->assertFalse(Replay::tokenIsValid(''));
    }

    #[Test]
    public function token_is_read_from_the_request_when_not_passed(): void
    {
        $token = Replay::getToken();
        $_REQUEST['knrp'] = $token;

        $this->assertTrue(Replay::tokenIsValid());
    }

    #[Test]
    public function request_token_is_trimmed(): void
    {
        $token = Replay::getToken();
        $_REQUEST['knrp'] = "  {$token}  ";

        $this->assertTrue(Replay::tokenIsValid());
    }

    #[Test]
    public function validation_fails_when_no_token_has_been_issued(): void
    {
        // No getToken() call, so the session holds nothing to match against.
        $this->assertFalse(Replay::tokenIsValid('anything'));
    }

    #[Test]
    public function client_area_check_returns_null_outside_the_client_area(): void
    {
        // CLIENTAREA is undefined in this process, so the gate should abstain
        // rather than validate.
        $this->assertNull(Replay::tokenIsValidForClientArea('anything'));
    }
}
