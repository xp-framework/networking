<?php namespace peer\unittest;

use lang\{FormatException, IllegalArgumentException};
use peer\URL;
use unittest\{Expect, Test, Values};

/**
 * TestCase
 *
 * @see   xp://peer.URL
 * @see   https://github.com/xp-framework/xp-framework/issues/182
 * @see   rfc://rfc1738
 * @see   http://bugs.php.net/54180
 */
class URLTest extends \unittest\TestCase {

  #[Test]
  public function scheme() {
    $this->assertEquals('http', (new URL('http://localhost'))->getScheme());
  }

  #[Test]
  public function schemeWithPlus() {
    $this->assertEquals('svn+ssl', (new URL('svn+ssl://localhost'))->getScheme());
  }

  #[Test]
  public function schemeMutability() {
    $this->assertEquals(
      'ftp://localhost', 
      (new URL('http://localhost'))->setScheme('ftp')->getURL()
    );
  }

  #[Test]
  public function host() {
    $this->assertEquals('localhost', (new URL('http://localhost'))->getHost());
  }

  #[Test]
  public function uppercaseHost() {
    $this->assertEquals('TEST', (new URL('http://TEST'))->getHost());
  }

  #[Test]
  public function hostMutability() {
    $this->assertEquals(
      'http://127.0.0.1', 
      (new URL('http://localhost'))->setHost('127.0.0.1')->getURL()
    );
  }

  #[Test]
  public function path() {
    $this->assertEquals('/news/index.html', (new URL('http://localhost/news/index.html'))->getPath());
  }

  #[Test]
  public function emptyPath() {
    $this->assertEquals(null, (new URL('http://localhost'))->getPath());
  }

  #[Test]
  public function slashPath() {
    $this->assertEquals('/', (new URL('http://localhost/'))->getPath());
  }

  #[Test]
  public function pathDefault() {
    $this->assertEquals('/', (new URL('http://localhost'))->getPath('/'));
  }

  #[Test]
  public function pathMutability() {
    $this->assertEquals(
      'http://localhost/index.html', 
      (new URL('http://localhost'))->setPath('/index.html')->getURL()
    );
  }

  #[Test]
  public function user() {
    $this->assertEquals('user', (new URL('http://user@localhost'))->getUser());
  }

  #[Test]
  public function emptyUser() {
    $this->assertEquals(null, (new URL('http://localhost'))->getUser());
  }

  #[Test]
  public function userDefault() {
    $this->assertEquals('nobody', (new URL('http://localhost'))->getUser('nobody'));
  }

  #[Test]
  public function urlEncodedUser() {
    $this->assertEquals('user?', (new URL('http://user%3F@localhost'))->getUser());
  }

  #[Test]
  public function setUrlEncodedUser() {
    $this->assertEquals('http://user%3F@localhost', (new URL('http://localhost'))->setUser('user?')->getURL());
  }

  #[Test]
  public function userMutability() {
    $this->assertEquals(
      'http://thekid@localhost', 
      (new URL('http://localhost'))->setUser('thekid')->getURL()
    );
  }

  #[Test]
  public function password() {
    $this->assertEquals('password', (new URL('http://user:password@localhost'))->getPassword());
  }

  #[Test]
  public function urlEncodedPassword() {
    $this->assertEquals('pass?word', (new URL('http://user:pass%3Fword@localhost'))->getPassword());
  }

  #[Test]
  public function setUrlEncodedPassword() {
    $this->assertEquals('http://user:pass%3Fword@localhost', (new URL('http://user@localhost'))->setPassword('pass?word')->getURL());
  }

  #[Test]
  public function emptyPassword() {
    $this->assertEquals(null, (new URL('http://localhost'))->getPassword());
  }

  #[Test]
  public function passwordDefault() {
    $this->assertEquals('secret', (new URL('http://user@localhost'))->getPassword('secret'));
  }

  #[Test]
  public function passwordMutability() {
    $this->assertEquals(
      'http://anon:anon@localhost', 
      (new URL('http://anon@localhost'))->setPassword('anon')->getURL()
    );
  }

  #[Test]
  public function query() {
    $this->assertEquals('a=b', (new URL('http://localhost?a=b'))->getQuery());
  }

  #[Test]
  public function queryModifiedByParams() {
    $this->assertEquals(
      'a=b&c=d', 
      (new URL('http://localhost?a=b'))->addParam('c', 'd')->getQuery()
    );
  }

  #[Test]
  public function emptyQuery() {
    $this->assertEquals(null, (new URL('http://localhost'))->getQuery());
  }

  #[Test]
  public function parameterLessQuery() {
    $this->assertEquals('1549', (new URL('http://localhost/?1549'))->getQuery());
  }

