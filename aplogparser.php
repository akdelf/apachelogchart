<?php

class apache_log_parser {

	private $lfile = '';

	function __construct($lfile, $pattern = '') {
		$this->lfile = $file;
		$this->pattern = "/^(\S+) (\S+) (\S+) \[([^:]+):(\d+:\d+:\d+) ([^\]]+)\] \"(\S+) (.*?) (\S+)\" (\S+) (\S+) (\".*?\") (\".*?\")$/";
	}
	


	private function format_log_line($line) {
    	preg_match($this->pattern, $line, $matches); // pattern to format the line
    	return $matches;
  	}


  	public function get_line($line_length=300)	{
    	return fgets($this->fp, 1096); // true and get a line and return the result
  	}

  	function parser () {
  		
  		if (!$this->log_open($this->lfile))
  			return null;

  		while ($line = $this->get_line()) { 
  			 $line[] = $this->formatlog($line); // format the line
  		}
  			
  	}


  	private function format ($line){
  		
  		$logs = $this->format_log_line($line); // format the line

  		$result = array(); // make an array to store the lin info in
      	$result['ip'] = $logs[1];
      	$result['identity'] = $logs[2];
      	$result['user'] = $logs[2];
      	$result['date'] = $logs[4];
      	$result['time'] = $logs[5];
      	$result['timezone'] = $logs[6];
      	$result['method'] = $logs[7];
      	$result['path'] = $logs[8];
      	$result['protocal'] = $logs[9];
      	$result['status'] = $logs[10];
      	$result['bytes'] = $logs[11];
      	$result['referer'] = $logs[12];
      	$result['agent'] = $logs[13];
      	
      	return $result; // return the array of info
  	
  	}

  	
  	private function log_open($file_name) {
    	$this->fp = fopen($file_name, 'r'); // open the file
    	if (!$this->fp)
    	     return false; // return false on fail
    	return true; // return true on sucsess
  	}

  
  	private function log_close() {
   		 return fclose($this->fp); // close the file
  	}
  

}