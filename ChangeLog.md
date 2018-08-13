Networking changelog
====================

## ?.?.? / ????-??-??

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