  #[Test]
  public function addToParameterLessQuery() {
    $this->assertEquals('1549&a=b', (new URL('http://localhost/?1549'))->addParam('a', 'b')->getQuery());
  }

  #[Test]
  public function ParameterLessQueryWithAdd() {
    $this->assertEquals('1549', (new URL('http://localhost/'))->addParam('1549')->getQuery());
  }

  #[Test]
  public function ParameterLessQueryWithSet() {
    $this->assertEquals('1549', (new URL('http://localhost/'))->setParam('1549')->getQuery());
  }

  #[Test]
  public function questionMarkOnly() {
    $this->assertEquals(null, (new URL('http://localhost?'))->getQuery());
  }

  #[Test]
  public function questionMarkAndFragmentOnly() {
    $this->assertEquals(null, (new URL('http://localhost?#'))->getQuery());
  }

  #[Test]
  public function queryDefault() {
    $this->assertEquals('1,2,3', (new URL('http://localhost'))->getQuery('1,2,3'));
  }

  #[Test]
  public function queryMutability() {
    $this->assertEquals(
      'http://localhost?a=b', 
      (new URL('http://localhost'))->setQuery('a=b')->getURL()
    );
  }

  #[Test]
  public function getParameterLessQuery() {
    $this->assertEquals('', (new URL('http://localhost/?1549'))->getParam('1549'));
  }

  #[Test]
  public function hasParameterLessQuery() {
    $this->assertTrue((new URL('http://localhost/?1549'))->hasParam('1549'));
  }

  #[Test]
  public function fragment() {
    $this->assertEquals('top', (new URL('http://localhost#top'))->getFragment());
  }

  #[Test]
  public function fragmentWithSlash() {
    $this->assertEquals('top', (new URL('http://localhost/#top'))->getFragment());
  }

  #[Test]
  public function fragmentWithSlashAndQuestionMark() {
    $this->assertEquals('top', (new URL('http://localhost/?#top'))->getFragment());
  }

  #[Test]
  public function fragmentWithQuery() {
    $this->assertEquals('top', (new URL('http://localhost/?query#top'))->getFragment());
  }

  #[Test]
  public function emptyFragment() {
    $this->assertEquals(null, (new URL('http://localhost'))->getFragment());
  }

  #[Test]
  public function hashOnly() {
    $this->assertEquals(null, (new URL('http://localhost#'))->getFragment());
  }

  #[Test]
  public function hashAtEnd() {
    $this->assertEquals(null, (new URL('http://localhost?#'))->getFragment());
  }

  #[Test]
  public function hashAtEndWithQuery() {
    $this->assertEquals(null, (new URL('http://localhost?query#'))->getFragment());
  }

  #[Test]
  public function fragmentDefault() {
    $this->assertEquals('top', (new URL('http://localhost'))->getFragment('top'));
  }

  #[Test]
  public function fragmentMutability() {
    $this->assertEquals(
      'http://localhost#list', 
      (new URL('http://localhost'))->setFragment('list')->getURL()
    );
  }

  #[Test]
  public function port() {
    $this->assertEquals(8080, (new URL('http://localhost:8080'))->getPort());
  }

  #[Test]
  public function emptyPort() {
    $this->assertEquals(null, (new URL('http://localhost'))->getPort());
  }

  #[Test]
  public function portDefault() {
    $this->assertEquals(80, (new URL('http://localhost'))->getPort(80));
  }

  #[Test]
  public function portMutability() {
    $this->assertEquals(
      'http://localhost:8081', 
      (new URL('http://localhost'))->setPort(8081)->getURL()
    );
  }

  #[Test]
  public function param() {
    $this->assertEquals('b', (new URL('http://localhost?a=b'))->getParam('a'));
  }

  #[Test]
  public function getArrayParameter() {
    $this->assertEquals(['b'], (new URL('http://localhost?a[]=b'))->getParam('a'));
  }

  #[Test]
  public function getEncodedArrayParameter() {
    $this->assertEquals(['='], (new URL('http://localhost?a[]=%3D'))->getParam('a'));
  }

  #[Test]
  public function getArrayParameters() {
    $this->assertEquals(['b', 'c'], (new URL('http://localhost?a[]=b&a[]=c'))->getParam('a'));
  }

  #[Test]
  public function getArrayParametersAsHash() {
    $this->assertEquals(
      ['name' => 'b', 'color' => 'c'],
      (new URL('http://localhost?a[name]=b&a[color]=c'))->getParam('a')
    );
  }

  #[Test]
  public function getArrayParametersAsHashWithEncodedNames() {
    $this->assertEquals(
      ['=name=' => 'b', '=color=' => 'c'],
      (new URL('http://localhost?a[%3Dname%3D]=b&a[%3Dcolor%3D]=c'))->getParam('a')
    );
  }

