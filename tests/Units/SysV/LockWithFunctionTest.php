<?php

namespace Tests\Units\SysV;

use Tests\TestCase;
use Takuya\SysV\IPCSemaphore;
use function Takuya\Helpers\str_rand;

class LockWithFunctionTest extends TestCase {
  
  public function test_sysv_lock_callback() {
    //
    $msg = str_rand();
    $sem = new IPCSemaphore(str_rand(10));
    $ret = $sem->withLock(fn() => $msg);
    $sem->destroy();
    $this->assertEquals($msg, $ret);
  }
  
  public function test_sysv_lock_in_lock() {
    //
    $msg = str_rand();
    $sem = new IPCSemaphore(str_rand(10));
    $ret = $sem->withLock(function () use ( $sem, $msg ) {
      return $sem->withLock(fn() => $msg);
    });
    $sem->destroy();
    $this->assertEquals($msg, $ret);
  }
  
  public function test_sysv_triple_lock_block() {
    //
    $msg = str_rand();
    $sem = new IPCSemaphore(str_rand(10));
    $ret = $sem->withLock(function () use ( $sem, $msg ) {
      return $sem->withLock(function () use ( $sem, $msg ) {
        return $sem->withLock(fn() => $msg);
      });
    });
    $sem->destroy();
    $this->assertEquals($msg, $ret);
  }
  
  public function test_sysv_lock_release_class() {
    $sem = new IPCSemaphore(str_rand(10));
    $sem->acquire();
    $sem->lock(true);
    $keep_lock = $sem->lock(false);
    $ret = $sem->acquire(true);
    unset($keep_lock);// call destruct
    $sem->acquire();
    $sem->destroy();
    $this->assertTrue($ret);
  }
  
  public function test_sysv_try_finally() {
    $sample_func = function ( $msg ) {
      try {
        $sem = new IPCSemaphore(str_rand(10));
        $sem->acquire();
        
        return $msg;
      } finally {
        $sem->release();
        $sem->destroy();
      }
    };
    $msg = str_rand();
    $ret = $sample_func($msg);
    $this->assertEquals($msg, $ret);
  }
}