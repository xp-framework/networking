Networking changelog
====================

## ?.?.? / ????-??-??

## 8.0.0 / 2016-07-23

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