  #[Test]
  public function arrayOffsetsInDifferentArrays() {
    $this->assertEquals(
      ['a' => ['c'], 'b' => ['d']],
      (new URL('http://localhost/?a[]=c&b[]=d'))->getParams()
    );
  }

  #[Test]
  public function duplicateOffsetsOverwriteEachother() {
    $this->assertEquals(
      ['c'], 
      (new URL('http://localhost/?a[0]=b&a[0]=c'))->getParam('a')
    );
  }

  #[Test]
  public function duplicateNamesOverwriteEachother() {
    $this->assertEquals(
      ['name' => 'c'],
      (new URL('http://localhost/?a[name]=b&a[name]=c'))->getParam('a')
    );
  }

  #[Test]
  public function twoDimensionalArray() {
    $this->assertEquals(
      [['b']], 
      (new URL('http://localhost/?a[][]=b'))->getParam('a')
    );
  }

  #[Test]
  public function threeDimensionalArray() {
    $this->assertEquals(
      [[['b']]],
      (new URL('http://localhost/?a[][][]=b'))->getParam('a')
    );
  }

  #[Test]
  public function arrayOfHash() {
    $this->assertEquals(
      [[['name' => 'b']]],
      (new URL('http://localhost/?a[][][name]=b'))->getParam('a')
    );
  }

  #[Test]
  public function hashOfArray() {
    $this->assertEquals(
      ['name' => [['b']]],
      (new URL('http://localhost/?a[name][][]=b'))->getParam('a')
    );
  }

  #[Test]
  public function hashOfArrayOfHash() {
    $this->assertEquals(
      ['name' => [['key' => 'b']]],
      (new URL('http://localhost/?a[name][][key]=b'))->getParam('a')
    );
  }

  #[Test]
  public function hashNotationWithoutValues() {
    $this->assertEquals(
      ['name' => '', 'color' => ''],
      (new URL('http://localhost/?a[name]&a[color]'))->getParam('a')
    );
  }

  #[Test]
  public function arrayNotationWithoutValues() {
    $this->assertEquals(
      ['', ''],
      (new URL('http://localhost/?a[]&a[]'))->getParam('a')
    );
  }

  #[Test]
  public function getArrayParams() {
    $this->assertEquals(
      ['a' => ['b', 'c']],
      (new URL('http://localhost?a[]=b&a[]=c'))->getParams()
    );
  }

  #[Test]
  public function mixedOffsetsAndKeys() {
    $this->assertEquals(
      [0 => 'b', 'name' => 'c', 1 => 'd'],
      (new URL('http://localhost/?a[]=b&a[name]=c&a[]=d'))->getParam('a')
    );
  }

  #[Test]
  public function nestedBraces() {
    $this->assertEquals(
      ['a' => ['nested[]' => 'b']],
      (new URL('http://localhost/?a[nested[]]=b'))->getParams()
    );
  }
 
  #[Test]
  public function nestedBracesTwice() {
    $this->assertEquals(
      ['a' => ['nested[a]' => 'b', 'nested[b]' => 'c']],
      (new URL('http://localhost/?a[nested[a]]=b&a[nested[b]]=c'))->getParams()
    );
  }

  #[Test]
  public function nestedBracesChained() {
    $this->assertEquals(
      ['a' => ['nested[a]' => ['c']]],
      (new URL('http://localhost/?a[nested[a]][]=c'))->getParams()
    );
  }

  #[Test]
  public function unnamedArrayParameterDoesNotArray() {
    $this->assertEquals(
      ['[]' => 'c'],
      (new URL('http://localhost/?[]=c'))->getParams()
    );
  }

  #[Test]
  public function nonExistantParam() {
    $this->assertEquals(null, (new URL('http://localhost?a=b'))->getParam('b'));
  }

  #[Test]
  public function emptyParam() {
    $this->assertEquals('', (new URL('http://localhost?x='))->getParam('x'));
  }

  #[Test]
  public function paramDefault() {
    $this->assertEquals('x', (new URL('http://localhost?a=b'))->getParam('c', 'x'));
  }
 
  #[Test]
  public function addNewParam() {
    $this->assertEquals(
      'http://localhost?a=b', 
      (new URL('http://localhost'))->addParam('a', 'b')->getURL()
    );
  }

  #[Test]
  public function setNewParam() {
    $this->assertEquals(
      'http://localhost?a=b', 
      (new URL('http://localhost'))->setParam('a', 'b')->getURL()
    );
  }

  #[Test]
  public function addAdditionalParam() {
    $this->assertEquals(
      'http://localhost?a=b&c=d', 
      (new URL('http://localhost?a=b'))->addParam('c', 'd')->getURL()
    );
  }

