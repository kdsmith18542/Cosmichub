<?php

namespace Tests\Unit\Support;

use Tests\TestCase;
use App\Support\Str;

/**
 * Test cases for the Str helper class
 */
class StrTest extends TestCase
{
    public function testAfter()
    {
        $this->assertEquals('name', Str::after('user.name', '.'));
        $this->assertEquals('', Str::after('username', '.'));
        $this->assertEquals('bar', Str::after('foobar', 'foo'));
    }
    
    public function testAfterLast()
    {
        $this->assertEquals('name', Str::afterLast('user.profile.name', '.'));
        $this->assertEquals('username', Str::afterLast('username', '.'));
        $this->assertEquals('baz', Str::afterLast('foo/bar/baz', '/'));
    }
    
    public function testBefore()
    {
        $this->assertEquals('user', Str::before('user.name', '.'));
        $this->assertEquals('username', Str::before('username', '.'));
        $this->assertEquals('foo', Str::before('foobar', 'bar'));
    }
    
    public function testBeforeLast()
    {
        $this->assertEquals('user.profile', Str::beforeLast('user.profile.name', '.'));
        $this->assertEquals('username', Str::beforeLast('username', '.'));
        $this->assertEquals('foo/bar', Str::beforeLast('foo/bar/baz', '/'));
    }
    
    public function testBetween()
    {
        $this->assertEquals('name', Str::between('[name]', '[', ']'));
        $this->assertEquals('', Str::between('username', '[', ']'));
        $this->assertEquals('bar', Str::between('foo(bar)baz', '(', ')'));
    }
    
    public function testCamel()
    {
        $this->assertEquals('userName', Str::camel('user_name'));
        $this->assertEquals('userProfileName', Str::camel('user-profile-name'));
        $this->assertEquals('userProfileName', Str::camel('user profile name'));
        $this->assertEquals('userName', Str::camel('UserName'));
    }
    
    public function testContains()
    {
        $this->assertTrue(Str::contains('Hello World', 'World'));
        $this->assertTrue(Str::contains('Hello World', ['Hello', 'Universe']));
        $this->assertFalse(Str::contains('Hello World', 'Universe'));
        $this->assertFalse(Str::contains('Hello World', ['Universe', 'Galaxy']));
    }
    
    public function testContainsAll()
    {
        $this->assertTrue(Str::containsAll('Hello World', ['Hello', 'World']));
        $this->assertFalse(Str::containsAll('Hello World', ['Hello', 'Universe']));
    }
    
    public function testEndsWith()
    {
        $this->assertTrue(Str::endsWith('Hello World', 'World'));
        $this->assertTrue(Str::endsWith('Hello World', ['Universe', 'World']));
        $this->assertFalse(Str::endsWith('Hello World', 'Hello'));
        $this->assertFalse(Str::endsWith('Hello World', ['Hello', 'Universe']));
    }
    
    public function testFinish()
    {
        $this->assertEquals('path/', Str::finish('path', '/'));
        $this->assertEquals('path/', Str::finish('path/', '/'));
        $this->assertEquals('path//', Str::finish('path/', '//'));
    }
    
    public function testIs()
    {
        $this->assertTrue(Str::is('foo*', 'foobar'));
        $this->assertTrue(Str::is('foo*', 'foo'));
        $this->assertFalse(Str::is('foo*', 'barfoo'));
        $this->assertTrue(Str::is(['foo*', 'bar*'], 'foobar'));
        $this->assertFalse(Str::is(['foo*', 'bar*'], 'bazqux'));
    }
    
    public function testIsAscii()
    {
        $this->assertTrue(Str::isAscii('Hello World'));
        $this->assertFalse(Str::isAscii('Héllo Wörld'));
        $this->assertTrue(Str::isAscii('123'));
    }
    
    public function testIsJson()
    {
        $this->assertTrue(Str::isJson('{"name":"John"}'));
        $this->assertTrue(Str::isJson('[1,2,3]'));
        $this->assertFalse(Str::isJson('Hello World'));
        $this->assertFalse(Str::isJson('{name:"John"}'));
    }
    
