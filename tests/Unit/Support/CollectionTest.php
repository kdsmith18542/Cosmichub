<?php

namespace Tests\Unit\Support;

use Tests\TestCase;
use App\Support\Collection;

/**
 * Test cases for the Collection class
 */
class CollectionTest extends TestCase
{
    public function testConstructor()
    {
        $collection = new Collection([1, 2, 3]);
        $this->assertEquals([1, 2, 3], $collection->all());
        
        $collection = new Collection();
        $this->assertEquals([], $collection->all());
    }
    
    public function testAll()
    {
        $collection = new Collection([1, 2, 3]);
        $this->assertEquals([1, 2, 3], $collection->all());
    }
    
    public function testAverage()
    {
        $collection = new Collection([1, 2, 3, 4, 5]);
        $this->assertEquals(3, $collection->average());
        
        $collection = new Collection([
            ['score' => 10],
            ['score' => 20],
            ['score' => 30]
        ]);
        $this->assertEquals(20, $collection->average('score'));
    }
    
    public function testChunk()
    {
        $collection = new Collection([1, 2, 3, 4, 5]);
        $chunks = $collection->chunk(2);
        
        $this->assertInstanceOf(Collection::class, $chunks);
        $this->assertEquals(3, $chunks->count());
        $this->assertEquals([1, 2], $chunks->first()->all());
        $this->assertEquals([5], $chunks->last()->all());
    }
    
    public function testCollapse()
    {
        $collection = new Collection([[1, 2], [3, 4], [5]]);
        $collapsed = $collection->collapse();
        
        $this->assertEquals([1, 2, 3, 4, 5], $collapsed->all());
    }
    
    public function testCombine()
    {
        $collection = new Collection(['name', 'age']);
        $combined = $collection->combine(['John', 30]);
        
        $this->assertEquals(['name' => 'John', 'age' => 30], $combined->all());
    }
    
    public function testConcat()
    {
        $collection = new Collection([1, 2]);
        $concatenated = $collection->concat([3, 4])->concat(new Collection([5, 6]));
        
        $this->assertEquals([1, 2, 3, 4, 5, 6], $concatenated->all());
    }
    
    public function testContains()
    {
        $collection = new Collection([1, 2, 3]);
        $this->assertTrue($collection->contains(2));
        $this->assertFalse($collection->contains(4));
        
        $collection = new Collection([
            ['name' => 'John'],
            ['name' => 'Jane']
        ]);
        $this->assertTrue($collection->contains('name', 'John'));
        $this->assertFalse($collection->contains('name', 'Bob'));
        
        $this->assertTrue($collection->contains(function($item) {
            return $item['name'] === 'Jane';
        }));
    }
    
    public function testCount()
    {
        $collection = new Collection([1, 2, 3]);
        $this->assertEquals(3, $collection->count());
        
        $collection = new Collection();
        $this->assertEquals(0, $collection->count());
    }
    
    public function testCountBy()
    {
        $collection = new Collection(['alice@example.com', 'bob@example.com', 'charlie@gmail.com']);
        $counted = $collection->countBy(function($email) {
            return substr(strrchr($email, '@'), 1);
        });
        
        $this->assertEquals(['example.com' => 2, 'gmail.com' => 1], $counted->all());
    }
    
    public function testDiff()
    {
        $collection = new Collection([1, 2, 3, 4]);
        $diff = $collection->diff([2, 4]);
        
        $this->assertEquals([0 => 1, 2 => 3], $diff->all());
    }
    
    public function testEach()
    {
        $collection = new Collection([1, 2, 3]);
        $result = [];
        
        $collection->each(function($item, $key) use (&$result) {
            $result[$key] = $item * 2;
        });
        
        $this->assertEquals([0 => 2, 1 => 4, 2 => 6], $result);
    }
    
    public function testEvery()
    {
        $collection = new Collection([2, 4, 6]);
        $this->assertTrue($collection->every(function($item) {
            return $item % 2 === 0;
        }));
        
        $collection = new Collection([1, 2, 3]);
        $this->assertFalse($collection->every(function($item) {
            return $item % 2 === 0;
        }));
    }
    
    public function testExcept()
    {
        $collection = new Collection(['name' => 'John', 'age' => 30, 'city' => 'NYC']);
        $result = $collection->except(['age']);
        
        $this->assertEquals(['name' => 'John', 'city' => 'NYC'], $result->all());
    }
    
