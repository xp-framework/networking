<?php namespace peer\unittest;

use lang\{FormatException, IllegalArgumentException};
use peer\URL;
use unittest\{Assert, Expect, Test, Values};

class URLTest {

  #[Test]
  public function scheme() {
    Assert::equals('http', (new URL('http://localhost'))->getScheme());
  }

  #[Test]
  public function schemeWithPlus() {
    Assert::equals('svn+ssl', (new URL('svn+ssl://localhost'))->getScheme());
  }

  #[Test]
  public function schemeMutability() {
    Assert::equals(
      'ftp://localhost', 
      (new URL('http://localhost'))->setScheme('ftp')->getURL()
    );
  }

  #[Test]
  public function host() {
    Assert::equals('localhost', (new URL('http://localhost'))->getHost());
  }

  #[Test]
  public function uppercaseHost() {
    Assert::equals('TEST', (new URL('http://TEST'))->getHost());
  }

  #[Test]
  public function hostMutability() {
    Assert::equals(
      'http://127.0.0.1', 
      (new URL('http://localhost'))->setHost('127.0.0.1')->getURL()
    );
  }

  #[Test]
  public function path() {
    Assert::equals('/news/index.html', (new URL('http://localhost/news/index.html'))->getPath());
  }

  #[Test]
  public function emptyPath() {
    Assert::equals(null, (new URL('http://localhost'))->getPath());
  }

  #[Test]
  public function slashPath() {
    Assert::equals('/', (new URL('http://localhost/'))->getPath());
  }

  #[Test]
  public function pathDefault() {
    Assert::equals('/', (new URL('http://localhost'))->getPath('/'));
  }

  #[Test]
  public function pathMutability() {
    Assert::equals(
      'http://localhost/index.html', 
      (new URL('http://localhost'))->setPath('/index.html')->getURL()
    );
  }

  #[Test]
  public function user() {
    Assert::equals('user', (new URL('http://user@localhost'))->getUser());
  }

  #[Test]
  public function emptyUser() {
    Assert::equals(null, (new URL('http://localhost'))->getUser());
  }

  #[Test]
  public function userDefault() {
    Assert::equals('nobody', (new URL('http://localhost'))->getUser('nobody'));
  }

  #[Test]
  public function urlEncodedUser() {
    Assert::equals('user?', (new URL('http://user%3F@localhost'))->getUser());
  }

  #[Test]
  public function setUrlEncodedUser() {
    Assert::equals('http://user%3F@localhost', (new URL('http://localhost'))->setUser('user?')->getURL());
  }

  #[Test]
  public function userMutability() {
    Assert::equals(
      'http://thekid@localhost', 
      (new URL('http://localhost'))->setUser('thekid')->getURL()
    );
  }

  #[Test]
  public function password() {
    Assert::equals('password', (new URL('http://user:password@localhost'))->getPassword());
  }

  #[Test]
  public function urlEncodedPassword() {
    Assert::equals('pass?word', (new URL('http://user:pass%3Fword@localhost'))->getPassword());
  }

  #[Test]
  public function setUrlEncodedPassword() {
    Assert::equals('http://user:pass%3Fword@localhost', (new URL('http://user@localhost'))->setPassword('pass?word')->getURL());
  }

  #[Test]
  public function emptyPassword() {
    Assert::equals(null, (new URL('http://localhost'))->getPassword());
  }

  #[Test]
  public function passwordDefault() {
    Assert::equals('secret', (new URL('http://user@localhost'))->getPassword('secret'));
  }

  #[Test]
  public function passwordMutability() {
    Assert::equals(
      'http://anon:anon@localhost', 
      (new URL('http://anon@localhost'))->setPassword('anon')->getURL()
    );
  }

  #[Test]
  public function query() {
    Assert::equals('a=b', (new URL('http://localhost?a=b'))->getQuery());
  }

  #[Test]
  public function queryModifiedByParams() {
    Assert::equals(
      'a=b&c=d', 
      (new URL('http://localhost?a=b'))->addParam('c', 'd')->getQuery()
    );
  }

  #[Test]
  public function emptyQuery() {
    Assert::equals(null, (new URL('http://localhost'))->getQuery());
  }

