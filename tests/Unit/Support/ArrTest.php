<?php

namespace Tests\Unit\Support;

use Tests\TestCase;
use App\Support\Arr;

/**
 * Test cases for the Arr helper class
 */
class ArrTest extends TestCase
{
    public function testAccessible()
    {
        $this->assertTrue(Arr::accessible([]));
        $this->assertTrue(Arr::accessible(['a' => 1]));
        $this->assertFalse(Arr::accessible('string'));
        $this->assertFalse(Arr::accessible(123));
    }
    
    public function testAdd()
    {
        $array = ['name' => 'John'];
        $result = Arr::add($array, 'age', 30);
        $this->assertEquals(['name' => 'John', 'age' => 30], $result);
        
        // Should not overwrite existing key
        $result = Arr::add($array, 'name', 'Jane');
        $this->assertEquals(['name' => 'John'], $result);
    }
    
    public function testCollapse()
    {
        $array = [['a', 'b'], ['c', 'd'], ['e', 'f']];
        $result = Arr::collapse($array);
        $this->assertEquals(['a', 'b', 'c', 'd', 'e', 'f'], $result);
    }
    
    public function testCrossJoin()
    {
        $result = Arr::crossJoin(['a', 'b'], [1, 2]);
        $expected = [['a', 1], ['a', 2], ['b', 1], ['b', 2]];
        $this->assertEquals($expected, $result);
    }
    
    public function testDivide()
    {
        $array = ['name' => 'John', 'age' => 30];
        [$keys, $values] = Arr::divide($array);
        $this->assertEquals(['name', 'age'], $keys);
        $this->assertEquals(['John', 30], $values);
    }
    
    public function testDot()
    {
        $array = ['user' => ['name' => 'John', 'age' => 30]];
        $result = Arr::dot($array);
        $expected = ['user.name' => 'John', 'user.age' => 30];
        $this->assertEquals($expected, $result);
    }
    
    public function testExcept()
    {
        $array = ['name' => 'John', 'age' => 30, 'city' => 'NYC'];
        $result = Arr::except($array, ['age']);
        $this->assertEquals(['name' => 'John', 'city' => 'NYC'], $result);
    }
    
    public function testExists()
    {
        $array = ['name' => 'John', 'age' => null];
        $this->assertTrue(Arr::exists($array, 'name'));
        $this->assertTrue(Arr::exists($array, 'age'));
        $this->assertFalse(Arr::exists($array, 'city'));
    }
    
    public function testFirst()
    {
        $array = [1, 2, 3, 4];
        $this->assertEquals(1, Arr::first($array));
        
        $result = Arr::first($array, function($value) {
            return $value > 2;
        });
        $this->assertEquals(3, $result);
        
        $this->assertEquals('default', Arr::first([], null, 'default'));
    }
    
    public function testLast()
    {
        $array = [1, 2, 3, 4];
        $this->assertEquals(4, Arr::last($array));
        
        $result = Arr::last($array, function($value) {
            return $value < 3;
        });
        $this->assertEquals(2, $result);
    }
    
    public function testFlatten()
    {
        $array = [1, [2, [3, 4]], 5];
        $result = Arr::flatten($array);
        $this->assertEquals([1, 2, 3, 4, 5], $result);
        
        $result = Arr::flatten($array, 1);
        $this->assertEquals([1, 2, [3, 4], 5], $result);
    }
    
    public function testForget()
    {
        $array = ['user' => ['name' => 'John', 'age' => 30]];
        Arr::forget($array, 'user.age');
        $this->assertEquals(['user' => ['name' => 'John']], $array);
    }
    
    public function testGet()
    {
        $array = ['user' => ['name' => 'John', 'age' => 30]];
        $this->assertEquals('John', Arr::get($array, 'user.name'));
        $this->assertEquals(30, Arr::get($array, 'user.age'));
        $this->assertEquals('default', Arr::get($array, 'user.city', 'default'));
    }
    
    public function testHas()
    {
        $array = ['user' => ['name' => 'John', 'age' => 30]];
        $this->assertTrue(Arr::has($array, 'user.name'));
        $this->assertTrue(Arr::has($array, ['user.name', 'user.age']));
        $this->assertFalse(Arr::has($array, 'user.city'));
        $this->assertFalse(Arr::has($array, ['user.name', 'user.city']));
    }
    
