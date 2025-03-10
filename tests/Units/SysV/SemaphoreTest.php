<?php

namespace Tests\Units\SysV;

use Tests\TestCase;
use Takuya\SysV\IPCSemaphore;
use function Takuya\Helpers\str_rand;

class SemaphoreTest extends TestCase {
  
  public function test_sysv_semaphore() {
    //
    $semaphore = new IPCSemaphore(str_rand(10));
    $ret = [];
    $ret[] = $semaphore->acquire();// first acquire must be success.
    $ret[] = $semaphore->acquire(true) === true;// multiple acquire must be success.
    $ret[] = $semaphore->acquire(true) === true;// multiple acquire must be success.
    $ret[] = $semaphore->release();
    $ret[] = $semaphore->acquire();
    $ret[] = $semaphore->release();
    $ret[] = $semaphore->destroy();
    //
    foreach ($ret as $value) {
      $this->assertTrue($value);
    }
  }
}