    public function testFilter()
    {
        $collection = new Collection([1, 2, 3, 4, 5]);
        $filtered = $collection->filter(function($item) {
            return $item > 3;
        });
        
        $this->assertEquals([3 => 4, 4 => 5], $filtered->all());
        
        // Test filter without callback (removes falsy values)
        $collection = new Collection([1, 0, '', null, false, 2]);
        $filtered = $collection->filter();
        $this->assertEquals([0 => 1, 5 => 2], $filtered->all());
    }
    
    public function testFirst()
    {
        $collection = new Collection([1, 2, 3]);
        $this->assertEquals(1, $collection->first());
        
        $collection = new Collection([1, 2, 3, 4]);
        $this->assertEquals(3, $collection->first(function($item) {
            return $item > 2;
        }));
        
        $collection = new Collection();
        $this->assertEquals('default', $collection->first(null, 'default'));
    }
    
    public function testFlatMap()
    {
        $collection = new Collection([
            ['name' => 'John', 'hobbies' => ['reading', 'gaming']],
            ['name' => 'Jane', 'hobbies' => ['cooking', 'traveling']]
        ]);
        
        $hobbies = $collection->flatMap(function($person) {
            return $person['hobbies'];
        });
        
        $this->assertEquals(['reading', 'gaming', 'cooking', 'traveling'], $hobbies->all());
    }
    
    public function testFlatten()
    {
        $collection = new Collection([[1, 2], [3, [4, 5]]]);
        $flattened = $collection->flatten();
        
        $this->assertEquals([1, 2, 3, 4, 5], $flattened->all());
        
        $collection = new Collection([[1, 2], [3, [4, 5]]]);
        $flattened = $collection->flatten(1);
        
        $this->assertEquals([1, 2, 3, [4, 5]], $flattened->all());
    }
    
    public function testFlip()
    {
        $collection = new Collection(['name' => 'John', 'age' => 30]);
        $flipped = $collection->flip();
        
        $this->assertEquals(['John' => 'name', 30 => 'age'], $flipped->all());
    }
    
    public function testForget()
    {
        $collection = new Collection(['name' => 'John', 'age' => 30]);
        $collection->forget('age');
        
        $this->assertEquals(['name' => 'John'], $collection->all());
    }
    
    public function testGet()
    {
        $collection = new Collection(['name' => 'John', 'age' => 30]);
        $this->assertEquals('John', $collection->get('name'));
        $this->assertEquals('default', $collection->get('city', 'default'));
    }
    
    public function testGroupBy()
    {
        $collection = new Collection([
            ['name' => 'John', 'department' => 'IT'],
            ['name' => 'Jane', 'department' => 'HR'],
            ['name' => 'Bob', 'department' => 'IT']
        ]);
        
        $grouped = $collection->groupBy('department');
        
        $this->assertInstanceOf(Collection::class, $grouped);
        $this->assertTrue($grouped->has('IT'));
        $this->assertTrue($grouped->has('HR'));
        $this->assertEquals(2, $grouped->get('IT')->count());
        $this->assertEquals(1, $grouped->get('HR')->count());
    }
    
    public function testHas()
    {
        $collection = new Collection(['name' => 'John', 'age' => 30]);
        $this->assertTrue($collection->has('name'));
        $this->assertFalse($collection->has('city'));
    }
    
    public function testImplode()
    {
        $collection = new Collection([1, 2, 3]);
        $this->assertEquals('1,2,3', $collection->implode(','));
        
        $collection = new Collection([
            ['name' => 'John'],
            ['name' => 'Jane']
        ]);
        $this->assertEquals('John,Jane', $collection->implode('name', ','));
    }
    
    public function testIntersect()
    {
        $collection = new Collection([1, 2, 3, 4]);
        $intersected = $collection->intersect([2, 3, 5]);
        
        $this->assertEquals([1 => 2, 2 => 3], $intersected->all());
    }
    
    public function testIsEmpty()
    {
        $collection = new Collection();
        $this->assertTrue($collection->isEmpty());
        
        $collection = new Collection([1]);
        $this->assertFalse($collection->isEmpty());
    }
    
    public function testIsNotEmpty()
    {
        $collection = new Collection([1]);
        $this->assertTrue($collection->isNotEmpty());
        
        $collection = new Collection();
        $this->assertFalse($collection->isNotEmpty());
    }
    
    public function testKeys()
    {
        $collection = new Collection(['name' => 'John', 'age' => 30]);
        $keys = $collection->keys();
        
        $this->assertEquals(['name', 'age'], $keys->all());
    }
    
