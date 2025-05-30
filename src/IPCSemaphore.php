<?php

namespace Takuya\SysV;

class IPCSemaphore {
  
  protected int            $ipc_key;
  protected \SysvSemaphore $sem;
  private bool             $acquired;
  private ?int              $count_locked;
  
  public static function str_to_key( string $str ):int {
    return IPCInfo::ipc_key($str);
  }
  
  protected function key():int {
    return $this->ipc_key ??= static::str_to_key($this->name);
  }
  
  /**
   * @param string $name        pseudo name -- php sem_get() using unnamed semaphore .
   * @param int    $max_acquire The number of processes that can acquire semaphore.( using fork. )
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
    
    return (bool)( $this->sem = $r ) && $this->acquired = false;
  }
  
  /**
   * @param callable $fn
   * @return mixed
   */
  public function withLock( callable $fn ):mixed {
    try {
      $this->acquire();
      $this->count_locked = 1 + ( $this->count_locked ?? 0 );
      
      return $fn($this);
    } finally {
      $unset = function (){unset($this->count_locked);return true;};
      ( 0 === --$this->count_locked ) && $unset() && $this->release();
    }
  }
  
  /**
   * This returns object which destructor has release().
   * Once exit scope, release() will be called automatically by garbage collection.
   * This method for the purpose of blocking.
   * @param bool $non_block for testing.
   * @return object
   */
  public function lock( bool $non_block = false ):object {
    return new class( $this, $non_block ) {
      
      public function __construct( protected IPCSemaphore $parent, $non_block ) {
        $this->parent->acquire($non_block);
      }
      
      public function __destruct() {
        $this->parent->release();
      }
    };
  }
  
  /**
   * @param bool $non_blocking
   * @return bool
   */
  public function acquire( bool $non_blocking = false ):bool {
    return $this->acquired || $this->acquired = sem_acquire($this->sem, $non_blocking);
  }
  
  /**
   * @return bool
   */
  public function release():bool {
    return ( $this->acquired && sem_release($this->sem) && $this->acquired = false ) === false;
  }
  
  /**
   * @return bool
   */
  public function destroy():bool {
    return $this->release() && sem_remove($this->sem);
  }
}