    public function testIsUuid()
    {
        $this->assertTrue(Str::isUuid('550e8400-e29b-41d4-a716-446655440000'));
        $this->assertFalse(Str::isUuid('not-a-uuid'));
        $this->assertFalse(Str::isUuid('550e8400-e29b-41d4-a716'));
    }
    
    public function testKebab()
    {
        $this->assertEquals('user-name', Str::kebab('userName'));
        $this->assertEquals('user-profile-name', Str::kebab('UserProfileName'));
        $this->assertEquals('user-name', Str::kebab('user_name'));
    }
    
    public function testLength()
    {
        $this->assertEquals(11, Str::length('Hello World'));
        $this->assertEquals(0, Str::length(''));
        $this->assertEquals(3, Str::length('123'));
    }
    
    public function testLimit()
    {
        $this->assertEquals('Hello...', Str::limit('Hello World', 5));
        $this->assertEquals('Hello World', Str::limit('Hello World', 20));
        $this->assertEquals('Hello***', Str::limit('Hello World', 5, '***'));
    }
    
    public function testLower()
    {
        $this->assertEquals('hello world', Str::lower('Hello World'));
        $this->assertEquals('hello world', Str::lower('HELLO WORLD'));
    }
    
    public function testWords()
    {
        $text = 'The quick brown fox jumps over the lazy dog';
        $this->assertEquals('The quick brown...', Str::words($text, 3));
        $this->assertEquals('The quick brown***', Str::words($text, 3, '***'));
        $this->assertEquals($text, Str::words($text, 20));
    }
    
    public function testPadBoth()
    {
        $this->assertEquals('  Hello  ', Str::padBoth('Hello', 9));
        $this->assertEquals('--Hello--', Str::padBoth('Hello', 9, '-'));
        $this->assertEquals('Hello', Str::padBoth('Hello', 3));
    }
    
    public function testPadLeft()
    {
        $this->assertEquals('    Hello', Str::padLeft('Hello', 9));
        $this->assertEquals('----Hello', Str::padLeft('Hello', 9, '-'));
        $this->assertEquals('Hello', Str::padLeft('Hello', 3));
    }
    
    public function testPadRight()
    {
        $this->assertEquals('Hello    ', Str::padRight('Hello', 9));
        $this->assertEquals('Hello----', Str::padRight('Hello', 9, '-'));
        $this->assertEquals('Hello', Str::padRight('Hello', 3));
    }
    
    public function testPlural()
    {
        $this->assertEquals('cats', Str::plural('cat'));
        $this->assertEquals('children', Str::plural('child'));
        $this->assertEquals('sheep', Str::plural('sheep'));
        $this->assertEquals('cat', Str::plural('cat', 1));
        $this->assertEquals('cats', Str::plural('cat', 2));
    }
    
    public function testRandom()
    {
        $random1 = Str::random(10);
        $random2 = Str::random(10);
        
        $this->assertEquals(10, strlen($random1));
        $this->assertEquals(10, strlen($random2));
        $this->assertNotEquals($random1, $random2);
        
        $this->assertEquals(0, strlen(Str::random(0)));
    }
    
    public function testRepeat()
    {
        $this->assertEquals('aaaa', Str::repeat('a', 4));
        $this->assertEquals('abcabc', Str::repeat('abc', 2));
        $this->assertEquals('', Str::repeat('a', 0));
    }
    
    public function testReplace()
    {
        $this->assertEquals('Hello Universe', Str::replace('Hello World', 'World', 'Universe'));
        $this->assertEquals('Hi Universe', Str::replace('Hello World', ['Hello', 'World'], ['Hi', 'Universe']));
    }
    
    public function testReplaceArray()
    {
        $this->assertEquals('Hello Jane and John', Str::replaceArray('?', ['Jane', 'John'], 'Hello ? and ?'));
        $this->assertEquals('Hello Jane and ?', Str::replaceArray('?', ['Jane'], 'Hello ? and ?'));
    }
    
    public function testReplaceFirst()
    {
        $this->assertEquals('Hello Universe World', Str::replaceFirst('Hello World World', 'World', 'Universe'));
        $this->assertEquals('Hello World', Str::replaceFirst('Hello World', 'Universe', 'Galaxy'));
    }
    