  #[Test]
  public function parameterLessQuery() {
    Assert::equals('1549', (new URL('http://localhost/?1549'))->getQuery());
  }

  #[Test]
  public function addToParameterLessQuery() {
    Assert::equals('1549&a=b', (new URL('http://localhost/?1549'))->addParam('a', 'b')->getQuery());
  }

  #[Test]
  public function ParameterLessQueryWithAdd() {
    Assert::equals('1549', (new URL('http://localhost/'))->addParam('1549')->getQuery());
  }

  #[Test]
  public function ParameterLessQueryWithSet() {
    Assert::equals('1549', (new URL('http://localhost/'))->setParam('1549')->getQuery());
  }

  #[Test]
  public function questionMarkOnly() {
    Assert::equals(null, (new URL('http://localhost?'))->getQuery());
  }

  #[Test]
  public function questionMarkAndFragmentOnly() {
    Assert::equals(null, (new URL('http://localhost?#'))->getQuery());
  }

  #[Test]
  public function queryDefault() {
    Assert::equals('1,2,3', (new URL('http://localhost'))->getQuery('1,2,3'));
  }

  #[Test]
  public function queryMutability() {
    Assert::equals(
      'http://localhost?a=b', 
      (new URL('http://localhost'))->setQuery('a=b')->getURL()
    );
  }

  #[Test]
  public function getParameterLessQuery() {
    Assert::equals('', (new URL('http://localhost/?1549'))->getParam('1549'));
  }

  #[Test]
  public function hasParameterLessQuery() {
    Assert::true((new URL('http://localhost/?1549'))->hasParam('1549'));
  }

  #[Test]
  public function fragment() {
    Assert::equals('top', (new URL('http://localhost#top'))->getFragment());
  }

  #[Test]
  public function fragmentWithSlash() {
    Assert::equals('top', (new URL('http://localhost/#top'))->getFragment());
  }

  #[Test]
  public function fragmentWithSlashAndQuestionMark() {
    Assert::equals('top', (new URL('http://localhost/?#top'))->getFragment());
  }

  #[Test]
  public function fragmentWithQuery() {
    Assert::equals('top', (new URL('http://localhost/?query#top'))->getFragment());
  }

  #[Test]
  public function emptyFragment() {
    Assert::equals(null, (new URL('http://localhost'))->getFragment());
  }

  #[Test]
  public function hashOnly() {
    Assert::equals(null, (new URL('http://localhost#'))->getFragment());
  }

  #[Test]
  public function hashAtEnd() {
    Assert::equals(null, (new URL('http://localhost?#'))->getFragment());
  }

  #[Test]
  public function hashAtEndWithQuery() {
    Assert::equals(null, (new URL('http://localhost?query#'))->getFragment());
  }

  #[Test]
  public function fragmentDefault() {
    Assert::equals('top', (new URL('http://localhost'))->getFragment('top'));
  }

  #[Test]
  public function fragmentMutability() {
    Assert::equals(
      'http://localhost#list', 
      (new URL('http://localhost'))->setFragment('list')->getURL()
    );
  }

  #[Test]
  public function port() {
    Assert::equals(8080, (new URL('http://localhost:8080'))->getPort());
  }

  #[Test]
  public function emptyPort() {
    Assert::equals(null, (new URL('http://localhost'))->getPort());
  }

  #[Test]
  public function portDefault() {
    Assert::equals(80, (new URL('http://localhost'))->getPort(80));
  }

  #[Test]
  public function portMutability() {
    Assert::equals(
      'http://localhost:8081', 
      (new URL('http://localhost'))->setPort(8081)->getURL()
    );
  }

  #[Test]
  public function param() {
    Assert::equals('b', (new URL('http://localhost?a=b'))->getParam('a'));
  }

  #[Test]
  public function getArrayParameter() {
    Assert::equals(['b'], (new URL('http://localhost?a[]=b'))->getParam('a'));
  }

  #[Test]
  public function getEncodedArrayParameter() {
    Assert::equals(['='], (new URL('http://localhost?a[]=%3D'))->getParam('a'));
  }

  #[Test]
  public function getArrayParameters() {
    Assert::equals(['b', 'c'], (new URL('http://localhost?a[]=b&a[]=c'))->getParam('a'));
  }

