<?php

declare(strict_types=1);

use Mei\Entity\EntityAttributeType;

/**
 * Class EntityAttributeTypeTest
 */
class EntityAttributeTypeTest extends PHPUnit\Framework\TestCase
{
    public function test_maps_bool_from_string(): void
    {
        self::assertEquals(false, EntityAttributeType::inflate('bool', '0'));
        self::assertEquals(true, EntityAttributeType::inflate('bool', '1'));
    }

    public function test_maps_enum_bool_from_string(): void
    {
        self::assertEquals(false, EntityAttributeType::inflate('enum-bool', '0'));
        self::assertEquals(true, EntityAttributeType::inflate('enum-bool', '1'));
    }

    public function test_maps_int_from_string(): void
    {
        $val = mt_rand();

        self::assertEquals($val, EntityAttributeType::inflate('int', sprintf('%d', $val)));
    }

    public function test_maps_flot_from_string(): void
    {
        $val = random_int(500, 5000) / random_int(1, 1000);

        self::assertEqualsWithDelta($val, EntityAttributeType::inflate('float', sprintf('%f', $val)), 0.000001);
    }

    public function test_maps_string_from_string(): void
    {
        self::assertEquals('hello', EntityAttributeType::inflate('string', 'hello'));
    }

    public function test_maps_date_time_from_string(): void
    {
        $val = '2009-04-09 23:24:53';

        self::assertEquals($val, EntityAttributeType::inflate('datetime', $val)->format('Y-m-d H:i:s'));
    }

    public function test_maps_array_from_string(): void
    {
        $val = 'a:3:{i:0;s:6:"georgi";i:1;s:2:"is";i:2;s:7:"awesome";}';

        self::assertEquals(unserialize($val), EntityAttributeType::inflate('array', $val));
    }

    public function test_maps_json_from_string(): void
    {
        $val = '["For realz"]';

        self::assertEquals(json_decode($val), EntityAttributeType::inflate('json', $val));
    }

    public function test_maps_epoch_from_string(): void
    {
        $val = time();

        self::assertEquals($val, EntityAttributeType::inflate('epoch', sprintf('%s', $val))->getTimestamp());
    }

    public function test_throws_on_invalid_type(): void
    {
        $this->expectException(InvalidArgumentException::class);

        EntityAttributeType::inflate('someinvalidtype', mt_rand());
        EntityAttributeType::deflate('someinvalidtype', mt_rand());
    }

    public function test_throws_on_invalid_json(): void
    {
        $this->expectException(RuntimeException::class);

        EntityAttributeType::inflate('json', '["For realz",]');
        EntityAttributeType::deflate('json', base64_decode('iVBORw0K5ErkJggg=='));
    }

    public function test_throws_on_invalid_array(): void
    {
        $this->expectException(Exception::class);

        EntityAttributeType::inflate('array', 'a:0:{i:0;s:6:"georgi";i:1;s:2:"is";i:2;s:7:"awesome";}');
    }

    public function test_maps_bool_to_string(): void
    {
        self::assertEquals('0', EntityAttributeType::deflate('bool', false));
        self::assertEquals('1', EntityAttributeType::deflate('bool', true));
    }

    public function test_maps_enum_bool_to_string(): void
    {
        self::assertEquals('0', EntityAttributeType::deflate('enum-bool', false));
        self::assertEquals('1', EntityAttributeType::deflate('enum-bool', true));
    }

    public function test_maps_int_to_string(): void
    {
        $val = mt_rand();

        self::assertEquals(sprintf('%d', $val), EntityAttributeType::deflate('int', $val));
    }

    public function test_maps_float_to_string(): void
    {
        $val = random_int(500, 5000) / random_int(1, 1000);

        self::assertEquals(
            sprintf('%.7f', $val),
            sprintf('%.7f', EntityAttributeType::deflate('float', $val))
        );
    }

    public function test_maps_string_to_string(): void
    {
        self::assertEquals('hello', EntityAttributeType::deflate('string', 'hello'));
    }

    public function test_maps_date_time_to_string(): void
    {
        self::assertEquals(
            '2009-04-09 00:00:00',
            EntityAttributeType::deflate('datetime', new DateTime('2009-04-09'))
        );

        self::assertEquals(
            '2009-04-09 23:24:53',
            EntityAttributeType::deflate('datetime', new DateTime('2009-04-09 23:24:53'))
        );
    }

    public function test_maps_array_to_string(): void
    {
        self::assertEquals(
            'a:3:{i:0;s:6:"georgi";i:1;s:2:"is";i:2;s:7:"awesome";}',
            EntityAttributeType::deflate('array', ['georgi', 'is', 'awesome'])
        );
    }

    public function test_maps_json_to_string(): void
    {
        self::assertEquals(
            '["For realz"]',
            EntityAttributeType::deflate('json', ['For realz'])
        );
    }

    public function test_maps_epoch_to_string(): void
    {
        $val = new DateTime();

        self::assertEquals((string)$val->getTimestamp(), EntityAttributeType::deflate('epoch', $val));
    }
}