    public function testLast()
    {
        $collection = new Collection([1, 2, 3]);
        $this->assertEquals(3, $collection->last());
        
        $collection = new Collection([1, 2, 3, 4]);
        $this->assertEquals(2, $collection->last(function($item) {
            return $item < 3;
        }));
        
        $collection = new Collection();
        $this->assertEquals('default', $collection->last(null, 'default'));
    }
    
    public function testMap()
    {
        $collection = new Collection([1, 2, 3]);
        $mapped = $collection->map(function($item) {
            return $item * 2;
        });
        
        $this->assertEquals([2, 4, 6], $mapped->all());
    }
    
    public function testMapWithKeys()
    {
        $collection = new Collection([
            ['name' => 'John', 'age' => 30],
            ['name' => 'Jane', 'age' => 25]
        ]);
        
        $mapped = $collection->mapWithKeys(function($item) {
            return [$item['name'] => $item['age']];
        });
        
        $this->assertEquals(['John' => 30, 'Jane' => 25], $mapped->all());
    }
    
    public function testMax()
    {
        $collection = new Collection([1, 3, 2, 5, 4]);
        $this->assertEquals(5, $collection->max());
        
        $collection = new Collection([
            ['score' => 10],
            ['score' => 30],
            ['score' => 20]
        ]);
        $this->assertEquals(30, $collection->max('score'));
    }
    
    public function testMedian()
    {
        $collection = new Collection([1, 2, 3, 4, 5]);
        $this->assertEquals(3, $collection->median());
        
        $collection = new Collection([1, 2, 3, 4]);
        $this->assertEquals(2.5, $collection->median());
    }
    
    public function testMerge()
    {
        $collection = new Collection([1, 2]);
        $merged = $collection->merge([3, 4]);
        
        $this->assertEquals([1, 2, 3, 4], $merged->all());
    }
    
    public function testMin()
    {
        $collection = new Collection([3, 1, 4, 2, 5]);
        $this->assertEquals(1, $collection->min());
        
        $collection = new Collection([
            ['score' => 30],
            ['score' => 10],
            ['score' => 20]
        ]);
        $this->assertEquals(10, $collection->min('score'));
    }
    
    public function testMode()
    {
        $collection = new Collection([1, 2, 2, 3, 3, 3]);
        $mode = $collection->mode();
        
        $this->assertEquals([3], $mode->all());
    }
    
    public function testOnly()
    {
        $collection = new Collection(['name' => 'John', 'age' => 30, 'city' => 'NYC']);
        $result = $collection->only(['name', 'age']);
        
        $this->assertEquals(['name' => 'John', 'age' => 30], $result->all());
    }
    
    public function testPad()
    {
        $collection = new Collection([1, 2]);
        $padded = $collection->pad(5, 0);
        
        $this->assertEquals([1, 2, 0, 0, 0], $padded->all());
        
        $padded = $collection->pad(-5, 0);
        $this->assertEquals([0, 0, 0, 1, 2], $padded->all());
    }
    
    public function testPartition()
    {
        $collection = new Collection([1, 2, 3, 4, 5]);
        [$evens, $odds] = $collection->partition(function($item) {
            return $item % 2 === 0;
        });
        
        $this->assertEquals([1 => 2, 3 => 4], $evens->all());
        $this->assertEquals([0 => 1, 2 => 3, 4 => 5], $odds->all());
    }
    
    public function testPipe()
    {
        $collection = new Collection([1, 2, 3]);
        $result = $collection->pipe(function($collection) {
            return $collection->sum();
        });
        
        $this->assertEquals(6, $result);
    }
    
    public function testPluck()
    {
        $collection = new Collection([
            ['name' => 'John', 'age' => 30],
            ['name' => 'Jane', 'age' => 25]
        ]);
        
        $names = $collection->pluck('name');
        $this->assertEquals(['John', 'Jane'], $names->all());
        
        $namesByAge = $collection->pluck('name', 'age');
        $this->assertEquals([30 => 'John', 25 => 'Jane'], $namesByAge->all());
    }
    
    public function testPop()
    {
        $collection = new Collection([1, 2, 3]);
        $popped = $collection->pop();
        
        $this->assertEquals(3, $popped);
        $this->assertEquals([1, 2], $collection->all());
    }
    
    public function testPrepend()
    {
        $collection = new Collection([2, 3]);
        $prepended = $collection->prepend(1);
        
        $this->assertEquals([1, 2, 3], $prepended->all());
        
        $prepended = $collection->prepend(0, 'zero');
        $this->assertEquals(['zero' => 0, 0 => 2, 1 => 3], $prepended->all());
    }
    
