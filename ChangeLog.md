Networking changelog
====================

## ?.?.? / ????-??-??

* Fixed *stream_select(): Argument 5 ($microseconds) should be null
  instead of 0 when argument 4 ($seconds) is null* warning in PHP 8.1
  (@thekid)

## 10.1.2 / 2021-04-15

* Fixed issue #18: IPv4 addresses using octal and hexadecimal notation
  (@thekid)

## 10.1.1 / 2021-04-10

* Fixed handling in async server for interrupted reads - @thekid

## 10.1.0 / 2021-03-31

* Implemented PR #17: Implement asynchronous server. This adds the new
  class `peer.server.AsyncServer` and changes the `handleData()` method
  in protocol to be able to return a generator.
  (@thekid)

## 10.0.2 / 2021-03-14

* Fixed issue #16: PHP 8+ compatibility: ext/sockets after it changed
  from resources to opaque objects.
  (@thekid)
* Fixed issue #15: PHP 8.1 compatibility: Socket select argument types
  (@thekid)
* Prevented warnings when building and extracting query in PHP 8.1
  (@thekid)

## 10.0.1 / 2020-07-11

* Fixed issue #14: Discover SOMAXCONN programmatically if not defined
  (@thekid)

## 10.0.0 / 2020-04-10

* Fixed "Only the first byte will be assigned to the string offset"
  warning in PHP 8.0
  (@thekid)
* Implemented xp-framework/rfc#334: Drop PHP 5.6:
  . **Heads up:** Minimum required PHP version now is PHP 7.0.0
  . Rewrote code base, grouping use statements
  . Converted `newinstance` to anonymous classes
  . Rewrote `isset(X) ? X : default` to `X ?? default`
  (@thekid)

## 9.3.3 / 2019-12-01

* Made compatible with XP 10 - @thekid

## 9.3.2 / 2019-09-17

* Fixed two more *Array and string offset access syntax with curly braces
  is deprecated* warnings
  (@thekid)

## 9.3.1 / 2019-08-25

* Made compatible with PHP 7.4 - refrain using `{}` for string offsets
  (@thekid)
* Fixed issue xp-forge/web/#57: Call to undefined method xp::stringOf()
  (@thekid)

## 9.3.0 / 2019-01-30

* Preserve keys in arrays passed to `Sockets::select()` - @thekid

## 9.2.5 / 2018-10-06

* Fixed issue #12: Changed `Sockets::select()` to return *NULL* instead of
  throwing an error when receiving `EINTR`, e.g. because of *SIGINT*.
  (@thekid)

## 9.2.4 / 2018-10-06

* Merged PR #11: Raise exceptions when pcntl extension is not loaded or
  disabled. Previously, this would fail more or less silently later on!
  (@thekid)

## 9.2.3 / 2018-08-24

* Made compatible with `xp-framework/logging` version 9.0.0 - @thekid

## 9.2.2 / 2018-08-16

* Fixed Inet4Address not implementing `lang.Value` - @thekid

## 9.2.1 / 2018-08-14

* Fixed server implementation to run housekeeping after a given timeout
  period, which defaults to 60 seconds.
  (@thekid)
* Fixed issue #10: Select(60, 0) failed - @thekid
* Fixed issue #9: ServerSocket binds tcp://::1:8080 by default - @thekid

## 9.2.0 / 2018-08-13

* Merged PR #8: Make ServerSocket use built-in streams. This removes the
  hard dependency on the *sockets* extension for the `peer.server` API.
  If it's available, the code will continue to use it as default until
  the next major release of this library. Otherwise, the built-in streams
  and PHP's stream_socket_server() will be used.
  (@thekid)

## 9.1.2 / 2018-08-06

* Fixed certificate validation problem when connecting to `localhost`
  via SSL. Issue #2 caused this problem by forcing sockets using localhost
  as hostname to connect to 127.0.0.1
  (@thekid)

## 9.1.1 / 2018-04-20

* Fixed issue #6: Missing dependency on xp-framework/logging - @thekid

## 9.1.0 / 2017-11-12

* Merged PR #5: Pass SOMAXCONN to listen() call - @thekid

## 9.0.0 / 2017-05-29

* **Heads up:** Dropped PHP 5.5 support - now requires PHP 5.6 minimum!
  (@thekid)
* Merged PR #3: XP9 Compat. **Heads up:** peer.Socket, peer.URL, the 
  INetAddr implementations in peer.net as well as peer.net.Network now
  implement `lang.Value` instead of extending `lang.Object`.
  (@thekid)

## 8.0.2 / 2017-05-23

* Trimmed error messages - those under Windows include a traling `\r\n`.
  (@thekid)

## 8.0.1 / 2017-01-16

* Fixed compatibility with PHP 7.2 - empty optional parts inside a
  regular expression seem to be returned as NULL instead of empty
  strings; see https://bugs.php.net/bug.php?id=73947
  (@thekid)

## 8.0.0 / 2016-08-28

* Added forward compatibility with XP 8.0.0 - @thekid
* Changed `localhost` to always refer to `127.0.0.1`. If you need to
  connect using IPv6, use `::1`. See issue #2 for an explanation.
  (@thekid)
* Allowed binding servers to IPv6 addresses - @thekid

## 7.1.0 / 2016-04-04

* Merged pull request #1: Separate connect & enable crypto. This way,
  SSL / TLS errors can be distinguished from connection failures.
  (@kiesel)

## 7.0.1 / 2016-02-27

* Refactored test codebase to no longer rely on net.xp_framework.unittest
  package from XP core
  (@thekid)

## 7.0.0 / 2016-02-21

* **Adopted semantic versioning. See xp-framework/rfc#300** - @thekid 
* Added version compatibility with XP 7 - @thekid

## 6.6.0 / 2014-12-09

* Extracted from the XP Framework's core - @thekid