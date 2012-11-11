<?php

class aplogparser {

	private $lfile = '';
  private $db = '';

	function __construct($lfile, $pattern = '') {
		
    $this->lfile = $lfile;
		
    if ($pattern == '')
      $this->pattern = "/^(\S+) (\S+) (\S+) \[([^:]+):(\d+:\d+:\d+) ([^\]]+)\] \"(\S+) (.*?) (\S+)\" (\S+) (\S+) (\".*?\") (\".*?\")$/";
    else
      $this->pattern = $pattern;
    
    return $this; 
	
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

  //подключение к базе данных через PDO
  public function db($dbname, $dbuser = 'root', $dbpass ='', $dbhost = 'localhost', $dbdriver = 'mysql') {
    
    try {
      $this->db = new PDO ($dbdriver.':host=' . $dbhost . ';dbname=' . $dbname, $dbuser, $dbpass); 
    } 
    catch(PDOException $e) {  
      echo $e->getMessage();  
    } 

    return $this;

    
  }

  private function parser_line($line) {
      preg_match($this->pattern, $line, $matches); // pattern to format the line
      return $matches;
  }


  public function get_line($line_length=300)  {
    return fgets($this->fp, 1096); // true and get a line and return the result
  }	

  
  private function formatlog ($line){
  		
  		$logs = $this->parser_line($line); // format the line
       		
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

  	
  	
  

  	public function  bestpath() {
  		
  		if (!$this->log_open($this->lfile))
  			return null;

  		

      #перерасчет топа
      while ($line = $this->get_line()) { 
  			
        $lines = $this->formatlog($line); // format the line
        $path = trim($lines['path']);
        if ($path !== '' and $path !== '*') {
            
            echo $path.'<br>';

            $key = md5($path);

            if ($this->db !== null) {
              $find_key = $this->db->prepare("SELECT * FROM `top24` WHERE key=:key")->bindParam(':key',$key, PDO::PARAM_INT);
              $find_key->execute();
              $row = $find_key->fetch();
              if (is_array($row))
                  print_r($row);
            }


           /* if (isset($toplink[$keypath])) {
               $toplink[$keypath]['clicks']++;
            }    
            else
              $toplink[$keypath] = array('link'=>$path, 'clicks'=>1, 'time' => strtotime($lines['time']));*/
          
        }    
  		
      }

      //print_r($toplink);
     // echo $this->titlehtml('http://www.argumenti.ru/');

 			
  	}


    function addelement($path, $table) {

        $STH = $DBH->prepare("INSERT INTO $table (path, key, city) values ($path, $key, $city)");      

    }


    private function createtable($table) {

      if (!$this->db)
          return False;

     $this->db->exec = "CREATE TABLE IF NOT EXISTS `".$table."` (
  `top_id` smallint(6) NOT NULL AUTO_INCREMENT,
  `link` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `clicks` int(11) NOT NULL,
  `title` varchar(200) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `key` varchar(220) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`top_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";
    
    return True;


    }


    public function titlehtml($page) {
      $content = file_get_contents('http://www.cyberforum.ru');
      preg_match_all('#<title>.+</title>#', $content, $matches);
      $title = preg_replace('#(<title>|</title>)#', '', $matches[0][0]); //здесь удаляем <title> и </title>
      return $title; //выводим на экран <title> страницы
    }


}