  #[Test]
  public function getArrayParametersAsHash() {
    Assert::equals(
      ['name' => 'b', 'color' => 'c'],
      (new URL('http://localhost?a[name]=b&a[color]=c'))->getParam('a')
    );
  }

  #[Test]
  public function getArrayParametersAsHashWithEncodedNames() {
    Assert::equals(
      ['=name=' => 'b', '=color=' => 'c'],
      (new URL('http://localhost?a[%3Dname%3D]=b&a[%3Dcolor%3D]=c'))->getParam('a')
    );
  }

  #[Test]
  public function arrayOffsetsInDifferentArrays() {
    Assert::equals(
      ['a' => ['c'], 'b' => ['d']],
      (new URL('http://localhost/?a[]=c&b[]=d'))->getParams()
    );
  }

  #[Test]
  public function duplicateOffsetsOverwriteEachother() {
    Assert::equals(
      ['c'], 
      (new URL('http://localhost/?a[0]=b&a[0]=c'))->getParam('a')
    );
  }

  #[Test]
  public function duplicateNamesOverwriteEachother() {
    Assert::equals(
      ['name' => 'c'],
      (new URL('http://localhost/?a[name]=b&a[name]=c'))->getParam('a')
    );
  }

  #[Test]
  public function twoDimensionalArray() {
    Assert::equals(
      [['b']], 
      (new URL('http://localhost/?a[][]=b'))->getParam('a')
    );
  }

  #[Test]
  public function threeDimensionalArray() {
    Assert::equals(
      [[['b']]],
      (new URL('http://localhost/?a[][][]=b'))->getParam('a')
    );
  }

  #[Test]
  public function arrayOfHash() {
    Assert::equals(
      [[['name' => 'b']]],
      (new URL('http://localhost/?a[][][name]=b'))->getParam('a')
    );
  }

  #[Test]
  public function hashOfArray() {
    Assert::equals(
      ['name' => [['b']]],
      (new URL('http://localhost/?a[name][][]=b'))->getParam('a')
    );
  }

  #[Test]
  public function hashOfArrayOfHash() {
    Assert::equals(
      ['name' => [['key' => 'b']]],
      (new URL('http://localhost/?a[name][][key]=b'))->getParam('a')
    );
  }

  #[Test]
  public function hashNotationWithoutValues() {
    Assert::equals(
      ['name' => '', 'color' => ''],
      (new URL('http://localhost/?a[name]&a[color]'))->getParam('a')
    );
  }

  #[Test]
  public function arrayNotationWithoutValues() {
    Assert::equals(
      ['', ''],
      (new URL('http://localhost/?a[]&a[]'))->getParam('a')
    );
  }

  #[Test]
  public function getArrayParams() {
    Assert::equals(
      ['a' => ['b', 'c']],
      (new URL('http://localhost?a[]=b&a[]=c'))->getParams()
    );
  }

  #[Test]
  public function mixedOffsetsAndKeys() {
    Assert::equals(
      [0 => 'b', 'name' => 'c', 1 => 'd'],
      (new URL('http://localhost/?a[]=b&a[name]=c&a[]=d'))->getParam('a')
    );
  }

  #[Test]
  public function nestedBraces() {
    Assert::equals(
      ['a' => ['nested[]' => 'b']],
      (new URL('http://localhost/?a[nested[]]=b'))->getParams()
    );
  }
 
  #[Test]
  public function nestedBracesTwice() {
    Assert::equals(
      ['a' => ['nested[a]' => 'b', 'nested[b]' => 'c']],
      (new URL('http://localhost/?a[nested[a]]=b&a[nested[b]]=c'))->getParams()
    );
  }

  #[Test]
  public function nestedBracesChained() {
    Assert::equals(
      ['a' => ['nested[a]' => ['c']]],
      (new URL('http://localhost/?a[nested[a]][]=c'))->getParams()
    );
  }

  #[Test]
  public function unnamedArrayParameterDoesNotArray() {
    Assert::equals(
      ['[]' => 'c'],
      (new URL('http://localhost/?[]=c'))->getParams()
    );
  }

  #[Test]
  public function nonExistantParam() {
    Assert::equals(null, (new URL('http://localhost?a=b'))->getParam('b'));
  }

