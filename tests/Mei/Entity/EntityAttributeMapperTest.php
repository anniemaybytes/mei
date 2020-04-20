<?php

declare(strict_types=1);

use Mei\Cache\EntityCache;
use Mei\Entity\EntityAttributeMapper;
use Mei\Entity\ICacheable;

/**
 * Class EntityAttributeMapperTest
 */
class EntityAttributeMapperTest extends PHPUnit\Framework\TestCase
{
    public function testGet(): void
    {
        $attr = [
            'A' => 'string',
            'B' => 'string',
        ];

        $defaults = [
            'A' => 'A',
            'B' => 'B'
        ];

        $c = $this->getMockBuilder(EntityCache::class)->disableOriginalConstructor()->getMock();
        $c->method('getRow')->willReturn([]);
        $m = new EntityAttributeMapper($attr, $defaults);

        /** @var ICacheable $c */
        $this->assertEquals('A', $m->get($c, 'A'));
        $this->assertEquals('B', $m->get($c, 'B'));

        $c = $this->getMockBuilder(EntityCache::class)->disableOriginalConstructor()->getMock();
        $c->method('getRow')->willReturn(['A' => 'C', 'B' => 'D']);
        $m = new EntityAttributeMapper($attr, $defaults);

        /** @var ICacheable $c */
        $this->assertEquals('C', $m->get($c, 'A'));
        $this->assertEquals('D', $m->get($c, 'B'));

        $c = $this->getMockBuilder(EntityCache::class)->disableOriginalConstructor()->getMock();
        $c->method('getRow')->willReturn(['A' => 'C']);
        $m = new EntityAttributeMapper($attr, $defaults);

        /** @var ICacheable $c */
        $this->assertEquals('C', $m->get($c, 'A'));
        $this->assertEquals('B', $m->get($c, 'B'));
    }

    public function testIsSet(): void
    {
        $attr = [
            'A' => 'string',
            'B' => 'string',
        ];

        // first test getting when no row is present
        $c = $this->getMockBuilder(EntityCache::class)->disableOriginalConstructor()->getMock();
        $c->method('getRow')->willReturn([]);
        $m = new EntityAttributeMapper($attr);

        /** @var ICacheable $c */
        $this->assertFalse($m->isAttributeSet($c, 'A'));
        $this->assertFalse($m->isAttributeSet($c, 'B'));

        $m = new EntityAttributeMapper($attr, ['A' => 'A']);

        /** @var ICacheable $c */
        $this->assertTrue($m->isAttributeSet($c, 'A'));
        $this->assertFalse($m->isAttributeSet($c, 'B'));

        $m = new EntityAttributeMapper($attr, ['A' => 'A', 'B' => 'B']);

        /** @var ICacheable $c */
        $this->assertTrue($m->isAttributeSet($c, 'A'));
        $this->assertTrue($m->isAttributeSet($c, 'B'));

        $m = new EntityAttributeMapper($attr);
        $c = $this->getMockBuilder(EntityCache::class)->disableOriginalConstructor()->getMock();
        $c->method('getRow')->willReturn(['A' => 'A']);

        /** @var ICacheable $c */
        $this->assertTrue($m->isAttributeSet($c, 'A'));
        $this->assertFalse($m->isAttributeSet($c, 'B'));

        $c = $this->getMockBuilder(EntityCache::class)->disableOriginalConstructor()->getMock();
        $c->method('getRow')->willReturn(['A' => 'A', 'B' => 'B']);

        $this->assertTrue($m->isAttributeSet($c, 'A'));
        $this->assertTrue($m->isAttributeSet($c, 'B'));
    }

    public function testSet(): void
    {
        $attr = [
            'A' => 'string',
            'B' => 'string',
        ];

        $c = $this->getMockBuilder(EntityCache::class)->disableOriginalConstructor()->getMock();
        $c->method('getRow')->willReturn([]);
        // should actually return EntityCache, but just test that it works
        $c->method('setRow')->with($this->equalTo(['A' => 'A']))->willReturn($c);
        $m = new EntityAttributeMapper($attr);

        /** @var ICacheable $c */
        $r = $m->set($c, 'A', 'A');
        /** @noinspection PhpUnitTestsInspection */
        $this->assertTrue($r instanceof $c);

        $c = $this->getMockBuilder(EntityCache::class)->disableOriginalConstructor()->getMock();
        $c->method('getRow')->willReturn(['A' => 'A']);
        // should actually return EntityCache, but just test that it works
        $c->method('setRow')->with($this->equalTo(['A' => 'A', 'B' => 'B']))->willReturn($c);
        $m = new EntityAttributeMapper($attr);

        $r = $m->set($c, 'B', 'B');
        /** @noinspection PhpUnitTestsInspection */
        $this->assertTrue($r instanceof $c);
    }

    public function testUnset(): void
    {
        $attr = [
            'A' => 'string',
            'B' => 'string',
        ];

        $c = $this->getMockBuilder(EntityCache::class)->disableOriginalConstructor()->getMock();
        $c->method('getRow')->willReturn(['A' => 'A', 'B' => 'B']);
        $c->method('setRow')->with($this->equalTo(['A' => 'A']))->willReturn(
            $c
        ); // should actually return EntityCache, but just test that it works
        $m = new EntityAttributeMapper($attr);

        /** @var ICacheable $c */
        $r = $m->unsetAttribute($c, 'B');
        /** @noinspection PhpUnitTestsInspection */
        $this->assertTrue($r instanceof $c);
    }