    public function testReplaceLast()
    {
        $this->assertEquals('Hello World Universe', Str::replaceLast('Hello World World', 'World', 'Universe'));
        $this->assertEquals('Hello World', Str::replaceLast('Hello World', 'Universe', 'Galaxy'));
    }
    
    public function testSingular()
    {
        $this->assertEquals('cat', Str::singular('cats'));
        $this->assertEquals('child', Str::singular('children'));
        $this->assertEquals('sheep', Str::singular('sheep'));
    }
    
    public function testSlug()
    {
        $this->assertEquals('hello-world', Str::slug('Hello World'));
        $this->assertEquals('hello_world', Str::slug('Hello World', '_'));
        $this->assertEquals('hello-world-123', Str::slug('Hello World 123!@#'));
    }
    
    public function testSnake()
    {
        $this->assertEquals('user_name', Str::snake('userName'));
        $this->assertEquals('user_profile_name', Str::snake('UserProfileName'));
        $this->assertEquals('user-name', Str::snake('userName', '-'));
    }
    
    public function testStart()
    {
        $this->assertEquals('/path', Str::start('path', '/'));
        $this->assertEquals('/path', Str::start('/path', '/'));
        $this->assertEquals('//path', Str::start('/path', '//'));
    }
    
    public function testStartsWith()
    {
        $this->assertTrue(Str::startsWith('Hello World', 'Hello'));
        $this->assertTrue(Str::startsWith('Hello World', ['Hi', 'Hello']));
        $this->assertFalse(Str::startsWith('Hello World', 'World'));
        $this->assertFalse(Str::startsWith('Hello World', ['World', 'Universe']));
    }
    
    public function testStudly()
    {
        $this->assertEquals('UserName', Str::studly('user_name'));
        $this->assertEquals('UserProfileName', Str::studly('user-profile-name'));
        $this->assertEquals('UserProfileName', Str::studly('user profile name'));
    }
    
    public function testSubstr()
    {
        $this->assertEquals('World', Str::substr('Hello World', 6));
        $this->assertEquals('Wor', Str::substr('Hello World', 6, 3));
        $this->assertEquals('orld', Str::substr('Hello World', -4));
    }
    
    public function testSubstrCount()
    {
        $this->assertEquals(2, Str::substrCount('Hello World World', 'World'));
        $this->assertEquals(0, Str::substrCount('Hello World', 'Universe'));
        $this->assertEquals(3, Str::substrCount('aaa', 'a'));
    }
    
    public function testSubstrReplace()
    {
        $this->assertEquals('Hello Universe', Str::substrReplace('Hello World', 'Universe', 6));
        $this->assertEquals('Hello Universe World', Str::substrReplace('Hello World', 'Universe ', 6, 0));
    }
    
    public function testTitle()
    {
        $this->assertEquals('Hello World', Str::title('hello world'));
        $this->assertEquals('Hello World', Str::title('HELLO WORLD'));
        $this->assertEquals('Hello-World', Str::title('hello-world'));
    }
    
    public function testUcfirst()
    {
        $this->assertEquals('Hello world', Str::ucfirst('hello world'));
        $this->assertEquals('HELLO WORLD', Str::ucfirst('hELLO WORLD'));
    }
    
    public function testUpper()
    {
        $this->assertEquals('HELLO WORLD', Str::upper('Hello World'));
        $this->assertEquals('HELLO WORLD', Str::upper('hello world'));
    }
    
    public function testUuid()
    {
        $uuid1 = Str::uuid();
        $uuid2 = Str::uuid();
        
        $this->assertTrue(Str::isUuid($uuid1));
        $this->assertTrue(Str::isUuid($uuid2));
        $this->assertNotEquals($uuid1, $uuid2);
    }
    
    public function testWordCount()
    {
        $this->assertEquals(2, Str::wordCount('Hello World'));
        $this->assertEquals(0, Str::wordCount(''));
        $this->assertEquals(1, Str::wordCount('Hello'));
        $this->assertEquals(4, Str::wordCount('The quick brown fox'));
    }
}