  #[Test]
  public function setAdditionalParam() {
    $this->assertEquals(
      'http://localhost?a=b&c=d', 
      (new URL('http://localhost?a=b'))->setParam('c', 'd')->getURL()
    );
  }

  #[Test]
  public function addAdditionalParamChained() {
    $this->assertEquals(
      'http://localhost?a=b&c=d&e=f', 
      (new URL('http://localhost?a=b'))->addParam('c', 'd')->addParam('e', 'f')->getURL()
    );
  }

  #[Test]
  public function setAdditionalParamChained() {
    $this->assertEquals(
      'http://localhost?a=b&c=d&e=f', 
      (new URL('http://localhost?a=b'))->setParam('c', 'd')->setParam('e', 'f')->getURL()
    );
  }

  #[Test, Expect(IllegalArgumentException::class)]
  public function addExistingParam() {
    (new URL('http://localhost?a=b'))->addParam('a', 'b');
  }

  #[Test]
  public function setExistingParam() {
    $this->assertEquals(
      'http://localhost?a=c', 
      (new URL('http://localhost?a=b'))->setParam('a', 'c')->getURL()
    );
  }

  #[Test, Expect(IllegalArgumentException::class)]
  public function addExistingParams() {
    (new URL('http://localhost?a=b'))->addParams(['a' => 'b']);
  }

  #[Test]
  public function addExistingParamsDoesNotPartiallyModify() {
    $original= 'http://localhost?a=b';
    $u= new URL($original);
    try {
      $u->addParams(['c' => 'd', 'a' => 'b']);
      $this->fail('Existing parameter "a" not detected', null, IllegalArgumentException::class);
    } catch (\lang\IllegalArgumentException $expected) { }
    $this->assertEquals($original, $u->getURL());
  }

  #[Test]
  public function setExistingParams() {
    $this->assertEquals(
      'http://localhost?a=c', 
      (new URL('http://localhost?a=b'))->setParams(['a' => 'c'])->getURL()
    );
  }

  #[Test]
  public function addNewParams() {
    $this->assertEquals(
      'http://localhost?a=b&c=d', 
      (new URL('http://localhost'))->addParams(['a' => 'b', 'c' => 'd'])->getURL()
    );
  }

  #[Test]
  public function setNewParams() {
    $this->assertEquals(
      'http://localhost?a=b&c=d', 
      (new URL('http://localhost'))->setParams(['a' => 'b', 'c' => 'd'])->getURL()
    );
  }

  #[Test]
  public function addAdditionalParams() {
    $this->assertEquals(
      'http://localhost?z=x&a=b&c=d', 
      (new URL('http://localhost?z=x'))->addParams(['a' => 'b', 'c' => 'd'])->getURL()
    );
  }

  #[Test]
  public function setAdditionalParams() {
    $this->assertEquals(
      'http://localhost?z=x&a=b&c=d', 
      (new URL('http://localhost?z=x'))->setParams(['a' => 'b', 'c' => 'd'])->getURL()
    );
  }

  #[Test]
  public function addArrayParam() {
    $u= new URL('http://localhost/');
    $u->addParam('x', ['y', 'z']);
    $this->assertEquals('http://localhost/?x[]=y&x[]=z', $u->getURL());
  }

  #[Test]
  public function setArrayParam() {
    $u= new URL('http://localhost/');
    $u->setParam('x', ['y', 'z']);
    $this->assertEquals('http://localhost/?x[]=y&x[]=z', $u->getURL());
  }

  #[Test]
  public function params() {
    $this->assertEquals(['a' => 'b', 'c' => 'd'], (new URL('http://localhost?a=b&c=d'))->getParams());
  }

  #[Test]
  public function withParams() {
    $this->assertTrue((new URL('http://localhost?a=b&c=d'))->hasParams());
  }

  #[Test]
  public function withArrayParams() {
    $this->assertTrue((new URL('http://localhost?a[]=b&a[]=d'))->hasParams());
  }

  #[Test]
  public function noParams() {
    $this->assertFalse((new URL('http://localhost'))->hasParams());
  }

  #[Test]
  public function withParam() {
    $this->assertTrue((new URL('http://localhost?a=b&c=d'))->hasParam('a'));
  }

  #[Test]
  public function withArrayParam() {
    $this->assertTrue((new URL('http://localhost?a[]=b&a[]=d'))->hasParam('a'));
  }

  #[Test]
  public function withNonExistantParam() {
    $this->assertFalse((new URL('http://localhost?a=b&c=d'))->hasParam('d'));
  }

  #[Test]
  public function noParam() {
    $this->assertFalse((new URL('http://localhost'))->hasParam('a'));
  }

