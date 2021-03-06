<?php

namespace ZoiloMora\ElasticAPM\Tests\Events\Transaction;

use ZoiloMora\ElasticAPM\Tests\Utils\TestCase;
use ZoiloMora\ElasticAPM\Events\Transaction\Transaction;

class TransactionTest extends TestCase
{
    /**
     * @test
     */
    public function given_data_when_instantiating_then_return_can_get_properties()
    {
        $name = 'GET /users/:id';
        $type = 'request';
        $context = $this->getMockWithoutConstructor('ZoiloMora\ElasticAPM\Events\Common\Context');
        $traceId = 'trace-id';
        $parentId = 'parent-id';

        $object = new Transaction(
            $name,
            $type,
            $context,
            $traceId,
            $parentId
        );

        self::assertSame($context, $object->context());
    }

    /**
     * @test
     */
    public function given_data_when_instantiating_then_return_transaction_started()
    {
        $object = $this->generateTransaction();

        self::assertFalse($object->isFinished());
        $object->stop();
        self::assertTrue($object->isFinished());
    }

    /**
     * @test
     */
    public function given_a_transaction_when_finish_then_can_get_the_duration()
    {
        $object = $this->generateTransaction();
        $object->stop();

        self::assertInternalType('double', $object->duration());
    }

    /**
     * @test
     */
    public function given_a_transaction_when_finish_with_null_result_then_has_been_finalized()
    {
        $object = $this->generateTransaction();
        $object->stop(null);

        self::assertTrue($object->isFinished());
    }

    /**
     * @test
     */
    public function given_a_transaction_when_finish_with_string_result_then_has_been_finalized()
    {
        $object = $this->generateTransaction();
        $object->stop('result');

        self::assertTrue($object->isFinished());
    }

    /**
     * @test
     */
    public function given_a_transaction_when_finish_with_not_allowed_type_of_result_then_throw_exception()
    {
        $object = $this->generateTransaction();

        self::setExpectedException('InvalidArgumentException', 'The [result] must be of type string or null.');

        $object->stop(1);
    }

    /**
     * @test
     */
    public function given_a_transaction_when_serialize_then_right_serialization()
    {
        $contextValue = 'context';

        $name = 'GET /users/:id';
        $type = 'request';
        $contextMock = $this->getMockSerializable('ZoiloMora\ElasticAPM\Events\Common\Context', $contextValue);
        $traceId = 'trace-id';
        $parentId = 'parent-id';

        $object = new Transaction(
            $name,
            $type,
            $contextMock,
            $traceId,
            $parentId
        );

        $actual = json_decode(
            json_encode($object),
            true
        );

        $transaction = $actual['transaction'];

        self::assertArrayHasKey('timestamp', $transaction);
        self::assertArrayHasKey('name', $transaction);
        self::assertArrayHasKey('type', $transaction);
        self::assertArrayHasKey('span_count', $transaction);
        self::assertArrayHasKey('context', $transaction);
        self::assertArrayHasKey('duration', $transaction);
        self::assertArrayHasKey('result', $transaction);

        self::assertSame($name, $transaction['name']);
        self::assertSame($type, $transaction['type']);
        self::assertSame($contextValue, $transaction['context']);
    }

    private function generateTransaction()
    {
        $name = 'GET /users/:id';
        $type = 'request';
        $context = $this->getMockWithoutConstructor('ZoiloMora\ElasticAPM\Events\Common\Context');
        $traceId = 'trace-id';
        $parentId = 'parent-id';

        return new Transaction(
            $name,
            $type,
            $context,
            $traceId,
            $parentId
        );
    }
}
