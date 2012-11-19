<?php

class aplogparser {

	private $lfile = ''; // текущий лог файл
  private $db = null; // подключение к БД
  private $domain = 'http://localhost'; // домен для ссылок
  private $toptable = 'toptable'; // имя таблицы где храним данные
  
	/**
  * В конструкторе указываем лог файл, а также домен к которому принадлежит для создания ссылок
  */
  function __construct($lfile, $domain = 'localhost', $pattern = '') {
		
    $this->lfile = $lfile;
    $this->domain = 'http://'.$domain;

		
    if ($pattern == '') # cтандартный формат лога Apache
      $this->pattern = "/^(\S+) (\S+) (\S+) \[([^:]+):(\d+:\d+:\d+) ([^\]]+)\] \"(\S+) (.*?) (\S+)\" (\S+) (\S+) (\".*?\") (\".*?\")$/";
    else
      $this->pattern = $pattern;
    
    return $this; 
	
  }


  /**
  * Если по каким то причинам стандартное название таблицы не подходит - меняем
  */
  function toptable($tname){
    $this->toptable = $tname;
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
      $this->db = new PDO ($dbdriver.':host='.$dbhost.';dbname='.$dbname, $dbuser, $dbpass); 
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
      $result['url'] = $logs[8];
      $result['protocal'] = $logs[9];
      $result['status'] = $logs[10];
      $result['bytes'] = $logs[11];
      $result['referer'] = $logs[12];
      $result['agent'] = $logs[13];
      
      return $result; // return the array of info
  	
  	}

  	

  	public function topparser($min = 120) {
  		
  		if (!$this->log_open($this->lfile))
  			return null;

      $toptable = $this->toptable;

      # промежуток времени между которым ищем
      $currdate = date('d/M/Y');
      $starttime = date('H:i:s', time()-($min*60)); //точка отсчета
      $endtime = date('H:i:s', time()); 
         		
      # автосоздание таблицы
      $this->create($toptable);

      # обнуляем результаты
      $truncate = $this->db->prepare("TRUNCATE TABLE `$toptable`");
      $truncate->execute();

      # перерасчет топа
      while ($line = $this->get_line()) { 
  			
        $lines = $this->formatlog($line); // format the line
        $url = trim($lines['url']);
                
        $time = $lines['time']; # время записи лога

        if ($time >= $starttime and $time < $endtime) {
            if ($url !== '' and $url !== '*') {
              $key = md5($url);
              if ($this->db !== null) { 
                $find_key = $this->db->prepare("SELECT * FROM `$toptable` WHERE `key`= '$key'");
                $find_key->execute();
                $count =  $find_key->rowCount();
                if ($count > 0) { // есть ли клики на эту страницу
                  $result = $find_key->fetch();
                  $clicks = $result['clicks'] + 1;
                  $this->db->exec("UPDATE `$toptable` SET `clicks`='$clicks' WHERE `top_id`=".$result['top_id']);
                }    
                else //добавляем новую страницу
                  $this->db->exec("INSERT INTO `$toptable` (`url`, `key`, `clicks`) VALUES ('$url', '$key', '1')");
              }
            }  
        }    
  		
      }

     
 			
  	}

    
    /**
    * Рисуем результаты подсчетов
    */
    public function render($limit = 20, $fview = 'view/toplinks.phtml', $resfile = 'pub/toplinks.phtml') {
      
      $result = $this->top($limit);

      if ($result !== False) { # формируем html страницу
        ob_start();
        include($fview);
        $render = trim(ob_get_contents());
        ob_end_clean(); 
        
        file_put_contents($resfile, $render); # пишем результат в файл

      }

    }
  
  private function top($limit) {

    $top = $this->db->prepare("SELECT * FROM `$this->toptable` ORDER BY `clicks` LIMIT $limit");
    $top->execute();
    
    if ($top->rowCount() > 0) {
      $result = $top->fetchAll();
      foreach ($result as $item) {
        $r['url'] = $this->domain.$item['url'];
        $title = trim($this->titlehtml($r['url'])); # пытаемся получить заголовок html страницы
        if ($title == '') 
            $title =  $r['url'];
        $r['title'] = $title;
        $r['clicks'] = $item['clicks'];
        $result[] = $r;
      }  
      return $result;
    }
    return False;
  
  }



   private function filterurl($url) {

   } 

    
    //создаем таблицу
    private function create($table) {

     if (!isset($this->db)) return False;
     
     $this->db->exec(
      "CREATE TABLE IF NOT EXISTS `".$table."` (
        `top_id` smallint(6) NOT NULL AUTO_INCREMENT,
        `url` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
        `clicks` int(11) NOT NULL,
        `title` varchar(200) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
        `key` varchar(220) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
        PRIMARY KEY (`top_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1");
    
    return True;


    }


    public function titlehtml($page) {
      
      $content = file_get_contents($page);
      $find = preg_match_all('#<title>.+</title>#', $content, $matches);
      
      if ($find == 0 or $find == False)
          return '';

      $title = preg_replace('#(<title>|</title>)#', '', $matches[0][0]); //здесь удаляем <title> и </title>
      return $title; //выводим на экран <title> страницы
         
    }


}