<?php
  
  require_once('System/Daemon.php');
  
  abstract class bmDaemon extends bmFFObject
  {
  
    const RUN = 1;
    const STOP = 2;
    
    private $options = array();
    private $ownOptions = array();
    private $runmodes = array();
    protected $status = self::RUN;
    
    public function __construct($application, $parameters = array())
    {
      parent::__construct($application, $parameters);
      global $argv;
      $this->runmodes = array
      (
        'no-daemon' => false,
        'help' => false,
        'write-initd' => false
      );
   
      foreach ($argv as $argument)
      {
        if (substr($argument, 0, 2) == '--' && isset($this->runmodes[substr($argument, 2)]))
        {
          $this->runmodes[substr($argument, 2)] = true;
        }
      }
      
      $this->options = array
      (
        'appName' => '',
        'appDir' => dirname($_SERVER['PHP_SELF']),
        'appDescription' => '',
        'authorName' => '',
        'authorEmail' => '',
        'sysMaxExecutionTime' => '0',
        'sysMaxInputTime' => '0',
        'sysMemoryLimit' => '10M',
        'appRunAsGID' => 1000,
        'appRunAsUID' => 1000
      );
      
      foreach($parameters as $name => $value)
      {
        if (array_key_exists($name, $this->options))
        {
          $this->options[$name] = $value;
        }
        else
        {
          $this->ownOptions[$name] = $value;
        }
      }
   
    }
      
    public function execute()
    {
      ob_start(); 
        
      if ($this->runmodes['help'] == true) 
      {
        echo 'Usage: '.$argv[0].' [runmodes]' . "\n";
        echo 'Available runmodes:' . "\n";
        foreach ($this->runmodes as $runmode) 
        {
          echo ' --'.$runmode . "\n";
        }
        die();
      }

      System_Daemon::setOptions($this->options);
      if (!$this->runmodes['no-daemon']) 
      {
        System_Daemon::start();
      }
      
      if (!$this->runmodes['write-initd']) 
      {
        System_Daemon::info('not writing an init.d script this time');
      }
      else 
      {
        
        if (($initd_location = System_Daemon::writeAutoRun()) === false)
        {
          System_Daemon::notice('unable to write init.d script');
        }
        else
        {
          System_Daemon::info('sucessfully written startup script: %s', $initd_location);
        }
      }

      $this->application->dataLink->disconnect();
      $this->application->dataLink->connect();
      
      while (!System_Daemon::isDying() && $this->status == self::RUN)
      { 
        $this->process();
        System_Daemon::iterate($this->ownOptions['sleep']);
        $output = ob_get_contents();
        if ($output != '')
        {
          System_Daemon::info($output);
        }
        ob_clean();
      }
    }
    
    abstract protected function process();

  }
  
?>