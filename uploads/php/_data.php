<?php
namespace iQuote;
class Data
{
    public static $conn;

    public static function IsDbconnected()
    {
        return (self::$conn);
    }

    public static function BeginTrans()
    {
        self::$conn->beginTransaction();
    }

    public static function CommitTrans()
    {
        self::$conn->commit();
    }

    public static function RollbackTrans()
    {
        self::$conn->rollBack();
    }

    public static function Connect($hostName, $dbName, $userName, $password)
    {
        self::$conn = new \PDO("sqlsrv:Server=$hostName;Database=$dbName;ConnectionPooling=0", $userName, $password);
        
        if (!self::$conn)
            self::$conn->setAttribute(constant('PDO::SQLSRV_ATTR_DIRECT_QUERY'), true);

        return self::$conn;
    }

    public static function ExecuteSP($class, $spAlias, $vars)
    {
        global $DbSPPrefix;

        $sql = "SELECT SUBSTRING(sys.parameters.name,2,999) FROM sys.parameters inner join sys.procedures on parameters.object_id = procedures.object_id inner join sys.types on parameters.system_type_id = types.system_type_id AND parameters.user_type_id = types.user_type_id
        WHERE procedures.name = ?";
        $params = array($DbSPPrefix . strtolower($class . "_" . $spAlias));
        $rows = self::FetchAll($sql, $params);
        $sp_params = array();
        for ($x = 0; $x < count($rows); $x++)
            $sp_params[] = $rows[$x][''];

        $sql = "EXEC " . $DbSPPrefix . strtolower($class . "_" . $spAlias) . " ";
        $args = '';
        $params = array();

        foreach ($vars as $key=>$value)
        {
            if (in_array($key, $sp_params))
            {
                if ($args != '') $args = $args . ', ';
                $args = $args . ' @' . $key . ' = ?';
    
                if (is_array($value))
                    $value = json_encode($value);
    
                $params[] = $value;
            }
        }
        $sql = $sql . ' ' . $args;

        
        if ($spAlias == 'merit_schemes_stuctures_create')
        {
            //print_r($params);die($sql);
        }
        
        //$rows = self::FetchAll($sql, $params);

        if ($spAlias == 'create')
        {
            //print_r($params);die($sql);
        }
        $rows = array();
        if ($stmt = self::Execute($sql, $params))
        {
            while($stmt->columnCount() === 0 && $stmt->nextRowset()) {
                // Advance rowset until we get to a rowset with data
            }                
        
            while ($row = self::FetchAssoc($stmt))
            {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    public static function Retrieve($tblRetrieval, $idName, $arrVars)
    {
        $response = array();
        $data = array();

        if (hasVars($arrVars, array($idName)))
        {
            $sql = "SELECT * FROM $tblRetrieval WHERE $idName = ?";
            $params = array($arrVars[$idName]);
            $data = self::fetchAll($sql, $params);
                        
            if (count($data) > 0)
            {
                $data = $data[0];
            }
        }

        return $data;
    }

    public static function FetchSingle($sql, $params = array(), $showSql = false)
    {
        $rows = self::FetchAll($sql, $params, $showSql);
        //print_r($rows);die;
        if (count($rows) > 0)
        {
            $row = $rows[0];
        }
        else
        {
            $row = null;
        }
        
        return $row;
    }

    public static function FetchAll($sql, $params = array(), $showSql = false)
    {
        $rows = array();
        if ($stmt = self::Execute($sql, $params, $showSql))
        {
            while ($row = self::FetchAssoc($stmt))
            {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    public static function Execute($sql, $params = array(), $logSQL = false)
    {
        self::$conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );

        if ($logSQL)
        {
            $sql_parsed = $sql;
            $from = '?';
            for ($n = 0; $n < count($params); $n++)
            {
                $sql_parsed = preg_replace( '/'.preg_quote($from, '/').'/', "'$params[$n]'", $sql_parsed, 1);
            }
            //die($sql);

            $sql_t = "INSERT INTO _sql (sql_parsed, sql_raw, params) VALUES (?, ?, ?)";
            $params_t = array($sql_parsed, $sql, json_encode($params));
            $stmt = self::$conn->prepare($sql_t, array(\PDO::ATTR_CURSOR => \PDO::CURSOR_SCROLL));
            $stmt->execute($params_t);
        }

        $stmt = self::$conn->prepare($sql, array(\PDO::ATTR_CURSOR => \PDO::CURSOR_SCROLL));
        try
        {
            // print_r($params);die;
            $stmt->execute($params);
        }
        catch (PDOException $e)
        {
            $stmt = null;
            die('dbMssql.execute: ' . $e->getMessage());
            logError('dbMssql.execute: ' . $e->getMessage());
        }

        return $stmt;
    }

    public static function ParseJsonColumnsValue($row)
    {
        //print_r($row);//die;
        if (count($row) > 0)
        {
            foreach ((array)$row as $key=>$value)
            {
                //echo "$key\r\n";
                if (strtolower(substr($key, -5)) == '_json')
                {
                    //echo "$key is json\r\n";
                    if (trim($value) == '')
                    {
                        $row[$key] = array();
                    }
                    else
                    {
                        if (isJson($value))
                        {
                            //echo "$key is really json\r\n";
                            //echo "$value\r\n";
                            $row[$key] = json_decode($value, true);
                            //print_r($row[$key]);
                        } 
                        else
                        {
                            echo "Invalid JSON\r\n";
                            echo "Key: $key\r\n";//Value:$value";
                            //$a= json_decode($value, true);
                            print_r($value);
                            //print_r($row);die;
                        }
                    }
                }
            }
        }

        return $row;
    }


    public static function FetchAssoc($stmt)
    {
        try
        {
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            $row = self::parseJsonColumnsValue($row);
        }
        catch (\PDOException $e)
        {
            logError('dbMssql.fetchAssoc: ' . $e->getMessage());
        }

        return $row;
    }

    public static function GetLastInsertId()
    {
        return self::$conn->lastInsertId();
    }

    public static function GetRowCount($tblName, $crit = '', $params = array())
    {
        $count = -1;

        $sql = "SELECT COUNT(*) AS row_count FROM $tblName";
        if ($crit != '')
        {
            $sql = $sql . " WHERE $crit";
        }

        if ($rows = self::FetchAll($sql, $params))
        {
            $count = $rows[0]['row_count'];
        }

        return $count;
    }

    public static function getUniqueColumns($tblName)
    {
        $uniqueColumns = array();

        $sql = "exec sp_helpindex $tblName";
        $stmt = data::execute($sql);
        while ($row = data::fetchAssoc($stmt))
        {
            $index_description = $row['index_description'];
            if (strpos($index_description, 'unique') !== false)
            {
                $uniqueColumns[] = $row['index_keys'];
            }
        }

        return $uniqueColumns;
    }

    public static function isIdentityColumn($tblName, $colName)
    {
        $isIdentity = false;

        $sql = "SELECT columnproperty(object_id('$tblName'),'$colName','IsIdentity') AS isIdentity";
        if ($rows = self::FetchAll($sql))
        {
            $isIdentity = $rows[0]['isIdentity'] != 0;
        }

        return $isIdentity;
    }

    public static function Insert($tblName, $data, $signUserId = 'author_user_id', $showSql = false)
    {
        if (parseArgs($data, $tblName))
        {
            $columns = array();
            $params = array();
            $arrTblCols = self::GetColumnProperty($tblName, "COLUMN_NAME");
    
            $errors = array();
    
            // Check for existing unique columns
            $uniqueColumns = self::getUniqueColumns($tblName);
            for ($n = 0; $n < count($uniqueColumns); $n++)
            {
                $col = $uniqueColumns[$n];
                if (!self::isIdentityColumn($tblName, $col))
                {
                    $count = self::GetRowCount($tblName, $crit = "$col = ?", array($data[$col]));
                    if ($count > 0)
                    {
                        $errors[] =  "Duplicate entry for " . self::niceColumnName($col) . " ($data[$col])";
                    }
                    elseif ($count == -1)
                    {
                        $errors[] = "System error triggered by " . self::niceColumnName($col);
                    }
                }
            }
    
            foreach ($data as $key=>$value)
            {
                if (!self::isIdentityColumn($tblName, $key))
                    if (in_array($key, $arrTblCols))
                    {
                        $columns[] = $key;
            
                        if (is_array($value))
                            $params[] = json_encode($value);
                        else
                        {
                            $allow_null = self::GetColumnProperty($tblName, "IS_NULLABLE", " COLUMN_NAME = '$key'")[0] == 'YES';
                            $data_type = self::GetColumnProperty($tblName, "DATA_TYPE", " COLUMN_NAME = '$key'")[0];
                            /*
                            if (!$allow_null && $value == '')
                            {
                                $errors[] = "Insert: Missing " . self::niceColumnName($key);
                            }
                            */
                            if ($data_type == 'int' || $data_type == 'decimal')
                            {
                                if (trim($value) != '' && !$allow_null)
                                {
                                    $value = cleanNumber($value);
                                    if (!is_numeric($value))
                                    {
                                        $errors[] = "Invalid value entered for " . self::niceColumnName($key);
                                    }
                                }
                            }
                            
                            if (!$allow_null && $value == '')
                            {
                                $params[] = '';
                            }
                            else
                            {
                                $params[] = ($value == '' ? null : $value);
                            }
                        }
                    }
            }
                
            if (count($errors) == 0)
            {
                if ($signUserId != '')
                {
                    $columns[] = $signUserId;
                    $params[] = Users::GetAuthUser()['user_id'];
                }
                $sql = "INSERT INTO $tblName (" . implode(",", $columns) . ") VALUES (" . rtrim(str_repeat('?,', count($columns)), ',') . ")";

                if ($showSql)
                {
                    $from = '?';
                    for ($n = 0; $n < count($params); $n++)
                    {
                        $sql = preg_replace( '/'.preg_quote($from, '/').'/', "'$params[$n]'", $sql, 1);
                    }
                    die($sql);
                }
    
                self::$conn->setAttribute( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );
                $stmt = self::$conn->prepare($sql);
                try
                {
                    // if ($tblName == 'document_items')
                    // {
                        // print_r($params);die($sql);
                    // }
                    $stmt->execute($params);
                    $id = self::getLastInsertId();
                    $response = array(1, 'new', array('id'=>$id));

                }
                catch (PDOException $e)
                {
                    $stmt = null;
                    $error = $e->getMessage();
                    $response = array(0, $error);
                }
            }
            else
            {
                $errors = implode("\r\n", $errors);
                $response = array(0, $errors);
            }
        }
        else
        {
            $response = array(0, 'System Error - Unable to create ' . Data::niceColumnName($tblName));

        }

        //$id = null;

        //print_r($response);die;
        return $response;
    }

    public static function Update($tblName, $obj, $idColumn="id", $signUserId = 'updated_user_id', $showSql = false)
    {
        if (parseArgs($obj, $tblName))
        {
            $id = null;
            $idValue = $obj[$idColumn];

            $columns = array();
            $params = array();
            $arrTblCols = self::GetColumnProperty($tblName, "COLUMN_NAME");
    
            $errors = array();
    
            // Check for existing unique columns
            $uniqueColumns = self::getUniqueColumns($tblName);
            for ($n = 0; $n < count($uniqueColumns); $n++)
            {
                $col = $uniqueColumns[$n];
                if (!self::isIdentityColumn($tblName, $col))
                {
                    $count = self::GetRowCount($tblName, $crit = "$col = ? AND $idColumn != ?", array($obj[$col], $idValue));
                    if ($count > 0)
                    {
                        $errors[] = "Duplicate entry for " . self::niceColumnName($col) . " ($obj[$col])";
                    }
                    elseif ($count == -1)
                    {
                        $errors[] = "System error triggered by " . self::niceColumnName($col);
                    }
                }
            }
    
            foreach ($obj as $key=>$value)
            {
                if (in_array($key, $arrTblCols) && $key != $idColumn && $key != 'time_updated' && $key != 'author_user_id')
                {
                    if (is_array($value))
                        $value = json_encode($value);
                    else
                    {
                        $value = $value . '';

                        $allow_null = self::GetColumnProperty($tblName, "IS_NULLABLE", " COLUMN_NAME = '$key'")[0] == 'YES';
                        $data_type = self::GetColumnProperty($tblName, "DATA_TYPE", " COLUMN_NAME = '$key'")[0];
    
                        if ($value == '')
                        {
                            //echo 'putting in null\r\n';
                            $value = NULL;
                        }
                        //$params[] = ($value == '' ? null : $value);
                        if (!$allow_null && $value == '')
                        {
                            die("value = $value");
                            $errors[] = "Missing " . self::niceColumnName($key);
                        }

                        if ($data_type == 'int' || $data_type == 'decimal')
                        {
                            if (trim($value) != '')
                            {
                                $value = cleanNumber($value);
                                if (!is_numeric($value))
                                {
                                    $errors[] = "Invalid value entered for " . self::niceColumnName($key);
                                }
                            }
                        }
                    }
    
                    $columns[$key] = $value;
                }
            }
    
            if (count($errors) == 0)
            {
                if ($signUserId != '')
                {
                    $columns[$signUserId] = Users::GetAuthUser()['user_id'];
                }
                
                $params = array();
                foreach($columns as $x=>$value)
                {
                    $params[] = $value;
                }
                //print_r($columns);die;
    
                $sql = "UPDATE $tblName SET time_updated = GETDATE(), " . implode(" = ?,", array_keys($columns)) . " = ? WHERE $idColumn = ?";
                $params[] = $idValue;
                //die($sql);
    
                if ($showSql)
                {
                    $from = '?';
                    for ($n = 0; $n < count($params); $n++)
                    {
                        $sql = preg_replace( '/'.preg_quote($from, '/').'/', "'$params[$n]'", $sql, 1);
                    }
                    die($sql);
                }
    
                self::$conn->setAttribute( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );
                $stmt = self::$conn->prepare($sql);

                try
                {
                    // print_r($params);die;
                    $stmt->execute($params);  
                    //$response = array(1, $id);
                    $response = array(1, 'update', array('id'=>$idValue));
                }
                catch (PDOException $e)
                {
                    $stmt = null;
                    $error = $e->getMessage();
                    $response = array(0, $error);
                }
            }
            else
            {
                $errors = implode("\r\n", $errors);
                $response = array(0, $errors);
            }
        }
        else
            $response = array(0, 'System Error - Unable to save ' . Data::niceColumnName($tblName));

        return $response;
    }
    
    public static function Search($tblName, $criterias, $orderBy = array())
    {
        $data = array();
        
        $sql = "SELECT * FROM $tblName ";
        $where = "";
        $params = array();

        if (count($criterias) > 0)
        {
            for ($n = 0; $n < count($criterias); $n ++)
            {
                $x = $criterias[$n];
                $x = explode(" ", $x, 3);
                $col = $x[0];
                $direct = (substr($col, 0, 1) == '@');
                if ($direct)
                {
                    $col = substr($col, 1);
                }

                $operator = strtoupper(trim($x[1]));
                $value = $x[2];
                
                if ($value != '')
                {
                    if (trim($where) != '')                
                    $where = $where . " AND ";

                    $where = $where . " $col $operator ";
                
                    if ($operator == "LIKE")
                    {
                        if ($direct)
                            $where = $where . " CONCAT('%', $value, '%') ";
                        else
                            $where = $where . " CONCAT('%', ?, '%') ";
                    }
                    else
                    {
                        if ($direct)
                            $where = $where .  " $value ";
                        else
                            $where = $where .  " ? ";
                    }

                    if (!$direct)                        
                        $params[] = $value;
                }
            }
        }

        if ($where != "")
            $sql = $sql . " WHERE $where";

        // print_r($params);die($sql);
        if (count($orderBy) > 0)
        {
            $sql = $sql . " ORDER BY " . implode(", ", $orderBy);
        }

        if ($stmt = Data::execute($sql, $params))
		{
            while($row = Data::fetchAssoc($stmt))
            {
                $data[] = $row;
            }
		}
        
        $response = array(1, count($data), $data);
        return $response;
    }
    
    public static function niceColumnName($colName)
    {
        $colName = ucwords(str_replace('_', ' ', $colName));
        return $colName;
    }

    public static function Escape($value, $encloseQuotes = true)
    {
        $value = str_replace("'", "''", $value);
        if ($encloseQuotes)
            $value = "'" . $value . "'";
        
        return $value;
    }
  
    public static function GetColumnProperty($tblName, $property, $crit = '')
    {
        $data = array();

        $sql = "SELECT $property FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ?";
        if ($crit != '')
            $sql = $sql . " AND $crit";

        $params = array($tblName);
        $stmt = self::Execute($sql, $params);
        while ($row = self::FetchAssoc($stmt))
        {
            $data[] = $row[$property];
        }
        
        return $data;
    }

}
?>