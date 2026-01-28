<?php

use PHPUnit\Framework\TestCase;
use App\Services\PasswordService;

class PasswordServiceTest extends TestCase
{
    private $db;
    private $service;

    protected function setUp(): void
    {
        $this->db = $this->createMock(mysqli::class);
        // We will mock the Service using Partial Mocking to override protected methods
        // `onlyMethods` allows us to mock specific methods while keeping others real.
    }

    private function getServiceMock($methodsToMock = [])
    {
        return $this->getMockBuilder(PasswordService::class)
                    ->setConstructorArgs([$this->db])
                    ->onlyMethods($methodsToMock)
                    ->getMock();
    }

    public function testChangePasswordFailureMismatch()
    {
        $service = new PasswordService($this->db);
        $result = $service->changePassword(1, 'oldpass', 'newpass', 'differentpass');
        $this->assertFalse($result['success']);
        $this->assertEquals('New passwords do not match.', $result['message']);
    }

    public function testChangePasswordFailureUserNotFound()
    {
        $service = $this->getServiceMock(['getUserHash']);
        $service->expects($this->once())
                ->method('getUserHash')
                ->with(1)
                ->willReturn(null);

        $result = $service->changePassword(1, 'oldpass', 'newpass', 'newpass');
        $this->assertFalse($result['success']);
        $this->assertEquals('User not found.', $result['message']);
    }

    public function testChangePasswordFailureWrongCurrent()
    {
        $realHash = password_hash('correct_old', PASSWORD_DEFAULT);
        
        $service = $this->getServiceMock(['getUserHash']);
        $service->expects($this->once())
                ->method('getUserHash')
                ->willReturn($realHash);

        $result = $service->changePassword(1, 'wrong_old', 'newpass', 'newpass');
        $this->assertFalse($result['success']);
        $this->assertEquals('Incorrect current password.', $result['message']);
    }

    public function testChangePasswordSuccess()
    {
        $realHash = password_hash('correct_old', PASSWORD_DEFAULT);
        
        $service = $this->getServiceMock(['getUserHash', 'updateUserHash']);
        
        $service->expects($this->once())
                ->method('getUserHash')
                ->willReturn($realHash);
                
        $service->expects($this->once())
                ->method('updateUserHash')
                ->with(1, $this->anything()) // New hash will vary
                ->willReturn(true);

        $result = $service->changePassword(1, 'correct_old', 'newpass', 'newpass');
        $this->assertTrue($result['success']);
        $this->assertEquals('Password changed successfully.', $result['message']);
    }
}
