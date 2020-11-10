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
        self::assertEquals(false, EntityAttributeType::fromTo('bool', '0'));
        self::assertEquals(true, EntityAttributeType::fromTo('bool', '1'));
    }

    public function test_maps_enum_bool_from_string(): void
    {
        self::assertEquals(false, EntityAttributeType::fromTo('enum-bool', '0'));
        self::assertEquals(true, EntityAttributeType::fromTo('enum-bool', '1'));
    }

    public function test_maps_int_from_string(): void
    {
        $val = mt_rand();

        self::assertEquals($val, EntityAttributeType::fromTo('int', sprintf('%d', $val)));
    }

    public function test_maps_flot_from_string(): void
    {
        $val = random_int(500, 5000) / random_int(1, 1000);

        self::assertEqualsWithDelta($val, EntityAttributeType::fromTo('float', sprintf('%f', $val)), 0.000001);
    }

    public function test_maps_string_from_string(): void
    {
        self::assertEquals('hello', EntityAttributeType::fromTo('string', 'hello'));
    }

    public function test_maps_date_time_from_string(): void
    {
        $val = '2009-04-09 23:24:53';

        self::assertEquals($val, EntityAttributeType::fromTo('datetime', $val)->format('Y-m-d H:i:s'));
    }

    public function test_maps_array_from_string(): void
    {
        $val = 'a:3:{i:0;s:6:"georgi";i:1;s:2:"is";i:2;s:7:"awesome";}';

        self::assertEquals(unserialize($val), EntityAttributeType::fromTo('array', $val));
    }

    public function test_maps_json_from_string(): void
    {
        $val = '["For realz"]';

        self::assertEquals(json_decode($val), EntityAttributeType::fromTo('json', $val));
    }

    public function test_maps_epoch_from_string(): void
    {
        $val = time();

        self::assertEquals($val, EntityAttributeType::fromTo('epoch', sprintf('%s', $val))->getTimestamp());
    }

    public function test_throws_on_invalid_type(): void
    {
        $this->expectException(InvalidArgumentException::class);

        EntityAttributeType::fromTo('someinvalidtype', mt_rand());
        EntityAttributeType::toString('someinvalidtype', mt_rand());
    }

    public function test_throws_on_invalid_json(): void
    {
        $this->expectException(RuntimeException::class);

        EntityAttributeType::fromTo('json', '["For realz",]');
        EntityAttributeType::toString('json', base64_decode('iVBORw0K5ErkJggg=='));
    }

    public function test_throws_on_invalid_array(): void
    {
        $this->expectException(Exception::class);

        EntityAttributeType::fromTo('array', 'a:0:{i:0;s:6:"georgi";i:1;s:2:"is";i:2;s:7:"awesome";}');
    }

    public function test_maps_bool_to_string(): void
    {
        self::assertEquals('0', EntityAttributeType::toString('bool', false));
        self::assertEquals('1', EntityAttributeType::toString('bool', true));
    }

    public function test_maps_enum_bool_to_string(): void
    {
        self::assertEquals('0', EntityAttributeType::toString('enum-bool', false));
        self::assertEquals('1', EntityAttributeType::toString('enum-bool', true));
    }

    public function test_maps_int_to_string(): void
    {
        $val = mt_rand();

        self::assertEquals(sprintf('%d', $val), EntityAttributeType::toString('int', $val));
    }

    public function test_maps_float_to_string(): void
    {
        $val = random_int(500, 5000) / random_int(1, 1000);

        self::assertEquals(
            sprintf('%.7f', $val),
            sprintf('%.7f', EntityAttributeType::toString('float', $val))
        );
    }

    public function test_maps_string_to_string(): void
    {
        self::assertEquals('hello', EntityAttributeType::toString('string', 'hello'));
    }

    public function test_maps_date_time_to_string(): void
    {
        self::assertEquals(
            '2009-04-09 00:00:00',
            EntityAttributeType::toString('datetime', new DateTime('2009-04-09'))
        );

        self::assertEquals(
            '2009-04-09 23:24:53',
            EntityAttributeType::toString('datetime', new DateTime('2009-04-09 23:24:53'))
        );
    }

    public function test_maps_array_to_string(): void
    {
        self::assertEquals(
            'a:3:{i:0;s:6:"georgi";i:1;s:2:"is";i:2;s:7:"awesome";}',
            EntityAttributeType::toString('array', ['georgi', 'is', 'awesome'])
        );
    }

    public function test_maps_json_to_string(): void
    {
        self::assertEquals(
            '["For realz"]',
            EntityAttributeType::toString('json', ['For realz'])
        );
    }

    public function test_maps_epoch_to_string(): void
    {
        $val = new DateTime();

        self::assertEquals((string)$val->getTimestamp(), EntityAttributeType::toString('epoch', $val));
    }
}