  #[Test]
  public function hasDotParam() {
    $this->assertTrue((new URL('http://localhost/?a.b=c'))->hasParam('a.b'));
  }

  #[Test]
  public function getDotParam() {
    $this->assertEquals('c', (new URL('http://localhost/?a.b=c'))->getParam('a.b'));
  }

  #[Test]
  public function getDotParams() {
    $this->assertEquals(['a.b' => 'c'], (new URL('http://localhost/?a.b=c'))->getParams());
  }

  #[Test]
  public function addDotParam() {
    $this->assertEquals('a.b=c', (new URL('http://localhost/'))->addParam('a.b', 'c')->getQuery());
  }

  #[Test]
  public function removeExistingParam() {
    $this->assertEquals(new URL('http://localhost'), (new URL('http://localhost?a=b'))->removeParam('a'));
  }

  #[Test]
  public function removeNonExistantParam() {
    $this->assertEquals(new URL('http://localhost'), (new URL('http://localhost'))->removeParam('a'));
  }

  #[Test]
  public function removeExistingArrayParam() {
    $this->assertEquals(new URL('http://localhost'), (new URL('http://localhost?a[]=b&a[]=c'))->removeParam('a'));
  }

  #[Test]
  public function sameUrlsAreEqual() {
    $this->assertEquals(new URL('http://localhost'), new URL('http://localhost'));
  }

  #[Test]
  public function differentUrlsAreNotEqual() {
    $this->assertNotEquals(new URL('http://localhost'), new URL('http://example.com'));
  }

  #[Test]
  public function hashCodesForSameUrls() {
    $this->assertEquals(
      (new URL('http://localhost'))->hashCode(), 
      (new URL('http://localhost'))->hashCode()
    );
  }

  #[Test]
  public function hashCodesForDifferentUrls() {
    $this->assertNotEquals(
      (new URL('http://localhost'))->hashCode(), 
      (new URL('ftp://localhost'))->hashCode()
    );
  }

  #[Test]
  public function hashCodeRecalculated() {
    $u= new URL('http://localhost');
    $u->addParam('a', 'b');
    
    $this->assertNotEquals(
      (new URL('http://localhost'))->hashCode(), 
      $u->hashCode()
    );
  }

  #[Test, Expect(FormatException::class)]
  public function insideAText() {
    new URL('this is the url http://url/ and nothing else');
  }

  #[Test, Expect(FormatException::class)]
  public function doesNotSupportMailto() {
    new URL('mailto:user@example.com');
  }

  #[Test, Expect(FormatException::class)]
  public function whiteSpaceInSchemeNotAllowed() {
    new URL('scheme ://host');
  }

  #[Test, Expect(FormatException::class)]
  public function minusInSchemeNotAllowed() {
    new URL('scheme-minus://host');
  }

  #[Test, Expect(FormatException::class)]
  public function underscoreInSchemeNotAllowed() {
    new URL('scheme_underscore://host');
  }

  #[Test, Expect(FormatException::class)]
  public function numericSchemeNotAllowed() {
    new URL('123://host');
  }

  #[Test, Expect(FormatException::class)]
  public function plusAsFirstSignInSchemeNotAllowed() {
    new URL('+v2://host');
  }

  #[Test]
  public function numericAsPartOfSchemeAllowed() {
    $this->assertEquals('foo+v2', (new URL('foo+v2://host'))->getScheme());
  }

  #[Test]
  public function oneLetterScheme() {
    $this->assertEquals('f', (new URL('f://host'))->getScheme());
  }

  #[Test, Expect(FormatException::class)]
  public function schemeOnlyUnparseable() {
    new URL('http:');
  }

  #[Test, Expect(FormatException::class)]
  public function schemeAndSeparatorOnlyUnparseable() {
    new URL('http://');
  }

  #[Test, Expect(FormatException::class)]
  public function schemeSeparatorOnlyUnparseable() {
    new URL('://');
  }

  #[Test, Expect(FormatException::class)]
  public function colonOnlyUnparseable() {
    new URL(':');
  }

  #[Test, Expect(FormatException::class)]
  public function slashSlashOnlyUnparseable() {
    new URL('//');
  }

  #[Test, Expect(FormatException::class)]
  public function missingSchemeUnparseable() {
    new URL(':///path/to/file');
  }

  #[Test, Expect(FormatException::class)]
  public function emptyUnparseable() {
    new URL('');
  }

  #[Test, Expect(FormatException::class)]
  public function withoutSchemeUnparseable() {
    new URL('/path/to/file');
  }

  #[Test, Expect(FormatException::class)]
  public function slashOnlyUnparseable() {
    new URL('/');
  }