  #[Test]
  public function emptyParam() {
    Assert::equals('', (new URL('http://localhost?x='))->getParam('x'));
  }

  #[Test]
  public function paramDefault() {
    Assert::equals('x', (new URL('http://localhost?a=b'))->getParam('c', 'x'));
  }
 
  #[Test]
  public function addNewParam() {
    Assert::equals(
      'http://localhost?a=b', 
      (new URL('http://localhost'))->addParam('a', 'b')->getURL()
    );
  }

  #[Test]
  public function setNewParam() {
    Assert::equals(
      'http://localhost?a=b', 
      (new URL('http://localhost'))->setParam('a', 'b')->getURL()
    );
  }

  #[Test]
  public function addAdditionalParam() {
    Assert::equals(
      'http://localhost?a=b&c=d', 
      (new URL('http://localhost?a=b'))->addParam('c', 'd')->getURL()
    );
  }

  #[Test]
  public function setAdditionalParam() {
    Assert::equals(
      'http://localhost?a=b&c=d', 
      (new URL('http://localhost?a=b'))->setParam('c', 'd')->getURL()
    );
  }

  #[Test]
  public function addAdditionalParamChained() {
    Assert::equals(
      'http://localhost?a=b&c=d&e=f', 
      (new URL('http://localhost?a=b'))->addParam('c', 'd')->addParam('e', 'f')->getURL()
    );
  }

