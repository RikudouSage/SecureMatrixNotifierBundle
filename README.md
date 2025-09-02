# Secure Matrix Symfony notifier transport

This is a replacement for the Symfony's [Matrix notifier](https://github.com/symfony/symfony/tree/7.3/src/Symfony/Component/Notifier/Bridge/Matrix) transport which can send encrypted and verified messages, unlike the Symfony one.
It's mostly drop-in replacement, except it doesn't support disabling SSL checks.

Because the OLM library used to encrypt messages is not available in PHP, this project uses a custom [Golang library](lib) which is called using PHP FFI. By default, x86_64 and arm64 OS based on libc are supported,
if you have a different platform, you can either [open an issue](https://github.com/RikudouSage/SecureMatrixNotifierBundle/issues/new) or [build the library yourself](#building-the-library-yourself).

## Installation

`composer require rikudou/notifier-matrix-bundle`

## Usage

> Tip: Generate the default config using `php bin/console config:dump rikudou_matrix_notifier > config/packages/rikudou_matrix_notifier.yaml`

You need the following to correctly configure the bundle:

- **access token** (used for accessing the api itself)
- **recovery key** (used to verify your session)
- **pickle key** (a random string used for encryption)
- **device id** (part of marking your session as verified)
- **persistent sqlite database** (used to store necessary metadata)

The good news is that in addition to the database being initialized automatically so you only need to provide the path to it,
there's [a command](src/Command/InitializeCommand.php) that can help you with most of those! If you provide it with username and password,
it will fetch the **access token** and the **device id** and will automatically generate a random **pickle key** for you:

`php bin/console rikudou:notifier:matrix:initialize-keys`

Without any arguments it will simply ask for everything. You can also provide all the arguments directly, but providing
the password like that is discouraged.

Afterwards put the values into your config, preferably using environment variables.

## Building the library yourself

You need Golang 1.24 or later. After that simply go to the [lib](lib) directory and run:

`go build -buildmode c-shared -o custom_bridge.so .`

This will create two files: `custom_bridge.so` and `custom_bridge.h`. You can either delete all the boilerplate in the
`custom_bridge.h` or you can simply use the [default header files](lib/out/x86/libmatrix.linux.x86.64.h) (unless
you of course changed the method signatures in the library). Afterwards you can simply provide your .so to the bridge:

```php
<?php

use Rikudou\MatrixNotifier\Bridge\GolangLibBridge;

// using default headers
$bridge = new GolangLibBridge(libraryPath: '/path/to/custom_bridge.so');

// custom headers
$bridge = new GolangLibBridge(libraryPath: '/path/to/custom_bridge.so', headerPath: '/path/to/custom_bridge.h');
```

## Common errors:

- `failed to unmarshal response body: unexpected end of JSON input`: your matrix server url is wrong and the http
  client instead fetched a html page which the bridge then tried to parse as a json
- `olm account is not marked as shared, but there are keys on the server`: the internal bridge database
  is not in sync, meaning you most likely deleted it. You need to login again and provide a new device id
  and access token
