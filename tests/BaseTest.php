<?php

namespace MrMadClown\Mnemosyne\Tests;

use PDO;
use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

abstract class BaseTest extends TestCase
{
    protected function mockPDO(): MockObject&PDO
    {
        return $this->getMockBuilder(\PDO::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function mockStatement(): MockObject&PDOStatement
    {
        return $this->getMockBuilder(\PDOStatement::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