  #[Test]
  public function setAdditionalParamChained() {
    Assert::equals(
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
    Assert::equals(
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
    Assert::equals($original, $u->getURL());
  }

  #[Test]
  public function setExistingParams() {
    Assert::equals(
      'http://localhost?a=c', 
      (new URL('http://localhost?a=b'))->setParams(['a' => 'c'])->getURL()
    );
  }

  #[Test]
  public function addNewParams() {
    Assert::equals(
      'http://localhost?a=b&c=d', 
      (new URL('http://localhost'))->addParams(['a' => 'b', 'c' => 'd'])->getURL()
    );
  }

  #[Test]
  public function setNewParams() {
    Assert::equals(
      'http://localhost?a=b&c=d', 
      (new URL('http://localhost'))->setParams(['a' => 'b', 'c' => 'd'])->getURL()
    );
  }

  #[Test]
  public function addAdditionalParams() {
    Assert::equals(
      'http://localhost?z=x&a=b&c=d', 
      (new URL('http://localhost?z=x'))->addParams(['a' => 'b', 'c' => 'd'])->getURL()
    );
  }

  #[Test]
  public function setAdditionalParams() {
    Assert::equals(
      'http://localhost?z=x&a=b&c=d', 
      (new URL('http://localhost?z=x'))->setParams(['a' => 'b', 'c' => 'd'])->getURL()
    );
  }

  #[Test]
  public function addArrayParam() {
    $u= new URL('http://localhost/');
    $u->addParam('x', ['y', 'z']);
    Assert::equals('http://localhost/?x[]=y&x[]=z', $u->getURL());
  }

  #[Test]
  public function setArrayParam() {
    $u= new URL('http://localhost/');
    $u->setParam('x', ['y', 'z']);
    Assert::equals('http://localhost/?x[]=y&x[]=z', $u->getURL());
  }

  #[Test]
  public function params() {
    Assert::equals(['a' => 'b', 'c' => 'd'], (new URL('http://localhost?a=b&c=d'))->getParams());
  }

  #[Test]
  public function withParams() {
    Assert::true((new URL('http://localhost?a=b&c=d'))->hasParams());
  }

  #[Test]
  public function withArrayParams() {
    Assert::true((new URL('http://localhost?a[]=b&a[]=d'))->hasParams());
  }

  #[Test]
  public function noParams() {
    Assert::false((new URL('http://localhost'))->hasParams());
  }

  #[Test]
  public function withParam() {
    Assert::true((new URL('http://localhost?a=b&c=d'))->hasParam('a'));
  }

  #[Test]
  public function withArrayParam() {
    Assert::true((new URL('http://localhost?a[]=b&a[]=d'))->hasParam('a'));
  }

  #[Test]
  public function withNonExistantParam() {
    Assert::false((new URL('http://localhost?a=b&c=d'))->hasParam('d'));
  }

  #[Test]
  public function noParam() {
    Assert::false((new URL('http://localhost'))->hasParam('a'));
  }

  #[Test]
  public function hasDotParam() {
    Assert::true((new URL('http://localhost/?a.b=c'))->hasParam('a.b'));
  }

  #[Test]
  public function getDotParam() {
    Assert::equals('c', (new URL('http://localhost/?a.b=c'))->getParam('a.b'));
  }

  #[Test]
  public function getDotParams() {
    Assert::equals(['a.b' => 'c'], (new URL('http://localhost/?a.b=c'))->getParams());
  }

  #[Test]
  public function addDotParam() {
    Assert::equals('a.b=c', (new URL('http://localhost/'))->addParam('a.b', 'c')->getQuery());
  }

  #[Test]
  public function removeExistingParam() {
    Assert::equals(new URL('http://localhost'), (new URL('http://localhost?a=b'))->removeParam('a'));
  }

  #[Test]
  public function removeNonExistantParam() {
    Assert::equals(new URL('http://localhost'), (new URL('http://localhost'))->removeParam('a'));
  }

  #[Test]
  public function removeExistingArrayParam() {
    Assert::equals(new URL('http://localhost'), (new URL('http://localhost?a[]=b&a[]=c'))->removeParam('a'));
  }

  #[Test]
  public function sameUrlsAreEqual() {
    Assert::equals(new URL('http://localhost'), new URL('http://localhost'));
  }

  #[Test]
  public function differentUrlsAreNotEqual() {
    Assert::notequals(new URL('http://localhost'), new URL('http://example.com'));
  }

  #[Test]
  public function hashCodesForSameUrls() {
    Assert::equals(
      (new URL('http://localhost'))->hashCode(), 
      (new URL('http://localhost'))->hashCode()
    );
  }

  #[Test]
  public function hashCodesForDifferentUrls() {
    Assert::notequals(
      (new URL('http://localhost'))->hashCode(), 
      (new URL('ftp://localhost'))->hashCode()
    );
  }

  #[Test]
  public function hashCodeRecalculated() {
    $u= new URL('http://localhost');
    $u->addParam('a', 'b');
    
    Assert::notequals(
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
    Assert::equals('foo+v2', (new URL('foo+v2://host'))->getScheme());
  }

  #[Test]
  public function oneLetterScheme() {
    Assert::equals('f', (new URL('f://host'))->getScheme());
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
    Assert::equals(
      ['projectName' => 'project', 'langCode' => 'en'],
      $u->getParam('data')
    );
  }

  #[Test]
  public function parseUnencodedAssociativeArray() {
    $u= new URL('http://example.com/ajax?load=getXML&data[projectName]=project&data[langCode]=en');
    Assert::equals(
      ['projectName' => 'project', 'langCode' => 'en'],
      $u->getParam('data')
    );
  }

  #[Test]
  public function addParamAssociativeAray() {
    $u= new URL('http://example.com/ajax?load=getXML');
    $u->addParam('data', ['projectName' => 'project', 'langCode' => 'en']);
    Assert::equals(
      'load=getXML&data[projectName]=project&data[langCode]=en',
      $u->getQuery()
    );
  }

  #[Test]
  public function addParamsAssociativeAray() {
    $u= new URL('http://example.com/ajax?load=getXML');
    $u->addParams(['data' => ['projectName' => 'project', 'langCode' => 'en']]);
    Assert::equals(
      'load=getXML&data[projectName]=project&data[langCode]=en',
      $u->getQuery()
    );
  }

  #[Test]
  public function associativeArrayQueryCalculation() {
    $u= new URL('http://example.com/ajax?load=getXML&data%5BprojectName%5D=project&data%5BlangCode%5D=en');
    Assert::equals(
      'load=getXML&data[projectName]=project&data[langCode]=en',
      $u->getQuery()
    );
  }
  
  #[Test]
  public function associativeArrayTwoDimensionalQueryCalculation() {
    $u= new URL('http://example.com/ajax?load=getXML&data%5Bproject%5D%5BName%5D=project&data%5Bproject%5D%5BID%5D=1337&data%5BlangCode%5D=en');
    Assert::equals(
      'load=getXML&data[project][Name]=project&data[project][ID]=1337&data[langCode]=en',
      $u->getQuery()
    );
  }
  
  #[Test]
  public function associativeArrayMoreDimensionalQueryCalculation() {
    $u= new URL('http://example.com/ajax?load=getXML&data%5Bproject%5D%5BName%5D%5BValue%5D=project&data%5Bproject%5D%5BID%5D%5BValue%5D=1337&data%5BlangCode%5D=en');
    Assert::equals(
      'load=getXML&data[project][Name][Value]=project&data[project][ID][Value]=1337&data[langCode]=en',
      $u->getQuery()
    );
  }

  #[Test]
  public function getURLWithEmptyParameter() {
    Assert::equals('http://example.com/test?a=v1&b&c=v2', (new URL('http://example.com/test?a=v1&b=&c=v2'))->getURL());
  }

  #[Test]
  public function getURLWithParameterWithoutValue() {
    Assert::equals('http://example.com/test?a=v1&b&c=v2', (new URL('http://example.com/test?a=v1&b&c=v2'))->getURL());
  }

  #[Test]
  public function getURLAfterSettingEmptyQueryString() {
    Assert::equals('http://example.com/test', (new URL('http://example.com/test'))->setQuery('')->getURL());
  }

  #[Test]
  public function getURLAfterSettingNullQueryString() {
    Assert::equals('http://example.com/test', (new URL('http://example.com/test'))->setQuery(null)->getURL());
  }

  #[Test]
  public function getURLWithEmptyQueryStringConstructor() {
    Assert::equals('http://example.com/test', (new URL('http://example.com/test?'))->getURL());
  }

  #[Test]
  public function fragmentWithQuestionMark() {
    $url= new URL('http://example.com/path/script.html#fragment?data');
    Assert::equals('/path/script.html', $url->getPath());
    Assert::equals('fragment?data', $url->getFragment());
  }
 
  #[Test]
  public function ipv4Address() {
    Assert::equals('64.246.30.37', (new URL('http://64.246.30.37'))->getHost());
  }

  #[Test]
  public function ipv6Address() {
    Assert::equals('[::1]', (new URL('http://[::1]'))->getHost());
  }

  #[Test]
  public function ipv4AddressAndPort() {
    $u= new URL('http://64.246.30.37:8080');
    Assert::equals('64.246.30.37', $u->getHost());
    Assert::equals(8080, $u->getPort());
  }

  #[Test]
  public function ipv6AddressAndPort() {
    $u= new URL('http://[::1]:8080');
    Assert::equals('[::1]', $u->getHost());
    Assert::equals(8080, $u->getPort());
  }

  #[Test]
  public function fileUrl() {
    $u= new URL('file:///etc/passwd');
    Assert::equals(null, $u->getHost());
    Assert::equals('/etc/passwd', $u->getPath());
  }

  #[Test]
  public function hostInFileUrl() {
    $u= new URL('file://localhost/etc/passwd');
    Assert::equals('localhost', $u->getHost());
    Assert::equals('/etc/passwd', $u->getPath());
  }

  #[Test]
  public function windowsDriveInFileUrl() {
    $u= new URL('file:///c:/etc/passwd');
    Assert::equals(null, $u->getHost());
    Assert::equals('c:/etc/passwd', $u->getPath());
  }

  #[Test]
  public function windowsDriveInFileUrlWithHost() {
    $u= new URL('file://localhost/c:/etc/passwd');
    Assert::equals('localhost', $u->getHost());
    Assert::equals('c:/etc/passwd', $u->getPath());
  }

  #[Test]
  public function windowsDriveInFileUrlWithPipe() {
    $u= new URL('file:///c|/etc/passwd');
    Assert::equals(null, $u->getHost());
    Assert::equals('c:/etc/passwd', $u->getPath());
  }

  #[Test]
  public function windowsDriveInFileUrlWithPipeWithHost() {
    $u= new URL('file://localhost/c|/etc/passwd');
    Assert::equals('localhost', $u->getHost());
    Assert::equals('c:/etc/passwd', $u->getPath());
  }

  #[Test]
  public function sqliteUrl() {
    $u= new URL('sqlite:///path/to/file.db');
    Assert::equals(null, $u->getHost());
    Assert::equals('/path/to/file.db', $u->getPath());
  }

  #[Test]
  public function parseIpv6LocalhostURL() {
    Assert::equals('http://[::1]:80/authenticate/', (new URL('http://[::1]:80/authenticate/'))->getURL());
  }

  #[Test]
  public function parseIpv6URL() {
    Assert::equals('http://[2001:8d8f:1fe:5:abba:dbff:fefe:7755]:80/authenticate/', (new URL('http://[2001:8d8f:1fe:5:abba:dbff:fefe:7755]:80/authenticate/'))->getURL());
  }

  #[Test]
  public function canonicalURLScheme() {
   Assert::equals('https://localhost/', (new URL('https+v3://localhost'))->getCanonicalUrl());
  }

  #[Test]
  public function canonicalURLLowerCaseHost() {
    Assert::equals('http://localhost/', (new URL('http://LOCALHOST'))->getCanonicalUrl());
  }
  
  #[Test]
  public function failCanonicalURLLowerCaseHost() {
    Assert::notequals('http://LOCALHOST/', (new URL('http://LOCALHOST'))->getCanonicalUrl());
  }
  
  #[Test]
  public function canonicalURLRemoveDefaultPort() {
    Assert::equals('http://localhost/', (new URL('http://localhost:80'))->getCanonicalUrl());
  }
  
  #[Test]
  public function canonicalURLPort() {
    Assert::equals('http://localhost:81/', (new URL('http://localhost:81'))->getCanonicalUrl());
  }
  
  #[Test]
  public function canonicalURLCapitalizeLettersInEscapeSequenceForPath() {
    Assert::equals('http://localhost/a%C2%B1b', (new URL('http://localhost/a%c2%b1b'))->getCanonicalUrl());
  }
  
  #[Test]
  public function canonicalURLdecodePercentEncodedOctetsForPath() {
    Assert::equals('http://localhost/-._~', (new URL('http://localhost/%2D%2E%5F%7E'))->getCanonicalUrl());
  }
  
  #[Test]
  public function canonicalURLremoveDotSegmentsForPath() {
    Assert::equals('http://localhost/a/g', (new URL('http://localhost/a/b/c/./../../g'))->getCanonicalUrl());
  }
  
  #[Test]
  public function canonicalURL() {
    $srcURL='https+v3://LOCALHOST:443/%c2/%7E?q1=%2D&q2=%b1#/a/b/c/./../../g';
    $destURL='https://localhost/%C2/~?q1=-&q2=%B1#/a/g';
    Assert::equals($destURL, (new URL($srcURL))->getCanonicalUrl());
  }

  #[Test]
  public function atInParams() {
    Assert::equals('@', (new URL('http://localhost/?q=@'))->getParam('q'));
  }

  #[Test]
  public function atInQuerystring() {
    Assert::equals('%40', (new URL('http://localhost/?@'))->getQuery());
  }

  #[Test]
  public function atInFragment() {
    Assert::equals('@', (new URL('http://localhost/#@'))->getFragment());
  }

  #[Test]
  public function atInPath() {
    Assert::equals('/@', (new URL('http://localhost/@'))->getPath());
  }

  #[Test]
  public function atInUserAndPath() {
    $u= new URL('http://user@localhost/@');
    Assert::equals('user', $u->getUser());
    Assert::equals('/@', $u->getPath());
  }

  #[Test, Values(['http://localhost/', 'http://localhost:8080/', 'http://localhost/path', 'http://localhost/path?query', 'http://localhost/path?query#fragment', 'http://user@localhost/path?query#fragment'])]
  public function string_representation($input) {
    Assert::equals($input, (new URL($input))->toString());
  }

  #[Test]
  public function string_representation_does_not_include_password() {
    $u= new URL('http://user:pass@localhost/path?query#fragment');
    Assert::equals('http://user:********@localhost/path?query#fragment', $u->toString());
  }

  #[Test]
  public function scalar_parameter_overwritten_by_hash() {
    $u= new URL('http://unittest.localhost/includes/orderSuccess.inc.php?&glob=1&cart_order_id=1&glob[rootDir]=http://cirt.net/rfiinc.txt?');
    Assert::equals(['rootDir' => 'http://cirt.net/rfiinc.txt?'], $u->getParam('glob'));
  }
}