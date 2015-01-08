<?php
/******************************************
 * DBCON
 * Main class for database connections;
 * primarily (as of 01/2015) a MySQL db
 * class
 *
 * @author			Tyler J Barnes
 * @email			B4RN3SCODE@gmail.com
 * @version			1.0
 ******************************************/
/*		--------Change Log--------
 *
 *------------------------------------*/
class DBCon {

	/**		Properties		**/

	// hostname
	private $HOST;

	// database user to access as
	private $USER;

	// password for the user
	private $PWD;

	// database name
	private $DB;

	//table name
	private $TBL;

	// link reference
	private $LinkRef;

	// query statement
	private $QueryStmt;

	// query result
	private	$QueryRslt;

	// error message
	private $Error;

	// tracks the status for linked reference
	private $IsLinked;

	/**		End Properties		**/


	/************************************
	 * Constructor
	 ************************************/
	public function DBCon($host = DB_HOST, $user = DB_USER, $password = DB_PASS, $dbname = DB_NAME, $tablename = null, $qstmt = null) {
		$this->HOST = $host;
		$this->USER = $user;
		$this->PWD = $password;
		$this->DB = $dbname;
		$this->TBL = $tablename;
		$this->LinkRef = null;
		$this->QueryStmt = $qstmt;
		$this->QueryRslt = null;
		$this->Error = "";
		$this->IsLinked = false;
	}



	/**************************************
	 * Creates a connection reference
	 *
	 * @return	boolean true for succeess
	 *************************************/
	public function Link() {
		// if already linked, return
		if($this->getIsLinked()) return true;

		$this->LinkRef = null;
		$this->Error = "";
		$this->LinkRef = new mysqli($this->HOST, $this->USER, $this->PWD, $this->DB);

		if($this->LinkRef->connect_errno) {
			$this->LinkRef = null;
			$this->Error = "Could not connect";
			$this->setIsLinked(false);
		} else $this->setIsLinked(true);

		return $this->getIsLinked();
	}


	/********************************
	 * Unsets all object propertys &
	 * values
	 ********************************/
	public function Kill() {
		foreach(get_class_vars("DBCon") as $prop => $val)
			unset($this->$prop);
	}


	/**		ACCESSORS		**/

	// get property values
	public function getHOST() { return $this->HOST; }
	public function getUSER() { return $this->USER; }
	public function getPWD() { return $this->PWD; }
	public function getDB() { return $this->DB; }
	public function getTBL() { return $this->TBL; }
	public function getLinkRef() { return $this->LinkRef; }
	public function getQueryStmt() { return $this->QueryStmt; }
	public function getQueryRslt() { return $this->QueryRslt; }
	public function getError() { return $this->Error; }
	public function getIsLinked() { return $this->IsLinked; }


	// set property values
	public function setTBL($tbl = null) {
		if(!isset($tbl) || !(OffMan::StringHasValue($tbl)))
			return false;
		else $this->TBL = $tbl;
	}

	private function setLinkRef($ref = null) {
		$this->LinkRef = $ref;
	}

	public function setQueryStmt($stmt = null) {
		if(!isset($stmt) || !(OffMan::StringHasValue($stmt)))
			return false;
		else $this->QueryStmt = $stmt;
	}

	protected function setQueryRslt($rslt = null) {
		$this->QueryRslt = $rslt;
	}

	private function setError($err = null) {
		$this->Error = $err;
	}

	private function setIsLinked($bool = false) {
		$this->IsLinked = $bool;
	}

	/**			END ACCESSORS		**/


	/*********************************
	 * Resets the query info
	 ********************************/
	public function ResetQueryAndResult() {
		$this->QueryStmt = "";
		$this->QueryRslt = null;
	}


	/******************************
	 * Fetches all query result
	 * data
	 *
	 * @return	assoc array of data
	 *********************************/
	public function GetAll() {
		if(!isset($this->QueryRslt))
			return null;

		return $this->QueryRslt->fetch_all(MYSQLI_ASSOC);
	}


	/*************************************
	 * Fetches a row of query result data
	 *
	 * @return an assoc array of data
	 *************************************/
	public function GetRow() {
		if($row = $this->QueryRslt->fetch_assoc())
			return $row;

		return null;
	}


	/********************************
	 * Executes a query
	 *
	 * @return boolean true for success
	 ***********************************/
	public function Query() {
		if($sent = $this->LinkRef->query($this->QueryStmt)) {
			$this->setQueryRslt($sent);
			return true;
		}

		$this->Error = "Query Failure: -- {$this->QueryStmt} --";
		return false;
	}



