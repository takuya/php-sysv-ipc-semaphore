<?php

namespace Tests\Units\SysV;

use Tests\TestCase;
use Takuya\SysV\IPCSemaphore;
use function Takuya\Helpers\str_rand;

class SemaphoreWithInterProcessTest extends TestCase {
  
  public function test_sysv_semaphore_inter_process_forked() {
    //
    $name = str_rand(10);
    //
    if (($pid = pcntl_fork())===false){
      throw new \Exception('fork failed');
    }
    if ( $pid===0 ){
      $sem = new IPCSemaphore($name);
      $sem->acquire();
      $sem->release();
      exit(0);
  
    }
    usleep(100);
    $sem = new IPCSemaphore($name);
    $sem->acquire();
    $sem->release();
    pcntl_waitpid($pid,$st);
    $sem->destroy();
  }
}