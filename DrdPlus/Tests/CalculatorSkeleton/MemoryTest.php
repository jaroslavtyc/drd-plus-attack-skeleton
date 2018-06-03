<?php
namespace DrdPlus\Tests\Skeleton;

use DrdPlus\CalculatorSkeleton\Memory;
use Granam\Tests\Tools\TestWithMockery;

class MemoryTest extends TestWithMockery
{
    /**
     * @test
     * @runInSeparateProcess
     */
    public function Values_from_url_get_are_ignored(): void
    {
        $memory = new Memory(
            true, // remove previous memory, if any
            ['from' => 'inner memory'],
            true, // remember current values
            'foo'
        );
        self::assertFalse($memory->shouldForgotMemory());
        self::assertSame('inner memory', $memory->getValue('from'));
        $_GET['from'] = 'get';
        self::assertSame('inner memory', $memory->getValue('from'));
    }

    /**
     * @test
     * @runInSeparateProcess
     */
    public function Memory_is_immediately_forgotten_if_requested(): void
    {
        $fooMemory = new Memory(
            true, // remove previous memory, if any
            ['foo' => 'FOO'],
            true, // remember current values
            __FUNCTION__ // cookies prefix
        );
        self::assertSame('FOO', $fooMemory->getValue('foo'));
        $barMemory = new Memory(
            false, // do NOT remove previous memory
            ['bar' => 'BAR'],
            true, // remember current values
            __FUNCTION__ // cookies prefix
        );
        self::assertSame('FOO', $fooMemory->getValue('foo'), 'Existing instances should NOT be affected');
        self::assertNull($barMemory->getValue('foo'));
        self::assertSame('BAR', $barMemory->getValue('bar'));
        $anotherMemory = new Memory(
            false, // do NOT remove previous memory
            ['baz' => 'BAZ'],
            true, // remember current values
            __FUNCTION__ // cookies prefix
        );
        self::assertSame('FOO', $fooMemory->getValue('foo'));
        self::assertNull($anotherMemory->getValue('foo'));
        self::assertSame('BAR', $barMemory->getValue('bar'), 'Existing instances should NOT be affected');
        self::assertNull($anotherMemory->getValue('bar'));
        self::assertSame('BAZ', $anotherMemory->getValue('baz'));
        $yetAnotherMemory = new Memory(
            false, // do NOT remove previous memory
            ['baz' => 'BAZ'],
            true, // remember current values
            __FUNCTION__ // cookies prefix
        );
        self::assertNull($yetAnotherMemory->getValue('foo'));
        self::assertNull($yetAnotherMemory->getValue('bar'));
        self::assertSame('BAZ', $yetAnotherMemory->getValue('baz'));
    }

    /**
     * @test
     * @runInSeparateProcess
     */
    public function Memory_is_truncated_when_current_values_are_empty_only_if_cookie_memory_expires(): void
    {
        $fooMemory = new Memory(
            true, // remove previous memory, if any
            ['foo' => 'FOO'],
            true, // remember current values
            __FUNCTION__ // cookies prefix
        );
        self::assertSame('FOO', $fooMemory->getValue('foo'));
        $barMemory = new Memory(
            false, // do NOT remove previous memory
            ['bar' => 'BAR'],
            true, // remember current values
            __FUNCTION__, // cookies prefix
            -1 // TTL
        );
        self::assertSame('FOO', $fooMemory->getValue('foo'), 'Existing instances should NOT be affected');
        self::assertNull($barMemory->getValue('foo'));
        self::assertSame('BAR', $barMemory->getValue('bar'));
        $anotherMemory = new Memory(
            false, // do NOT remove previous memory
            [], // empty values
            true, // remember current values
            __FUNCTION__ // cookies prefix
        );
        self::assertNull($anotherMemory->getValue('foo'));
        self::assertSame('BAR', $anotherMemory->getValue('bar'), 'Nothing should changed with empty current values');
        $_COOKIE['configurator_memory_token-' . __FUNCTION__] = false;
        $yetAnotherMemory = new Memory(
            false, // do NOT remove previous memory
            [], // empty values
            false, // do NOT remember current values
            __FUNCTION__ // cookies prefix
        );
        self::assertNull($anotherMemory->getValue('foo'));
        self::assertNull($yetAnotherMemory->getValue('bar'));
    }

    /**
     * @test
     * @runInSeparateProcess
     */
    public function I_can_set_new_value_as_well_as_rewrite_it(): void
    {
        $fooMemory = new Memory(
            true, // remove previous memory, if any
            ['foo' => 'FOO'],
            true, // remember current values
            __FUNCTION__ // cookies prefix
        );
        self::assertSame('FOO', $fooMemory->getValue('foo'));
        self::assertNull($fooMemory->getValue('bar'));
        $fooMemory->rewrite('bar', 'BAR');
        self::assertSame('BAR', $fooMemory->getValue('bar'));
        $fooMemory->rewrite('bar', 'Whisky');
        self::assertSame('Whisky', $fooMemory->getValue('bar'));
    }

    /**
     * @test
     * @runInSeparateProcess
     */
    public function I_can_set_value_by_rewrite_even_if_no_values_were_set_before(): void
    {
        $memory = new Memory(
            true, // remove previous memory, if any
            [], // no values
            true, // remember current values
            __FUNCTION__ // cookies prefix
        );
        $memory->rewrite('foo', 'FOO');
        self::assertSame('FOO', $memory->getValue('foo'));
    }

    /**
     * @test
     * @runInSeparateProcess
     */
    public function I_can_get_all_values_by_iteration(): void
    {
        $values = ['foo' => 123, 'bar' => 456];
        $memory = new Memory(
            true, // remove previous memory, if any
            $values,
            true, // remember current values
            __FUNCTION__ // cookies prefix
        );

        $collectedValues = [];
        foreach ($memory as $name => $value) {
            $collectedValues[$name] = $value;
        }
        self::assertSame($values, $collectedValues);
    }

    /**
     * @test
     * @runInSeparateProcess
     */
    public function I_can_get_all_values_at_once(): void
    {
        $values = ['foo' => 123, 'bar' => 456];
        $memory = new Memory(
            true, // remove previous memory, if any
            $values,
            true, // remember current values
            __FUNCTION__ // cookies prefix
        );
        self::assertSame($values, $memory->getIterator()->getArrayCopy());
    }
}
