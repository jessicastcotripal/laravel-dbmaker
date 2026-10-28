## DBMaker integration for Laravel Framework
laravel-dbmaker is a DBMaker service provider for Laravel. It provides DBMaker Connection by extending the Illuminate Database component of the laravel framework.

### How to install

This package must be installed from the fork that contains the PHP 8.2 and
Laravel 9–12 compatibility fixes. Add the VCS repository and require the
`update-to-php8.2` branch in your application's `composer.json`:

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/jessicastcotripal/laravel-dbmaker.git"
    }
  ],
  "require": {
    "dbmaker/laravel-odbc": "dev-update-to-php8.2"
  }
}
```

Then run:

```bash
composer update dbmaker/laravel-odbc --with-all-dependencies
```


### Install ODBC for DBMaker

For legacy PHP versions (5.4 through 7.3 only), the precompiled extension
package can be used:

1. Download URL  <a href="https://github.com/dbmaker-go/php_ext/releases/download/1.0.0/php_dbmaker-5.4-7.3-Linux2_x86_64.tgz">https://github.com/dbmaker-go/php_ext/releases/download/1.0.0/php_dbmaker-5.4-7.3-Linux2_x86_64.tgz</a> and unzip
2. According to your DBMaker version to choice bundle or standard 
3. rename pdo_odbc.ini to 20-pdo_odbc.ini and move to /etc/php.d/
4. copy pdo_odbc.so to /usr/lib64/php/modules/
4. `php -m` Check if the installation was successful


you can follow this step
```
# wget https://github.com/dbmaker-go/php_ext/releases/download/1.0.0/php_dbmaker-5.4-7.3-Linux2_x86_64.tgz
# tar zxvf php_dbmaker-5.4-7.3-Linux2_x86_64.tgz
# mv php_dbmaker/bundle/pdo_odbc.ini /etc/php.d/20-pdo-odbc.ini
# mv php_dbmaker/bundle/pdo_odbc.so /usr/lib64/php/modules/pdo-odbc.so
# php -m
```

### PHP 8.2 in Docker

The precompiled extension above **is not compatible with PHP 8.2**. Use the
maintained [PHP extension fork](https://github.com/jessicastcotripal/php_ext)
to compile the `odbc` and `pdo_odbc` extensions against the DBMaker library
while building the image. The example below assumes DBMaker 5.4 is installed
in `/home/dbmaker/5.4` and that `libdmapic.so` is in `lib/so`.

The Dockerfile pins commit `92f4d1155481c909e0b355376b0b73926bedf33e`, which
contains the PHP 8.2.35-compatible `82.patch`.

The image must install `git`, `patch`, build tools, and the DBMaker headers
before running this step. The BCP API Dockerfile already provides these
dependencies.

```dockerfile
RUN set -eux; \
    git clone --depth 1 --branch update-php-82 https://github.com/jessicastcotripal/php_ext.git /tmp/php-ext; \
    test "$(git -C /tmp/php-ext rev-parse HEAD)" = "92f4d1155481c909e0b355376b0b73926bedf33e"; \
    docker-php-source extract; \
    patch -p1 -d /usr/src/php/ext/pdo_odbc < /tmp/php-ext/82.patch; \
    sed -i '1iAC_DEFUN([PHP_ALWAYS_SHARED],[])dnl' /usr/src/php/ext/odbc/config.m4; \
    CUSTOM_ODBC_LIBS="-ldmapic" docker-php-ext-configure odbc --with-custom-odbc=shared,/home/dbmaker/5.4 --with-libdir="lib/so"; \
    docker-php-ext-configure pdo_odbc --with-pdo-odbc=generic,/home/dbmaker/5.4,dmapic --with-libdir="lib/so"; \
    docker-php-ext-install -j$(nproc) odbc pdo_odbc pdo_mysql; \
    docker-php-source delete; \
    rm -rf /tmp/php-ext; \
    php -m | grep -E 'odbc|pdo_odbc|pdo_mysql'
```

The DBMaker driver does not support `SQL_ATTR_CP_MATCH`. Disable PDO ODBC
connection pooling in the image to avoid `SQLSTATE[HYC00] ... driver not capable`:

```ini
pdo_odbc.connection_pooling=off
```

The DBMaker runtime also requires the following environment variables:

```dockerfile
ENV DBMAKER=/home/dbmaker/5.4/
ENV DCI_CONFIG=/home/dbmaker/5.4/dci.cfg
ENV LD_LIBRARY_PATH=/home/dbmaker/5.4/lib/so
```


Note: If your default installation directory of DBMaker standard is not `/home/dbmaker/5.4` or bundle is not `/opt/dbmaker`, please add installation directory to LD_LIBRARY_PATH environment variable.


### Usage Instructions
It's very simple to configure:

**1) Add database to database.php file**
```PHP
'dbmaker' => [
    'driver' => 'odbc',
    'dsn' => 'odbc:DRIVER=/home/dbmaker/5.4/lib/so/libdmapic.so;DATABASE=COTRIPALDB_SIMPLUS_BASE',
    'database' => 'COTRIPALDB_SIMPLUS_BASE',
    'host' => 'localhost',
    'username' => 'username',
    'password' => 'password',
    'options' => [
            'dbidcap' => 1 // Set this to the same value as the server DB_IDCAP.
    ]
]
```

**2) set default database to dbmaker**
```PHP
'default' =>  'dbmaker',
```

**3) testing**

```
# php artisan make:command MyCommand
```

```
# vi app/Console/Commands/MyCommand.php
```

find 
```
protected $signature = 'command:name';
```

change to
```
protected $signature = 'my:command';
```

and add test code
```
public function handle()
{
    $data= \DB::table('TA1')->get('C1');
	print_R($data);
}
```

```
# vi app/Console/Kernel.php
```

```
protected $commands = [
    // ...
    Commands\MyCommand::class,  //add this
];
```

run
```
# php artisan my:command
```


### Laravel DB Usage

Consult the <a href="http://laravel.com/docs" rel="nofollow">Laravel framework documentation</a>