    public function testPull()
    {
        $collection = new Collection(['name' => 'John', 'age' => 30]);
        $pulled = $collection->pull('name');
        
        $this->assertEquals('John', $pulled);
        $this->assertEquals(['age' => 30], $collection->all());
    }
    
    public function testPush()
    {
        $collection = new Collection([1, 2]);
        $collection->push(3);
        
        $this->assertEquals([1, 2, 3], $collection->all());
    }
    
    public function testPut()
    {
        $collection = new Collection(['name' => 'John']);
        $collection->put('age', 30);
        
        $this->assertEquals(['name' => 'John', 'age' => 30], $collection->all());
    }
    
    public function testRandom()
    {
        $collection = new Collection([1, 2, 3, 4, 5]);
        $random = $collection->random();
        
        $this->assertContains($random, [1, 2, 3, 4, 5]);
        
        $randoms = $collection->random(2);
        $this->assertInstanceOf(Collection::class, $randoms);
        $this->assertEquals(2, $randoms->count());
    }
    
    public function testReduce()
    {
        $collection = new Collection([1, 2, 3, 4]);
        $sum = $collection->reduce(function($carry, $item) {
            return $carry + $item;
        }, 0);
        
        $this->assertEquals(10, $sum);
    }
    
    public function testReject()
    {
        $collection = new Collection([1, 2, 3, 4, 5]);
        $rejected = $collection->reject(function($item) {
            return $item > 3;
        });
        
        $this->assertEquals([0 => 1, 1 => 2, 2 => 3], $rejected->all());
    }
    
    public function testReverse()
    {
        $collection = new Collection([1, 2, 3]);
        $reversed = $collection->reverse();
        
        $this->assertEquals([2 => 3, 1 => 2, 0 => 1], $reversed->all());
    }
    
    public function testSearch()
    {
        $collection = new Collection([1, 2, 3, 4]);
        $this->assertEquals(2, $collection->search(3));
        $this->assertFalse($collection->search(5));
        
        $this->assertEquals(2, $collection->search(function($item) {
            return $item > 2;
        }));
    }
    
    public function testShuffle()
    {
        $collection = new Collection([1, 2, 3, 4, 5]);
        $shuffled = $collection->shuffle();
        
        $this->assertEquals(5, $shuffled->count());
        $this->assertNotEquals([1, 2, 3, 4, 5], $shuffled->all());
    }
    
    public function testSlice()
    {
        $collection = new Collection([1, 2, 3, 4, 5]);
        $sliced = $collection->slice(2, 2);
        
        $this->assertEquals([2 => 3, 3 => 4], $sliced->all());
    }
    
    public function testSort()
    {
        $collection = new Collection([3, 1, 4, 2]);
        $sorted = $collection->sort();
        
        $this->assertEquals([1, 2, 3, 4], array_values($sorted->all()));
    }
    
    public function testSortBy()
    {
        $collection = new Collection([
            ['name' => 'John', 'age' => 30],
            ['name' => 'Jane', 'age' => 25],
            ['name' => 'Bob', 'age' => 35]
        ]);
        
        $sorted = $collection->sortBy('age');
        $ages = $sorted->pluck('age')->all();
        
        $this->assertEquals([25, 30, 35], array_values($ages));
    }
    
    public function testSortByDesc()
    {
        $collection = new Collection([
            ['name' => 'John', 'age' => 30],
            ['name' => 'Jane', 'age' => 25],
            ['name' => 'Bob', 'age' => 35]
        ]);
        
        $sorted = $collection->sortByDesc('age');
        $ages = $sorted->pluck('age')->all();
        
        $this->assertEquals([35, 30, 25], array_values($ages));
    }
    
    public function testSplice()
    {
        $collection = new Collection([1, 2, 3, 4, 5]);
        $spliced = $collection->splice(2, 2, [10, 11]);
        
        $this->assertEquals([3, 4], $spliced->all());
        $this->assertEquals([1, 2, 10, 11, 5], $collection->all());
    }
    
    public function testSum()
    {
        $collection = new Collection([1, 2, 3, 4]);
        $this->assertEquals(10, $collection->sum());
        
        $collection = new Collection([
            ['score' => 10],
            ['score' => 20],
            ['score' => 30]
        ]);
        $this->assertEquals(60, $collection->sum('score'));
    }
    
    public function testTake()
    {
        $collection = new Collection([1, 2, 3, 4, 5]);
        $taken = $collection->take(3);
        
        $this->assertEquals([1, 2, 3], $taken->all());
        
        $taken = $collection->take(-2);
        $this->assertEquals([3 => 4, 4 => 5], $taken->all());
    }
    