  #[Test, Expect(FormatException::class)]
  public function missingClosingBracket() {
    new URL('http://example.com/?a[=c');
  }

  #[Test, Expect(FormatException::class)]
  public function missingOpeningBracket() {
    new URL('http://example.com/?a]=c');
  }

  #[Test, Expect(FormatException::class)]
  public function unbalancedOpeningBrackets() {
    new URL('http://example.com/?a[[[]]=c');
  }

  #[Test, Expect(FormatException::class)]
  public function unbalancedClosingBrackets() {
    new URL('http://example.com/?a[[]]]=c');
  }

  #[Test, Expect(FormatException::class)]
  public function missingClosingBracketAfterClosed() {
    new URL('http://example.com/?a[][=c');
  }

  #[Test, Expect(FormatException::class)]
  public function missingClosingBracketInNested() {
    new URL('http://localhost/?a[nested[a]=c');
  }

  #[Test, Expect(FormatException::class)]
  public function missingClosingBracketInNestedAfterClosed() {
    new URL('http://localhost/?a[][nested[a]=c');
  }

  #[Test, Expect(FormatException::class)]
  public function missingClosingBracketInNestedBeforeClosed() {
    new URL('http://localhost/?a[nested[a][]=c');
  }

  #[Test, Expect(FormatException::class)]
  public function singleSlash() {
    new URL('http:/blah.com');
  }

  #[Test, Expect(FormatException::class)]
  public function portOnlyNoHost() {
    new URL('http://:80');
  }

  #[Test, Expect(FormatException::class)]
  public function userAndPortOnlyNoHost() {
    new URL('http://user@:80');
  }

  #[Test, Expect(FormatException::class)]
  public function atSignOnlyNoHost() {
    new URL('http://@');
  }

  #[Test, Expect(FormatException::class)]
  public function userOnlyNoHost() {
    new URL('http://user@');
  }

  #[Test, Expect(FormatException::class)]
  public function doubleDoubleColon() {
    new URL('http://::');
  }

  #[Test, Expect(FormatException::class)]
  public function questionMarkOnlyNoHost() {
    new URL('http://?');
  }

  #[Test, Expect(FormatException::class)]
  public function hashSignOnlyNoHost() {
    new URL('http://#');
  }

  #[Test, Expect(FormatException::class)]
  public function colonAndQuestionMarkOnlyNoHost() {
    new URL('http://:?');
  }

  #[Test, Expect(FormatException::class)]
  public function questionMarkAndColonAndOnlyNoHost() {
    new URL('http://?:');
  }

  #[Test, Expect(FormatException::class)]
  public function nonNumericPort() {
    new URL('http://example.com:ABCDEF');
  }

  #[Test, Expect(FormatException::class)]
  public function duplicatePort() {
    new URL('http://example.com:443:443');
  }

  #[Test, Expect(FormatException::class)]
  public function unclosedIPV6Brackets() {
    new URL('http://[::1');
  }

  #[Test, Expect(FormatException::class)]
  public function colonInDomainNameNotAllowed() {
    new URL('http://a:o.com/');
  }

  #[Test, Expect(FormatException::class)]
  public function percentSignInDomainNameNotAllowed() {
    new URL('http://a%o.com/');
  }

  #[Test, Expect(FormatException::class)]
  public function spaceInDomainNameNotAllowed() {
    new URL('http://a o.com/');
  }
  
  #[Test]
  public function parseEncodedAssociativeArray() {
    $u= new URL('http://example.com/ajax?load=getXML&data%5BprojectName%5D=project&data%5BlangCode%5D=en');
    $this->assertEquals(
      ['projectName' => 'project', 'langCode' => 'en'],
      $u->getParam('data')
    );
  }

  #[Test]
  public function parseUnencodedAssociativeArray() {
    $u= new URL('http://example.com/ajax?load=getXML&data[projectName]=project&data[langCode]=en');
    $this->assertEquals(
      ['projectName' => 'project', 'langCode' => 'en'],
      $u->getParam('data')
    );
  }

  #[Test]
  public function addParamAssociativeAray() {
    $u= new URL('http://example.com/ajax?load=getXML');
    $u->addParam('data', ['projectName' => 'project', 'langCode' => 'en']);
    $this->assertEquals(
      'load=getXML&data[projectName]=project&data[langCode]=en',
      $u->getQuery()
    );
  }

  #[Test]
  public function addParamsAssociativeAray() {
    $u= new URL('http://example.com/ajax?load=getXML');
    $u->addParams(['data' => ['projectName' => 'project', 'langCode' => 'en']]);
    $this->assertEquals(
      'load=getXML&data[projectName]=project&data[langCode]=en',
      $u->getQuery()
    );
  }