    public function testHasAny()
    {
        $array = ['user' => ['name' => 'John', 'age' => 30]];
        $this->assertTrue(Arr::hasAny($array, ['user.name', 'user.city']));
        $this->assertFalse(Arr::hasAny($array, ['user.city', 'user.country']));
    }
    
    public function testIsAssoc()
    {
        $this->assertTrue(Arr::isAssoc(['name' => 'John']));
        $this->assertFalse(Arr::isAssoc([1, 2, 3]));
        $this->assertTrue(Arr::isAssoc([1 => 'a', 0 => 'b']));
    }
    
    public function testOnly()
    {
        $array = ['name' => 'John', 'age' => 30, 'city' => 'NYC'];
        $result = Arr::only($array, ['name', 'age']);
        $this->assertEquals(['name' => 'John', 'age' => 30], $result);
    }
    
    public function testPluck()
    {
        $array = [
            ['name' => 'John', 'age' => 30],
            ['name' => 'Jane', 'age' => 25]
        ];
        
        $result = Arr::pluck($array, 'name');
        $this->assertEquals(['John', 'Jane'], $result);
        
        $result = Arr::pluck($array, 'age', 'name');
        $this->assertEquals(['John' => 30, 'Jane' => 25], $result);
    }
    
    public function testPull()
    {
        $array = ['name' => 'John', 'age' => 30];
        $value = Arr::pull($array, 'name');
        $this->assertEquals('John', $value);
        $this->assertEquals(['age' => 30], $array);
        
        $value = Arr::pull($array, 'city', 'default');
        $this->assertEquals('default', $value);
    }
    
    public function testRandom()
    {
        $array = [1, 2, 3, 4, 5];
        $result = Arr::random($array);
        $this->assertContains($result, $array);
        
        $results = Arr::random($array, 2);
        $this->assertCount(2, $results);
        foreach ($results as $result) {
            $this->assertContains($result, $array);
        }
    }
    
    public function testSet()
    {
        $array = [];
        Arr::set($array, 'user.name', 'John');
        $this->assertEquals(['user' => ['name' => 'John']], $array);
        
        Arr::set($array, 'user.age', 30);
        $this->assertEquals(['user' => ['name' => 'John', 'age' => 30]], $array);
    }
    
    public function testShuffle()
    {
        $array = [1, 2, 3, 4, 5];
        $result = Arr::shuffle($array);
        $this->assertCount(5, $result);
        $this->assertEquals(sort($array), sort($result));
    }
    
    public function testSort()
    {
        $array = [3, 1, 4, 1, 5];
        $result = Arr::sort($array);
        $this->assertEquals([1, 1, 3, 4, 5], array_values($result));
        
        $array = [
            ['name' => 'John', 'age' => 30],
            ['name' => 'Jane', 'age' => 25]
        ];
        $result = Arr::sort($array, function($item) {
            return $item['age'];
        });
        $this->assertEquals('Jane', $result[0]['name']);
        $this->assertEquals('John', $result[1]['name']);
    }
    
    public function testSortRecursive()
    {
        $array = [
            'users' => [
                ['name' => 'John'],
                ['name' => 'Jane']
            ],
            'admins' => [
                ['name' => 'Bob'],
                ['name' => 'Alice']
            ]
        ];
        
        $result = Arr::sortRecursive($array);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('users', $result);
        $this->assertArrayHasKey('admins', $result);
    }
    
    public function testWhere()
    {
        $array = [
            ['name' => 'John', 'age' => 30],
            ['name' => 'Jane', 'age' => 25],
            ['name' => 'Bob', 'age' => 30]
        ];
        
        $result = Arr::where($array, function($item) {
            return $item['age'] === 30;
        });
        
        $this->assertCount(2, $result);
    }
    
    public function testWhereNotNull()
    {
        $array = ['name' => 'John', 'age' => null, 'city' => 'NYC'];
        $result = Arr::whereNotNull($array);
        $this->assertEquals(['name' => 'John', 'city' => 'NYC'], $result);
    }
    
    public function testWrap()
    {
        $this->assertEquals(['test'], Arr::wrap('test'));
        $this->assertEquals([1, 2, 3], Arr::wrap([1, 2, 3]));
        $this->assertEquals([null], Arr::wrap(null));
    }
}