    public function testTap()
    {
        $collection = new Collection([1, 2, 3]);
        $tapped = $collection->tap(function($collection) {
            $collection->push(4);
        });
        
        $this->assertSame($collection, $tapped);
        $this->assertEquals([1, 2, 3, 4], $collection->all());
    }
    
    public function testTransform()
    {
        $collection = new Collection([1, 2, 3]);
        $collection->transform(function($item) {
            return $item * 2;
        });
        
        $this->assertEquals([2, 4, 6], $collection->all());
    }
    
    public function testUnique()
    {
        $collection = new Collection([1, 2, 2, 3, 3, 4]);
        $unique = $collection->unique();
        
        $this->assertEquals([0 => 1, 1 => 2, 3 => 3, 5 => 4], $unique->all());
        
        $collection = new Collection([
            ['name' => 'John', 'age' => 30],
            ['name' => 'Jane', 'age' => 25],
            ['name' => 'John', 'age' => 30]
        ]);
        $unique = $collection->unique('name');
        
        $this->assertEquals(2, $unique->count());
    }
    
    public function testValues()
    {
        $collection = new Collection(['name' => 'John', 'age' => 30]);
        $values = $collection->values();
        
        $this->assertEquals(['John', 30], $values->all());
    }
    
    public function testWhen()
    {
        $collection = new Collection([1, 2, 3]);
        $result = $collection->when(true, function($collection) {
            return $collection->push(4);
        });
        
        $this->assertEquals([1, 2, 3, 4], $result->all());
        
        $result = $collection->when(false, function($collection) {
            return $collection->push(5);
        });
        
        $this->assertEquals([1, 2, 3, 4], $result->all());
    }
    
    public function testWhere()
    {
        $collection = new Collection([
            ['name' => 'John', 'age' => 30],
            ['name' => 'Jane', 'age' => 25],
            ['name' => 'Bob', 'age' => 30]
        ]);
        
        $filtered = $collection->where('age', 30);
        $this->assertEquals(2, $filtered->count());
        
        $filtered = $collection->where('age', '>', 25);
        $this->assertEquals(2, $filtered->count());
    }
    
    public function testWhereIn()
    {
        $collection = new Collection([
            ['name' => 'John', 'age' => 30],
            ['name' => 'Jane', 'age' => 25],
            ['name' => 'Bob', 'age' => 35]
        ]);
        
        $filtered = $collection->whereIn('age', [25, 35]);
        $this->assertEquals(2, $filtered->count());
    }
    
    public function testWhereNotIn()
    {
        $collection = new Collection([
            ['name' => 'John', 'age' => 30],
            ['name' => 'Jane', 'age' => 25],
            ['name' => 'Bob', 'age' => 35]
        ]);
        
        $filtered = $collection->whereNotIn('age', [25]);
        $this->assertEquals(2, $filtered->count());
    }
    
    public function testZip()
    {
        $collection = new Collection([1, 2, 3]);
        $zipped = $collection->zip(['a', 'b', 'c']);
        
        $expected = [
            new Collection([1, 'a']),
            new Collection([2, 'b']),
            new Collection([3, 'c'])
        ];
        
        $this->assertEquals(3, $zipped->count());
        $this->assertEquals([1, 'a'], $zipped->first()->all());
    }
    
    public function testArrayAccess()
    {
        $collection = new Collection(['name' => 'John', 'age' => 30]);
        
        // Test offsetExists
        $this->assertTrue(isset($collection['name']));
        $this->assertFalse(isset($collection['city']));
        
        // Test offsetGet
        $this->assertEquals('John', $collection['name']);
        
        // Test offsetSet
        $collection['city'] = 'NYC';
        $this->assertEquals('NYC', $collection['city']);
        
        // Test offsetUnset
        unset($collection['age']);
        $this->assertFalse(isset($collection['age']));
    }
    
    public function testIterator()
    {
        $collection = new Collection(['a' => 1, 'b' => 2, 'c' => 3]);
        $result = [];
        
        foreach ($collection as $key => $value) {
            $result[$key] = $value;
        }
        
        $this->assertEquals(['a' => 1, 'b' => 2, 'c' => 3], $result);
    }
    
    public function testJsonSerialize()
    {
        $collection = new Collection(['name' => 'John', 'age' => 30]);
        $json = json_encode($collection);
        
        $this->assertEquals('{"name":"John","age":30}', $json);
    }
    
    public function testToString()
    {
        $collection = new Collection(['name' => 'John', 'age' => 30]);
        $string = (string) $collection;
        
        $this->assertEquals('{"name":"John","age":30}', $string);
    }
}