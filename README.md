# php-sysv-ipc-semaphore

This package is wrapper for php sysv sem_xxx. And with `string $name`

## Installing

from Packagist

```shell
composer require takuya/php-sysv-ipc-semaphore
```

from GitHub

```shell
name='php-sysv-ipc-semaphore'
composer config repositories.$name \
vcs https://github.com/takuya/$name  
composer require takuya/$name:master
composer install
```

## Examples

```php
<?php
$uniq_name = 'semphore_name';
$semaphore = new IPCSemaphore($uniq_name);
$semaphore->acquire();// first acquire must be success.
$semaphore->acquire(true);// multiple acquire must be failed.
$semaphore->release();
//
// remove from IPC
//
$semaphore->destroy();
```

### semaphore and thread-mutex

Compare to Thread and SyncMutex , SysV semaphore has one big advantage in PHP.

SysV function (ex `sem_get`) is bundled with PHP, no required PECL.

But, `sem_get()` does not accept string named. This package utilize pseudo string $name.  

### remove ipc by manually

If unused ipc remains. use SHELL command to remove.

```shell
ipcs -s | grep $USER | grep -oE '0x[a-f0-9]+' | xargs -I@ ipcrm --semaphore-key @
```