    public function testInvalidGet(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Tried to get unknown key name 'C' - not in allowed attributes");

        $attr = [
            'A' => 'string',
            'B' => 'string',
        ];

        // first test getting when no row is present
        $c = $this->getMockBuilder(EntityCache::class)->disableOriginalConstructor()->getMock();
        $c->method('getRow')->willReturn([]);
        $m = new EntityAttributeMapper($attr);

        /** @var ICacheable $c */
        $m->get($c, 'C');
    }

    public function testInvalidSet(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Tried to set unknown key name 'C'");

        $attr = [
            'A' => 'string',
            'B' => 'string',
        ];

        // first test getting when no row is present
        $c = $this->getMockBuilder(EntityCache::class)->disableOriginalConstructor()->getMock();
        $c->method('getRow')->willReturn([]);
        $m = new EntityAttributeMapper($attr);

        /** @var ICacheable $c */
        $m->set($c, 'C', mt_rand());
    }

    public function testUnsetAccess(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Tried to get attribute that hasn't been set");

        $attr = [
            'A' => 'string',
            'B' => 'string',
        ];

        // first test getting when no row is present
        $c = $this->getMockBuilder(EntityCache::class)->disableOriginalConstructor()->getMock();
        $c->method('getRow')->willReturn([]);
        $m = new EntityAttributeMapper($attr);

        /** @var ICacheable $c */
        $m->get($c, 'A');
    }

    public function testGetChangedValues(): void
    {
        $attr = [
            'A' => 'string',
            'B' => 'string',
        ];

        $noVals = $this->getMockBuilder(EntityCache::class)->disableOriginalConstructor()->getMock();
        $noVals->method('getRow')->willReturn([]);

        $onlyA = $this->getMockBuilder(EntityCache::class)->disableOriginalConstructor()->getMock();
        $onlyA->method('getRow')->willReturn(['A' => 'A']);

        $bAndA = $this->getMockBuilder(EntityCache::class)->disableOriginalConstructor()->getMock();
        $bAndA->method('getRow')->willReturn(['A' => 'A', 'B' => 'B']);

        $m = new EntityAttributeMapper($attr);

        /**
         * @var ICacheable $noVals
         * @var ICacheable $onlyA
         * @var ICacheable $bAndA
         */
        $this->assertEquals([], $m->getChangedValues($noVals));

        $m->set($noVals, 'A', 'A');
        $this->assertEquals(['A' => 'A'], $m->getChangedValues($onlyA));

        $m->set($onlyA, 'B', 'B');

        $this->assertEquals(['A' => 'A', 'B' => 'B'], $m->getChangedValues($bAndA));

        $m = new EntityAttributeMapper($attr);

        $m->set($bAndA, 'A', 'A');
        $this->assertEquals([], $m->getChangedValues($bAndA));

        $m->set($bAndA, 'B', 'B');
        $this->assertEquals([], $m->getChangedValues($bAndA));

        $m = new EntityAttributeMapper($attr);

        $m->set($onlyA, 'A', 'A');
        $this->assertEquals([], $m->getChangedValues($onlyA));

        $m->set($onlyA, 'B', 'B');

        $this->assertEquals(['B' => 'B'], $m->getChangedValues($bAndA));


        $m = new EntityAttributeMapper($attr, ['B' => 'B']);

        $m->set($noVals, 'A', 'A');
        $this->assertEquals(['A' => 'A'], $m->getChangedValues($bAndA));

        $m->set($noVals, 'B', 'B');

        // even though default val is set, it is still considered changed
        $this->assertEquals(['A' => 'A', 'B' => 'B'], $m->getChangedValues($bAndA));
    }

    public function testGetValues(): void
    {
        $attr = [
            'A' => 'string',
            'B' => 'string',
        ];

        $noVals = $this->getMockBuilder(EntityCache::class)->disableOriginalConstructor()->getMock();
        $noVals->method('getRow')->willReturn([]);

        $onlyA = $this->getMockBuilder(EntityCache::class)->disableOriginalConstructor()->getMock();
        $onlyA->method('getRow')->willReturn(['A' => 'A']);

        $bAndA = $this->getMockBuilder(EntityCache::class)->disableOriginalConstructor()->getMock();
        $bAndA->method('getRow')->willReturn(['A' => 'A', 'B' => 'B']);

        $m = new EntityAttributeMapper($attr, ['A' => 'C', 'B' => 'D']);

        /**
         * @var ICacheable $noVals
         * @var ICacheable $onlyA
         * @var ICacheable $bAndA
         */
        $this->assertEquals(['A' => 'C', 'B' => 'D'], $m->getValues($noVals));
        $this->assertEquals(['A' => 'A', 'B' => 'D'], $m->getValues($onlyA));
        $this->assertEquals(['A' => 'A', 'B' => 'B'], $m->getValues($bAndA));
    }
}
