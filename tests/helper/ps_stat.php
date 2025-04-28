<?php

namespace Takuya\Helpers;

if( ! function_exists('ps_stat') ) {
  function ps_stat( $pid, $status = 'S' ):bool {
    /**
     * D    uninterruptible sleep (usually IO)
     * I    Idle kernel thread
     * R    running or runnable (on run queue)
     * S    interruptible sleep (waiting for an event to complete)
     * T    stopped by job control signal
     * t    stopped by debugger during the tracing
     * W    paging (not valid since the 2.6.xx kernel)
     * X    dead (should never be seen)
     * Z    defunct ("zombie") process, terminated but not reaped by its parent
     */
    if( ! is_executable('/bin/ps') ) {
      throw new \RuntimeException('/bin/ps is not found executable, only GNU Linux/BSD supported.');
    }
    $ps_string = `/bin/ps -o pid,tty,stat,time,command -p {$pid}`;
    $parse_procps = function ( $str ) {
      $lines = preg_split("/\r\n|\n|\r/", $str);
      if( sizeof($lines) < 2 ) {
        throw new \InvalidArgumentException();
      }
      [$headers, $body] = array_slice(array_map('trim', $lines), 0, 2);
      $headers = preg_split('/\s+/', $headers);
      $body = preg_split('/\s+/', $body, sizeof($headers));
      $info = array_combine($headers, $body);
      
      return $info;
    };
    if( 2 > substr_count($ps_string, PHP_EOL) ) {
      return false;
    }
    $info = $parse_procps($ps_string);
    return str_starts_with($info['STAT'], $status);
  }
}

if( ! function_exists('ps_sleeping') ) {
  function ps_sleeping( $pid, $status = 'S' ):bool {
    return ps_stat($pid,$status);
  }
}

