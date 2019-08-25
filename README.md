Networking for the XP Framework
===============================

[![Build Status on TravisCI](https://secure.travis-ci.org/xp-framework/networking.svg)](http://travis-ci.org/xp-framework/networking)
[![XP Framework Module](https://raw.githubusercontent.com/xp-framework/web/master/static/xp-framework-badge.png)](https://github.com/xp-framework/core)
[![BSD Licence](https://raw.githubusercontent.com/xp-framework/web/master/static/licence-bsd.png)](https://github.com/xp-framework/core/blob/master/LICENCE.md)
[![Required PHP 5.6+](https://raw.githubusercontent.com/xp-framework/web/master/static/php-5_6plus.png)](http://php.net/)
[![Supports PHP 7.0+](https://raw.githubusercontent.com/xp-framework/web/master/static/php-7_0plus.png)](http://php.net/)
[![Latest Stable Version](https://poser.pugx.org/xp-framework/networking/version.png)](https://packagist.org/packages/xp-framework/networking)

Client and server APIs

Basic low-level
---------------

```php
package peer {
  public enum peer.Sockets

  public class peer.AuthenticationException
  public class peer.BSDSocket
  public class peer.ConnectException
  public class peer.CryptoSocket
  public class peer.ProtocolException
  public class peer.SSLSocket
  public class peer.ServerSocket
  public class peer.Socket
  public class peer.SocketEndpoint
  public class peer.SocketException
  public class peer.SocketInputStream
  public class peer.SocketOutputStream
  public class peer.SocketTimeoutException
  public class peer.TLSSocket
  public class peer.UDPSocket
  public class peer.URL
}
```

Networks and DNS
----------------

```php
package peer.net {
  public interface peer.net.InetAddress

  public class peer.net.Inet4Address
  public class peer.net.Inet6Address
  public class peer.net.InetAddressFactory
  public class peer.net.NameserverLookup
  public class peer.net.Network
  public class peer.net.NetworkParser
}
```

Server
------

```php
package peer.server {
  public interface peer.server.ServerProtocol

  public class peer.server.EventServer
  public class peer.server.EventSocket
  public class peer.server.ForkingServer
  public class peer.server.PreforkingServer
  public class peer.server.Server
}

package peer.server.protocol {
  public interface peer.server.protocol.OutOfResourcesHandler
  public interface peer.server.protocol.SocketAcceptHandler
}
```