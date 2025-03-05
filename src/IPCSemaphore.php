<?php

namespace Takuya\SysV;

class IPCSemaphore {
  
  protected int            $ipc_key;
  protected \SysvSemaphore $sem;
  
  protected function key():int {
    if( empty($this->ipc_key) ) {
      $seed = crc32($this->name);
      mt_srand($seed);
      $fixed_random_unsigned_int32_seed_by_name = mt_rand(0, PHP_INT_MAX)&0x7FFFFFFF;
      mt_srand(time());
      $this->ipc_key = $fixed_random_unsigned_int32_seed_by_name;
    }
    
    return $this->ipc_key;
  }
  
  /**
   * @param string $name        pseudo name -- php sem_get() using unnamed semaphore .
   * @param int    $max_acquire The number of processes that can acquire semaphore.
   * @param int    $perm
   * @param bool   $auto_release
   */
  public function __construct( public string $name,
                               public int    $max_acquire = 1,
                               public int    $perm = 0770,
                               public bool   $auto_release = true ) {
    $this->init();
  }
  
  public function init():bool {
    $r = sem_get($this->key(), $this->max_acquire, $this->perm, $this->auto_release);
    if( ! $r ) {
      throw new \RuntimeException('sem_get() failed.');
    }
    
    return (bool)( $this->sem = $r );
  }
  
  /**
   * @param bool $non_blocking
   * @return bool
   */
  public function acquire( bool $non_blocking = false ):bool {
    return sem_acquire($this->sem, $non_blocking);
  }
  
  /**
   * @return bool
   */
  public function release():bool {
    return sem_release($this->sem);
  }
  
  /**
   * @return bool
   */
  public function destroy():bool {
    return sem_remove($this->sem);
  }
}