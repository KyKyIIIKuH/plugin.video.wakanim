<?
// Класс для работы с MySQLi
class MySQLiDB {
	function __construct()
	{
		$this->mysqli = @new mysqli('localhost', 'user', 'password', 'db');
	}

	// 
	private function con() {
		// О нет!! переменная connect_errno существует, а это значит, что соединение не было успешным!
		if ($this->mysqli->connect_errno) {
		    // Соединение не удалось. Что нужно делать в этом случае? 
		    // Можно отправить письмо администратору, отразить ошибку в журнале, 
		    // информировать пользователя об ошибке на экране и т.п.
		    // Вам не нужно при этом раскрывать конфиденциальную информацию, поэтому
		    // просто попробуем так:
		    echo "<p>Извините, возникла проблема на сайте</p>";

		    // На реальном сайте этого делать не следует, но в качестве примера мы покажем 
		    // как распечатывать информацию о подробностях возникшей ошибки MySQL
		    echo "<p>Ошибка: Не удалась создать соединение с базой MySQL и вот почему: </p>";
		    echo "<p>Номер ошибки: " . $this->mysqli->connect_errno . "</p>";
		    
		    // Вы можете захотеть показать что-то еще, но мы просто выйдем
		    exit();
		}

		return $this->mysqli;
	}

	// Открываем соединение
	public function open() {
		try{
			$handle = $this->con();
		    return $handle;
		} catch (Exception $e) {
	    	return $arrayName = $e;
	    }
	}

	// Выполняем SELECT запросы
	public function query($query) {
		try{
	    	$result = $this->mysqli->query($query);
	    	return $result; 
	    } catch (Exception $e) {
	    	return $arrayName = $e; 
	    }
	}

	// Выполняем запросы UPDATE, INSERT, DELETE .etc
	public function exec($query) {
		try{
	    	$result = $this->mysqli->query($query);
	    	return $result; 
	    } catch (Exception $e) {
	    	return $arrayName = $e; 
	    }
	}

	// Получаем результат
	public function fetch_array(&$result) {
		$columns = [];
		try{
			while ($row = $result->fetch_assoc()) {
			    array_push($columns, $row);
			}
			return $columns;
		} catch (Exception $e) {
	    	return $arrayName = $e;
	    }
	}

	public function close() {
		$this->mysqli->close();
	}
}

?>