  #[Test]
  public function associativeArrayQueryCalculation() {
    $u= new URL('http://example.com/ajax?load=getXML&data%5BprojectName%5D=project&data%5BlangCode%5D=en');
    $this->assertEquals(
      'load=getXML&data[projectName]=project&data[langCode]=en',
      $u->getQuery()
    );
  }
  
  #[Test]
  public function associativeArrayTwoDimensionalQueryCalculation() {
    $u= new URL('http://example.com/ajax?load=getXML&data%5Bproject%5D%5BName%5D=project&data%5Bproject%5D%5BID%5D=1337&data%5BlangCode%5D=en');
    $this->assertEquals(
      'load=getXML&data[project][Name]=project&data[project][ID]=1337&data[langCode]=en',
      $u->getQuery()
    );
  }
  
  #[Test]
  public function associativeArrayMoreDimensionalQueryCalculation() {
    $u= new URL('http://example.com/ajax?load=getXML&data%5Bproject%5D%5BName%5D%5BValue%5D=project&data%5Bproject%5D%5BID%5D%5BValue%5D=1337&data%5BlangCode%5D=en');
    $this->assertEquals(
      'load=getXML&data[project][Name][Value]=project&data[project][ID][Value]=1337&data[langCode]=en',
      $u->getQuery()
    );
  }

  #[Test]
  public function getURLWithEmptyParameter() {
    $this->assertEquals('http://example.com/test?a=v1&b&c=v2', (new URL('http://example.com/test?a=v1&b=&c=v2'))->getURL());
  }

  #[Test]
  public function getURLWithParameterWithoutValue() {
    $this->assertEquals('http://example.com/test?a=v1&b&c=v2', (new URL('http://example.com/test?a=v1&b&c=v2'))->getURL());
  }

  #[Test]
  public function getURLAfterSettingEmptyQueryString() {
    $this->assertEquals('http://example.com/test', (new URL('http://example.com/test'))->setQuery('')->getURL());
  }

  #[Test]
  public function getURLAfterSettingNullQueryString() {
    $this->assertEquals('http://example.com/test', (new URL('http://example.com/test'))->setQuery(null)->getURL());
  }

  #[Test]
  public function getURLWithEmptyQueryStringConstructor() {
    $this->assertEquals('http://example.com/test', (new URL('http://example.com/test?'))->getURL());
  }

  #[Test]
  public function fragmentWithQuestionMark() {
    $url= new URL('http://example.com/path/script.html#fragment?data');
    $this->assertEquals('/path/script.html', $url->getPath());
    $this->assertEquals('fragment?data', $url->getFragment());
  }
 
  #[Test]
  public function ipv4Address() {
    $this->assertEquals('64.246.30.37', (new URL('http://64.246.30.37'))->getHost());
  }

  #[Test]
  public function ipv6Address() {
    $this->assertEquals('[::1]', (new URL('http://[::1]'))->getHost());
  }

  #[Test]
  public function ipv4AddressAndPort() {
    $u= new URL('http://64.246.30.37:8080');
    $this->assertEquals('64.246.30.37', $u->getHost());
    $this->assertEquals(8080, $u->getPort());
  }

  #[Test]
  public function ipv6AddressAndPort() {
    $u= new URL('http://[::1]:8080');
    $this->assertEquals('[::1]', $u->getHost());
    $this->assertEquals(8080, $u->getPort());
  }

  #[Test]
  public function fileUrl() {
    $u= new URL('file:///etc/passwd');
    $this->assertEquals(null, $u->getHost());
    $this->assertEquals('/etc/passwd', $u->getPath());
  }

  #[Test]
  public function hostInFileUrl() {
    $u= new URL('file://localhost/etc/passwd');
    $this->assertEquals('localhost', $u->getHost());
    $this->assertEquals('/etc/passwd', $u->getPath());
  }

  #[Test]
  public function windowsDriveInFileUrl() {
    $u= new URL('file:///c:/etc/passwd');
    $this->assertEquals(null, $u->getHost());
    $this->assertEquals('c:/etc/passwd', $u->getPath());
  }

  #[Test]
  public function windowsDriveInFileUrlWithHost() {
    $u= new URL('file://localhost/c:/etc/passwd');
    $this->assertEquals('localhost', $u->getHost());
    $this->assertEquals('c:/etc/passwd', $u->getPath());
  }

  #[Test]
  public function windowsDriveInFileUrlWithPipe() {
    $u= new URL('file:///c|/etc/passwd');
    $this->assertEquals(null, $u->getHost());
    $this->assertEquals('c:/etc/passwd', $u->getPath());
  }

