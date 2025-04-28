<?php

namespace Tests\Units\SysV;

use Tests\TestCase;
use Takuya\SysV\IPCInfo;
use Takuya\SysV\IPCSemaphore;
use function Takuya\Helpers\ps_stat;
use function Takuya\Helpers\str_rand;
use function Takuya\Helpers\ps_sleeping;

class SemaphoreWithInterProcessTest extends TestCase {
  
  public string $shm_name;
  public \Shmop $shm;
  
  protected function setUp():void {
    parent::setUp();
    $this->shm = shmop_open(IPCInfo::ipc_key($this->shm_name = str_rand(10)), 'c', 0777, 1024);
    $this->shm_cnt = `ipcs -m | wc -l `;
  }
  
  protected function tearDown():void {
    shmop_delete($this->shm);
    $this->assertEquals($this->shm_cnt, `ipcs -m | wc -l `);
    parent::tearDown();
  }
  
  //
  public function test_sysv_semaphore_inter_process_forked() {
    
    $name = str_rand(10);
    //
    $cnt = 10;
    if( ( $pid = pcntl_fork() ) === false ) {
      throw new \Exception('fork failed');
    }
    if( $pid === 0 ) {
      // child
      $sem = new IPCSemaphore($name);
      foreach (range(100, 100 + $cnt - 1) as $i) {
        $sem->withLock(function () use ( $i ) {
          $arr = @unserialize(shmop_read($this->shm, 0, 1024)) ?: [];
          $arr[] = $i;
          shmop_write($this->shm, serialize($arr), 0);
        });
      }
      exit(0);
    }
    $sem = new IPCSemaphore($name);
    //
    foreach (range(0, $cnt - 1) as $idx) {
      $sem->withLock(function () use ( $idx, $pid, &$child_is_blocked ) {
        $child_is_blocked[] = ps_sleeping($pid);// ここが sleep 代わりになる
        $arr = @unserialize(shmop_read($this->shm, 0, 1024)) ?: [];
        $arr[] = $idx;
        shmop_write($this->shm, serialize($arr), 0);
      });
    }
    //
    pcntl_waitpid($pid, $st);
    $sem->destroy();
    //
    $this->assertEquals($cnt, sizeof(array_filter($child_is_blocked)));
    $this->assertEquals([
                          0 => 0,
                          1 => 100,
                          2 => 1,
                          3 => 101,
                          4 => 2,
                          5 => 102,
                          6 => 3,
                          7 => 103,
                          8 => 4,
                          9 => 104,
                          10 => 5,
                          11 => 105,
                          12 => 6,
                          13 => 106,
                          14 => 7,
                          15 => 107,
                          16 => 8,
                          17 => 108,
                          18 => 9,
                          19 => 109,
                        ],
                        @unserialize(
                          shmop_read(
                            $this->shm,
                            0,
                            1024)));
  }
}