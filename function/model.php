<?php

require 'config/db.php';

/**
 * get currencies data from api url
 */
class Model extends Db
{
	
	private function Connect()
	{
		$servername = $this->servername;
		$database = $this->database;
		$username = $this->username;
		$password = $this->password;

		$this->conn = new PDO("mysql:host=$servername;dbname=$database", $username, $password);
		$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}

	public function Select($query = '')
	{
		$this->Connect();

		$stmt = $this->conn->prepare($query);
		$stmt->execute();

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function Insert($query='', $input=[])
	{
		$this->Connect();

		$stmt = $this->conn->prepare($query);
		try {
		    $this->conn->beginTransaction();
		    foreach ($input as $row)
		    {
		        $stmt->execute($row);
		    }
		    $this->conn->commit();
		}catch (Exception $e){
		    $this->conn->rollback();
		    throw $e;
		}
	}

	public function Update($query='', $input=[])
	{
		$this->Connect();

		$stmt = $this->conn->prepare($query);
		try {
		    $this->conn->beginTransaction();
		    foreach ($input as $row)
		    {
		        $stmt->execute($row);
		    }
		    $this->conn->commit();
		}catch (Exception $e){
		    $this->conn->rollback();
		    throw $e;
		}
	}

}