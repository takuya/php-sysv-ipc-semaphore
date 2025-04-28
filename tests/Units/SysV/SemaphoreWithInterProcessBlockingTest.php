<?php

namespace Tests\Units\SysV;

use Tests\TestCase;
use Takuya\SysV\IPCInfo;
use Takuya\SysV\IPCSemaphore;
use function Takuya\Helpers\ps_stat;
use function Takuya\Helpers\str_rand;
use function Takuya\Helpers\ps_sleeping;

class SemaphoreWithInterProcessBlockingTest extends TestCase {
  
  public function test_sysv_semaphore_process_blocked() {

    $name = str_rand(10);
    if( ( $pid = pcntl_fork() ) === false ) {
      throw new \Exception('fork failed');
    }
    if( $pid === 0 ) {
      $sem = new IPCSemaphore($name);
      usleep(100);
      $sem->acquire();
      $sem->release();
      exit(0);
    }
    $sem = new IPCSemaphore($name);
    $sem->acquire();
    $is_blocked = ps_sleeping($pid);
    $sem->release();
    pcntl_waitpid($pid, $st);
    $sem->destroy();
    //
    $this->assertTrue($is_blocked);
  }
}