	/*********************************************************************************************************************
	 * SStatement
	 * Builds simple select statement
	 *
	 * @param	select	:	array with
	 *							- EMPTY = ALL or *
	 *							- Columns to select
	 * @param	fromTbl	:	table from which to select
	 * @param	where	:	array with
	 * 							- first index column value to check
	 * 							- second index array with
	 * 								0 index is the value being compared the column/value
	 * 								1 index uses word for the next comparison (AND, OR), keep
	 * 									NULL if no further comparisons desired.
	 *
	 * @return	string full query
	 *
	 * EXAMPLE:
	 * 		select	=	array('Name', 'Age', 'BirthDay')
	 * 		fromTbl	=	'StudentCourseList'
	 * 		where	=	array(
	 * 						'CourseID'	=>	array(45, 'AND'),
	 * 						'Gender'	=>	array('Male')
	 * 					)
	 * 		Resulting Query: SELECT Name, Age, BirthDay FROM StudentCourseList WHERE CourseID = 45 AND Gender = 'Male'
	 *********************************************************************************************************************/
	public function SStatement($select = array(), $fromTbl = null, $where = array()) {
		if(!isset($fromTbl) || empty($fromTbl) || is_null($fromTbl))
			return false;

		// build SELECT portion
		$str = "SELECT";

		// if empty select ALL
		if(count($select) == 0)
			$str .= " *";

		else {

			$pos = -1;

			foreach($select as $clmn) {
				$str .= " ${clmn}";
				$pos++;

				if(isset($select[$pos]) && isset($select[$pos + 1]) &&
					($pos < (count($select) - 1)))
					$str .= ",";

			} //end foreach

		} // end else

		// build FROM portion
		$str .= " FROM ${fromTbl}";


		// make sure there is a condition
		if(isset($where) && count($where) > 0) {

			// build WHERE portion
			$str .= " WHERE";

			foreach($where as $metaA => $arrDetails) {
				$str .= " ${metaA} =";

				if(gettype($arrDetails[0]) == "string")
					$str .= " '${arrDetails[0]}'";
				else $str .= " ${arrDetails[0]}";

				if(isset($arrDetails[1]) && !is_null($arrDetails[1]) && !empty($arrDetails[1]))
					$str .= " ${arrDetails[1]}";
			}

		} // end IF

		$this->QueryStmt = $str;
		return $str;
	}


	/*********************************************************************************************************************
	 * IStatement
	 * Builds simple insert statement
	 *
	 * @param	intoTbl	:	table to insert data into
	 * @param	valsArr	:	array with
	 * 							- values paired following (ColumnName, ValueToInsert)
	 *
	 * @return	string full query
	 *
	 * EXAMPLE:
	 * 		intoTbl	=	'StudentList'
	 * 		valsArr	=	array('FirstName', 'Joe', 'Age', 15, 'RidesBus', true)
	 *
	 * 		Resulting Query: INSERT INTO StudentList (FirstName, Age, RidesBus) VALUES ('Joe', 15, true)
	 *********************************************************************************************************************/
	public function IStatement($intoTbl = null, $valsArr = array()) {
		if(!isset($intoTbl) || empty($intoTbl) || !isset($valsArr) || count($valsArr) < 1)
			return false;

		// begin statement
		$str = "INSERT INTO ${intoTbl}";

		// array for the column names
		$tblColmnLst = array();
		// array for the corresponding values
		$valLst = array();

		// iterate through valsArr and separate to the
		//	appropriate array above
		foreach($valsArr as $TBLCOL => $INVAL) {

			$tblColmnLst[] = $TBLCOL;

			// determine type of data
			if(gettype($INVAL) == "NULL" || strtoupper($INVAL) == "NULL" || (empty($INVAL) && gettype($INVAL) == "string"))
				$valLst[] = "NULL";
			else
				$valLst[] = (gettype($INVAL) == "string") ? "'${INVAL}'" : "${INVAL}";

		} //end foreach

		$tmpA = "(" . implode(", ", $tblColmnLst) . ")";
		$tmpB = "(" . implode(", ", $valLst) . ")";
		$str = "${str} ${tmpA} VALUES ${tmpB}";
		$this->QueryStmt = $str;

		return $str;
	}

	/****************************************************************************************
	 * UStatement
	 * Builds a simple update statement
	 * Similar to SSTatement (SELECT), with an additional array
	 * 	for setting the columns with corresponding values
	 *
	 * @param	updTbl	:	table being updated
	 * @param	setVals	:	array with
	 * 							column names and values following (ColumnName, ValueToSet)
	 * @param	whereConds	:	array with where conditions similar to SStatement (SELECT)
	 *
	 * @return	string of full query
	 ****************************************************************************************/
	public function UStatement($updTbl = null, $setVals = array(), $whereConds = array()) {
		if(!isset($updTbl) || is_null($updTbl) || empty($updTbl) ||
			!isset($setVals) || count($setVals) < 1) return false;

		// beign stmt
		$str = "UPDATE ${updTbl} SET ";

		// array containing all of the pairs as
		//	a string similar to array('Column1 = Val1', 'Column2 = Val2', 'Column3 = Val3')
		$tmpA = array();

		// fill the above array with correct sytax for data types
		foreach($setVals as $COL => $VAL) {

			if(gettype($VAL) == "NULL" || strtoupper($VAL) == "NULL" || (empty($VAL) && gettype($VAL) == "string"))
				$tmpA[] = "${COL} = NULL";

			else
				$tmpA[] = (gettype($VAL) == "string") ? "${COL} = '${VAL}'" : "${COL} = ${VAL}";
		}

		// implode to make one string
		$str = $str . implode(", ", $tmpA) . " WHERE";

		// where conditions
		foreach($whereConds as $metaA => $arrDetails) {
			$str .= " ${metaA} =";

			if(gettype($arrDetails[0]) == "string")
				$str .= " '${arrDetails[0]}'";
			else $str .= " ${arrDetails[0]}";

			if(isset($arrDetails[1]) && !is_null($arrDetails[1]) && !empty($arrDetails[1]))
				$str .= " ${arrDetails[1]}";
		}
		$this->QueryStmt = $str;

		return $str;
	}


}
?>
