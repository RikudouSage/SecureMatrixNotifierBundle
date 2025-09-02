# Secure Matrix Symfony notifier transport

This is a replacement for the Symfony's [Matrix notifier](https://github.com/symfony/symfony/tree/7.3/src/Symfony/Component/Notifier/Bridge/Matrix) transport which can send encrypted and verified messages, unlike the Symfony one.
It's mostly drop-in replacement, except it doesn't support disabling SSL checks.

Because the OLM library used to encrypt messages is not available in PHP, this project uses a custom [Golang library](lib) which is called using PHP FFI. By default, x86_64 and arm64 OS based on libc are supported,
if you have a different platform, you can either [open an issue](https://github.com/RikudouSage/SecureMatrixNotifierBundle/issues/new) or [build the library yourself](#building-the-library-yourself).

## Installation

`composer require rikudou/notifier-matrix-bundle`

## Usage

### Configuration

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

Don't forget to configure the notifier itself with the channel:

```yaml
framework:
    notifier:
        chatter_transports:
          matrix: matrix://example.com?accessToken=syt_someAccessToken
```

> The access token can be configured either as part of the DSN or directly in the bundle configuration. There's
> no difference, this is simply to be compatible with the Symfony Matrix notifier transport.

### Sending the message

Simply inject either `ChatterInterface` or `NotifierInterface` and send the message:

```php
final readonly class Test1
{
    public function __construct(
        private ChatterInterface $chatter,
    ) {
    }

    public function send(): void
    {
        $this->chatter->send(new ChatMessage(
            subject: 'Hello from notifier',
            options: new MatrixOptions(
                recipientId: '#test-room:your-server.com',
            ),
        ));
    }
}
```

Or using notifier:

```php
final readonly class Test2
{
    public function __construct(
        private NotifierInterface $notifier,
    ) {
    }

    public function send(): void
    {
        // you need to configure the default recipient for this to work
        // or use a custom Notification class which implements ChatNotificationInterface
        $this->notifier->send(new Notification(
            subject: 'Hello from notifier',
            channels: ['chat'],
        ));
    }
}
```

### Message options

The `MatrixOptions` class has the following options you can configure:

- recipient id
- message type
- rendering type

#### Recipient ID

Recipient ID can be in on of the following format:

- **raw room id** - the raw room id starting with `!`
- **room alias** - the room alias (like #room:example.com) starting with `#`
- **username** - the username with server (like @user:example.com) starting with `@`

> Using raw room ID is the fastest, in other cases the library has to resolve the room alias or user id
> to a room ID, which can be one (for room aliases) or multiple (for usernames) additional http calls.

#### Message type

One of the [MessageType](src/Enum/MessageType.php) enum cases, changes how the content is displayed.

#### Rendering type

How to render the content, one of the [RenderingType](src/Enum/RenderingType.php) enum cases.
Can be plaintext (default), html or markdown.

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

## Default config

Here's the autogenerated config, same as you can get using `php bin/console config:dump rikudou_matrix_notifier`:

```yaml
# Default configuration for extension with alias: "rikudou_matrix_notifier"
rikudou_matrix_notifier:

    # The path to the SQLite database which the Matrix maintains internally, it holds stuff such as room state, cryptography configuration and is needed for the bridge to function. If you lose this database, you must login again and get a new device ID.
    database_path:        '%kernel.project_dir%/var/matrix_notifier/matrix_internal.sqlite3'

    # Should be a random string of 32 bytes (can be more, but the Matrix bridge truncates it internally), used for encrypting/decrypting local account data. You can use the rikudou:notifier:matrix:initialize-keys command to generate a secure random string.
    pickle_key:           ~

    # A unique ID of the device, usually obtained by logging in. You can use the rikudou:notifier:matrix:initialize-keys command to login and generate a device ID.
    device_id:            ~

    # An access token to use with the api, usually obtained by logging in. You can use the rikudou:notifier:matrix:initialize-keys command to login and generate an access token. Can be also set as part of the notifier DSN for compatibility purposes.
    access_token:         ~

    # The recovery key for the bot account, the easiest way to get it is to login to the account using Element and copying it from there (or setting it up if you have not yet). Note that this is the most sensitive secret a Matrix account has (even more than your password), treat it with care.
    recovery_key:         ~

    # The base server url (aka hostname, optionally a port, WITHOUT scheme). Only needed if you plan to use the rikudou:notifier:matrix:initialize-keys command. Can be called as rikudou.matrix_notifier.server_hostname parameter
    server_hostname:      ~

    # You can customize the .so/.h library paths.
    lib:

        # The path to the .so library, leave at null to use the bundled one.
        library_path:         null

        # Path to the library headers, leave at null to use the bundled one.
        headers_path:         null
```

## Common errors:

- `failed to unmarshal response body: unexpected end of JSON input`: your matrix server url is wrong and the http
  client instead fetched a html page which the bridge then tried to parse as a json
- `olm account is not marked as shared, but there are keys on the server`: the internal bridge database
  is not in sync, meaning you most likely deleted it. You need to login again and provide a new device id
  and access token
