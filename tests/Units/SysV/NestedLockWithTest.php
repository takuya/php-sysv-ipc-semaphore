<?php

namespace Tests\Units\SysV;

use Tests\TestCase;
use Takuya\SysV\IPCSemaphore;
use function Takuya\Helpers\str_rand;

class NestedLockWithTest extends TestCase {
  protected function get_lock_cnt_by_ref(IPCSemaphore $instance){
    try {
      $ref = new \ReflectionClass($instance);
      return $ref->getProperty('count_locked')->getValue($instance);
    }catch (\Error $e){
      return $e->getMessage();
    }
  }
  public function test_sysv_nested_lock_callback() {
    //
    $msg = str_rand();
    $sem = new IPCSemaphore(str_rand(10));
    $ret = $sem->withLock(function()use($sem,$msg){
      $ret =  $sem->withLock(function()use($msg,$sem){
        $this->assertEquals(2,$this->get_lock_cnt_by_ref($sem));
        return $msg;
      });
      $this->assertEquals(1,$this->get_lock_cnt_by_ref($sem));
      return $ret;
    });
    $this->assertStringContainsString('accessed before initialization',$this->get_lock_cnt_by_ref($sem));
    $sem->destroy();
    $this->assertEquals($msg, $ret);
  }
  
}