<?php

/**
 * This file is part of the Simple Serializer.
 *
 * Copyright (c) 2012 Farheap Solutions (http://www.farheap.com)
 *
 * For the full copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Opensoft\SimpleSerializer\Tests\Adapter;

use Opensoft\SimpleSerializer\Tests\Metadata\Driver\Fixture\A\A;
use Opensoft\SimpleSerializer\Tests\Metadata\Driver\Fixture\A\AChildren;
use Opensoft\SimpleSerializer\Tests\Metadata\Driver\Fixture\A\D;
use Opensoft\SimpleSerializer\Tests\Metadata\Driver\Fixture\A\E;
use Opensoft\SimpleSerializer\Tests\Metadata\Driver\Fixture\A\Recursion;
use Opensoft\SimpleSerializer\Adapter\ArrayAdapterInterface;
use Opensoft\SimpleSerializer\Metadata\MetadataFactory;
use DateTime;

/**
 * @author Dmitry Petrov <dmitry.petrov@opensoftdev.ru>
 */
class ArrayAdapterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ArrayAdapterInterface;
     */
    private $unitUnderTest;

    private $metadataFactory;

    public function testToArray()
    {
        $object = new A();
        $object->setRid(2)
            ->setName('testName')
            ->setStatus(true)
            ->setHiddenStatus(false);
        $result = $this->unitUnderTest->toArray($object);
        $this->assertCount(3, $result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('status', $result);
        $this->assertEquals(2, $result['id']);
        $this->assertEquals('testName', $result['name']);
        $this->assertTrue($result['status']);

        $object = new AChildren();
        $object->setRid(3)
            ->setName('children')
            ->setStatus(false)
            ->setHiddenStatus(true);
        $object->setFloat(3.23)
            ->setArray(array(3,2,44))
            ->setAssocArray(array('true' => 345, 'false' => 34));
        $time = time();
        $object->setDateTime(new DateTime(date('Y-m-d H:i:s', $time)));
        $object->setNull(null);
        $result = $this->unitUnderTest->toArray($object);
        $this->assertCount(8, $result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('float', $result);
        $this->assertArrayHasKey('null', $result);
        $this->assertArrayHasKey('dateTime', $result);
        $this->assertArrayHasKey('array', $result);
        $this->assertArrayHasKey('assocArray', $result);
        $this->assertEquals(3, $result['id']);
        $this->assertEquals('children', $result['name']);
        $this->assertFalse($result['status']);
        $this->assertNull($result['null']);
        $this->assertEquals(3.23, $result['float']);
        $testTime = new DateTime(date('Y-m-d H:i:s', $time));
        $this->assertEquals($testTime->format(DateTime::ISO8601), $result['dateTime']);
        $this->assertEquals(array(3,2,44), $result['array']);
        $this->assertEquals(array('true' => 345, 'false' => 34), $result['assocArray']);

        $objectComplex = new E();
        $objectComplex->setRid(434);
        $objectComplex->setObject($object);
        $objectComplex->setArrayOfObjects(array($object, $object));
        $resultComplex = $this->unitUnderTest->toArray($objectComplex);
        $this->assertCount(3, $resultComplex);
        $this->assertArrayHasKey('object', $resultComplex);
        $this->assertEquals($result, $resultComplex['object']);
        $this->assertArrayHasKey('arrayOfObjects', $resultComplex);
        $this->assertEquals(array($result, $result), $resultComplex['arrayOfObjects']);
    }

    /**
     * @expectedException \Opensoft\SimpleSerializer\Exception\RecursionException
     */
    public function testToArrayRecursionException()
    {
        $objectComplex = new Recursion();
        $objectComplex->setObject($objectComplex);
        $this->unitUnderTest->toArray($objectComplex);
    }

    /**
     * @expectedException \Opensoft\SimpleSerializer\Exception\InvalidArgumentException
     */
    public function testUndefined()
    {
        $object = new D();
        $object->setRid(4);
        $object->setName('test');
        $this->unitUnderTest->toArray($object);
    }

    public function testToObject()
    {
        $object = new E();
        $array = array(
            'rid' => 23,
            'object' => array(
                'id' => 3,
                'name' => 'test',
                'status' => true,
                'hiddenStatus' => false,
                'float' => 3.23,
                'null' => null,
                'array' => array(23, 24),
                'assocArray' => array('str' => 34),
                'dateTime' => '2005-08-15T15:52:01+0000'
            ),
            'arrayOfObjects' => array(
                array(
                    'id' => 3,
                    'name' => 'test',
                    'status' => true,
                    'hiddenStatus' => false,
                    'float' => 3.23,
                    'null' => null,
                    'array' => array(23, 24),
                    'assocArray' => array('str' => 34),
                    'dateTime' => '2005-08-15T15:52:01+0000'
                )
            )
        );
        $result = $this->unitUnderTest->toObject($array, $object);

        $this->assertInstanceOf('Opensoft\SimpleSerializer\Tests\Metadata\Driver\Fixture\A\E', $result);
        $this->assertInstanceOf('Opensoft\SimpleSerializer\Tests\Metadata\Driver\Fixture\A\AChildren', $result->getObject());
        $objects = $result->getArrayOfObjects();
        $this->assertCount(1, $objects);
        $this->assertInstanceOf('Opensoft\SimpleSerializer\Tests\Metadata\Driver\Fixture\A\AChildren', $objects[0]);
        $this->assertEquals(23, $result->getRid());
        $this->assertEquals(3, $result->getObject()->getRid());
        $this->assertEquals('test', $result->getObject()->getName());
        $this->assertTrue($result->getObject()->getStatus());
        $this->assertNull($result->getObject()->getHiddenStatus());
        $this->assertEquals(3.23, $result->getObject()->getFloat());
        $this->assertNull($result->getObject()->getNull());
        $arrayA = $result->getObject()->getArray();
        $this->assertCount(2, $arrayA);
        $this->assertEquals(23, $arrayA[0]);
        $this->assertEquals(24, $arrayA[1]);
        $arrayAssoc = $result->getObject()->getAssocArray();
        $this->assertCount(1, $arrayAssoc);
        $this->assertArrayHasKey('str', $arrayAssoc);
        $this->assertEquals(34, $arrayAssoc['str']);
        $this->assertInstanceOf('\DateTime', $result->getObject()->getDateTime());
        $this->assertEquals('2005-08-15T15:52:01+0000', $result->getObject()->getDateTime()->format(DateTime::ISO8601));
    }

    /**
     * @group Test
     */
    public function testToObjectWithData()
    {
        $object = new E();
        $objectA = new AChildren();
        $objectA->setRid(11);
        $objectA->setName(23);
        $objectA->setHiddenStatus(true);
        $object->setRid(2);
        $object->setObject($objectA);
        $object->setArrayOfObjects(array($objectA));
        $array = array(
            'rid' => 23,
            'object' => array(
                'id' => 3
            ),
            'arrayOfObjects' => array(
                array(
                    'id' => 3
                )
            )
        );
        $result = $this->unitUnderTest->toObject($array, $object);

        $this->assertInstanceOf('Opensoft\SimpleSerializer\Tests\Metadata\Driver\Fixture\A\E', $result);
        $this->assertInstanceOf('Opensoft\SimpleSerializer\Tests\Metadata\Driver\Fixture\A\AChildren', $result->getObject());
        $objects = $result->getArrayOfObjects();
        $this->assertCount(1, $objects);
        $this->assertInstanceOf('Opensoft\SimpleSerializer\Tests\Metadata\Driver\Fixture\A\AChildren', $objects[0]);
        $this->assertEquals(23, $result->getRid());
        $this->assertEquals(3, $result->getObject()->getRid());
        $this->assertEquals(23, $result->getObject()->getName());
        $this->assertTrue($result->getObject()->getHiddenStatus());
        $this->assertEquals(3, $objects[0]->getRid());
        $this->assertEquals(23, $objects[0]->getName());
        $this->assertTrue($objects[0]->getHiddenStatus());
    }

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $locator = $this->getMockForAbstractClass(
            'Opensoft\SimpleSerializer\Metadata\Driver\FileLocator',
            array(
                array(
                    'Opensoft\SimpleSerializer\Tests\Metadata\Driver\Fixture\A' => __DIR__ . '/../Metadata/Driver/Fixture/A',
                    'Opensoft\SimpleSerializer\Tests\Metadata\Driver\Fixture\B' => __DIR__ . '/../Metadata/Driver/Fixture/B'
                )
            )
        );

        $driver = $this->getMockForAbstractClass(
            'Opensoft\SimpleSerializer\Metadata\Driver\YamlDriver',
            array($locator)
        );
        $this->metadataFactory = new MetadataFactory($driver);
        $this->unitUnderTest = $this->getMockForAbstractClass('\Opensoft\SimpleSerializer\Adapter\ArrayAdapter', array($this->metadataFactory));
    }
}