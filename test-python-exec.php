<?php
	/* An easy way to keep in track of external processes.
	* Ever wanted to execute a process in php, but you still wanted to have somewhat controll of the process ? Well.. This is a way of doing it.
	* @compability: Linux only. (Windows does not work).
	* @author: Peec
	*/
	class Process{
		private $pid;
		private $command;
		private $output = null;

		public function __construct($cl=false){
			if ($cl != false){
				$this->command = $cl;
			}
		}
		private function runCom(){
			$command = 'nohup '.$this->command.' & echo $!';
			exec($command, $op);
			echo '<pre>';
			echo $command."\n";
			print_r($op);
			$this->pid = (int)$op[0];
		}

		public function setPid($pid){
			$this->pid = $pid;
		}

		public function getPid(){
			return $this->pid;
		}

		public function status(){
			$command = 'ps -p '.$this->pid;
			exec($command,$op);
			if (!isset($op[1]))return false;
			else return true;
		}

		public function start(){
			if ($this->command != '')$this->runCom();
			else return true;
		}

		public function stop(){
			$command = 'kill '.$this->pid;
			exec($command);
			if ($this->status() == false)return true;
			else return false;
		}
	}

    // You may use status(), start(), and stop(). notice that start() method gets called automatically one time.
	$process = new Process('python python/hello.py --file="examples-books/candide.txt" -ag > python-out/out.json');
	$process->start();

    // or if you got the pid, however here only the status() method will work.
#	$process = new Process();
#	$process->setPid(my_pid);


    // Then you can start/stop/ check status of the job.
#	$process->stop();
#	$process->start();
	if ($process->status()) {
		echo "The process is currently running";
	} else {
		echo "The process is not running.";
	}


	?>