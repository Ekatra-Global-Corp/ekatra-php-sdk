<?php

namespace Ekatra\Product\Tests\Unit\Exceptions;

use PHPUnit\Framework\TestCase;
use Ekatra\Product\Exceptions\EkatraValidationException;

class ValidationExceptionTest extends TestCase
{
    public function testValidationExceptionCreation()
    {
        $message = 'Validation failed';
        $errors = ['field1' => 'Error 1', 'field2' => 'Error 2'];
        
        $exception = new EkatraValidationException($message, $errors);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($errors, $exception->getErrors());
        $this->assertEquals(422, $exception->getCode());
    }

    public function testValidationExceptionWithEmptyErrors()
    {
        $message = 'Validation failed';
        $errors = [];
        
        $exception = new EkatraValidationException($message, $errors);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($errors, $exception->getErrors());
        $this->assertEquals(422, $exception->getCode());
    }

    public function testValidationExceptionInheritance()
    {
        $message = 'Validation failed';
        $errors = ['field1' => 'Error 1'];
        
        $exception = new EkatraValidationException($message, $errors);

        $this->assertInstanceOf(\Exception::class, $exception);
    }
}