  #[Test]
  public function windowsDriveInFileUrlWithPipeWithHost() {
    $u= new URL('file://localhost/c|/etc/passwd');
    $this->assertEquals('localhost', $u->getHost());
    $this->assertEquals('c:/etc/passwd', $u->getPath());
  }

  #[Test]
  public function sqliteUrl() {
    $u= new URL('sqlite:///path/to/file.db');
    $this->assertEquals(null, $u->getHost());
    $this->assertEquals('/path/to/file.db', $u->getPath());
  }

  #[Test]
  public function parseIpv6LocalhostURL() {
    $this->assertEquals('http://[::1]:80/authenticate/', (new URL('http://[::1]:80/authenticate/'))->getURL());
  }

  #[Test]
  public function parseIpv6URL() {
    $this->assertEquals('http://[2001:8d8f:1fe:5:abba:dbff:fefe:7755]:80/authenticate/', (new URL('http://[2001:8d8f:1fe:5:abba:dbff:fefe:7755]:80/authenticate/'))->getURL());
  }

  #[Test]
  public function canonicalURLScheme() {
   $this->assertEquals('https://localhost/', (new URL('https+v3://localhost'))->getCanonicalUrl());
  }

  #[Test]
  public function canonicalURLLowerCaseHost() {
    $this->assertEquals('http://localhost/', (new URL('http://LOCALHOST'))->getCanonicalUrl());
  }
  
  #[Test]
  public function failCanonicalURLLowerCaseHost() {
    $this->assertNotEquals('http://LOCALHOST/', (new URL('http://LOCALHOST'))->getCanonicalUrl());
  }
  
  #[Test]
  public function canonicalURLRemoveDefaultPort() {
    $this->assertEquals('http://localhost/', (new URL('http://localhost:80'))->getCanonicalUrl());
  }
  
  #[Test]
  public function canonicalURLPort() {
    $this->assertEquals('http://localhost:81/', (new URL('http://localhost:81'))->getCanonicalUrl());
  }
  
  #[Test]
  public function canonicalURLCapitalizeLettersInEscapeSequenceForPath() {
    $this->assertEquals('http://localhost/a%C2%B1b', (new URL('http://localhost/a%c2%b1b'))->getCanonicalUrl());
  }
  
  #[Test]
  public function canonicalURLdecodePercentEncodedOctetsForPath() {
    $this->assertEquals('http://localhost/-._~', (new URL('http://localhost/%2D%2E%5F%7E'))->getCanonicalUrl());
  }
  
  #[Test]
  public function canonicalURLremoveDotSegmentsForPath() {
    $this->assertEquals('http://localhost/a/g', (new URL('http://localhost/a/b/c/./../../g'))->getCanonicalUrl());
  }
  
  #[Test]
  public function canonicalURL() {
    $srcURL='https+v3://LOCALHOST:443/%c2/%7E?q1=%2D&q2=%b1#/a/b/c/./../../g';
    $destURL='https://localhost/%C2/~?q1=-&q2=%B1#/a/g';
    $this->assertEquals($destURL, (new URL($srcURL))->getCanonicalUrl());
  }

  #[Test]
  public function atInParams() {
    $this->assertEquals('@', (new URL('http://localhost/?q=@'))->getParam('q'));
  }

  #[Test]
  public function atInQuerystring() {
    $this->assertEquals('%40', (new URL('http://localhost/?@'))->getQuery());
  }

  #[Test]
  public function atInFragment() {
    $this->assertEquals('@', (new URL('http://localhost/#@'))->getFragment());
  }

  #[Test]
  public function atInPath() {
    $this->assertEquals('/@', (new URL('http://localhost/@'))->getPath());
  }

  #[Test]
  public function atInUserAndPath() {
    $u= new URL('http://user@localhost/@');
    $this->assertEquals('user', $u->getUser());
    $this->assertEquals('/@', $u->getPath());
  }

  #[Test, Values(['http://localhost/', 'http://localhost:8080/', 'http://localhost/path', 'http://localhost/path?query', 'http://localhost/path?query#fragment', 'http://user@localhost/path?query#fragment'])]
  public function string_representation($input) {
    $this->assertEquals($input, (new URL($input))->toString());
  }

  #[Test]
  public function string_representation_does_not_include_password() {
    $u= new URL('http://user:pass@localhost/path?query#fragment');
    $this->assertEquals('http://user:********@localhost/path?query#fragment', $u->toString());
  }

  #[Test]
  public function scalar_parameter_overwritten_by_hash() {
    $u= new URL('http://unittest.localhost/includes/orderSuccess.inc.php?&glob=1&cart_order_id=1&glob[rootDir]=http://cirt.net/rfiinc.txt?');
    $this->assertEquals(['rootDir' => 'http://cirt.net/rfiinc.txt?'], $u->getParam('glob'));
  }
}