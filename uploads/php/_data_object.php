<?php
namespace iQuote;

class DataObject
{
    static $tblSelect;
    static $tblLookup;
    static $tblUpdate;
    
    static $lookup_max_return_rows_short_chars = 3;
    static $lookup_max_return_rows_short = 30;
    static $lookup_max_return_rows = 30;

    private static function validate($o)
    {
        $errors  = array();
        
        $tbl = static::$tblUpdate;
        $sql = "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ?";
        $p = array($tbl);
        $rows = Data::FetchAll($sql, $p);
        if (count($rows) > 0)
        {
            for ($x = 0; $x < count($rows); $x++)
            {
                $c = $rows[$x];
                if ($c['IS_NULLABLE'] == 'NO' && $c['COLUMN_NAME'] != 'id')
                {
                    if (!isset($o[$c['COLUMN_NAME']]))
                    {
                        $errors[] = $c['COLUMN_NAME'] . ' is required.';
                    }
                    elseif (trim($o[$c['COLUMN_NAME']] . '' == ''))
                    {
                        $errors[] = $c['COLUMN_NAME'] . ' is required.';
                    }
                }
            }
        }

        $sql = "";
        extract($o, EXTR_PREFIX_ALL, 'o');
        if (isset($o_id) && isset($o_code))
        {
            $sql = "SELECT COUNT(*) AS c FROM $tbl WHERE id <> ? AND code = ?";
            $p = array($o_id, $o_code);
        }
        elseif (isset($o_code))
        {
            $sql = "SELECT COUNT(*) AS c FROM $tbl WHERE code = ?";
            $p = array($o_code);
        }

        if ($sql != '')
        {
            $rc = Data::FetchSingle($sql, $p);
            if ($rc['c'] > 0)
            {
                $errors[] = 'Duplicate code';
            }
        }

        return $errors;
    }

    public static function _save($o)
    {
        $response  = array(0);
        $errors = self::validate($o);

        if (count($errors) == 0 )
        {
            if (isset($o['id']))
                $id = trim($o['id']);
            else
                $id = '';
            
            if ($id == '')
            {
                // die('create');
                $response = self::_create($o);
            }
            else
            {
                //die('update');
                $response = self::_update($o);
            }
                
        }
        else
        {
            $response = array(0, "", implode("\r\n", $errors));
        }

        return $response;
    }

    public static function _create($o = array())
    {
        $response  = array(0);
        $tbl = static::$tblUpdate;
        $response = Data::Insert($tbl, $o);
        
        return $response;
    }

    public static function _update($o = array())
    {
        //print_r($o);die($tbl);

        $tbl = static::$tblUpdate;
        $response  = array(0);
        //print_r($o); die($tbl);
        $response = Data::Update($tbl, $o);
        //$response = array(1, 'update', array('id'=>$o['id']));

        return $response;
    }

    public static function _retrieve($o = array())
    {
        $response  = array(0);
        
        $tbl = static::$tblSelect;

        $row = null;
        extract($o, EXTR_PREFIX_ALL, 'o');

        if (isset($o_id))
        {
            $sql = "SELECT * FROM $tbl WHERE id = ?";
            $p = array($o_id);
        }
        elseif (isset($o_code))
        {
            $sql = "SELECT * FROM $tbl WHERE code = ?";
            $p = array($o_code);
        }
        
        $row = Data::FetchSingle($sql, $p);

        if ($row)
        {
            $response = array(1, "", $row);           
        }

        return $response;
    }

    public static function _delete($o = array())
    {
        $tbl = static::$tblUpdate;
        $response = array(0);
        $sql = "DELETE FROM $tbl WHERE id = ?";
        $p = array($o['id']);
        if (Data::Execute($sql, $p))
        {
            $response = array(1);
        }

        return $response;
    }

    public static function _list($o = array(), $additional_search_columns = array())
    {
        extract($o, EXTR_PREFIX_ALL, 'v');
        $p = array();

        $tbl = static::$tblSelect;
        $sql = "SELECT * FROM $tbl";
        if (isset($v_term))
        {
            $crit = " id LIKE CONCAT('%',?,'%') OR code LIKE CONCAT('%', ?, '%') ";
            $p[] = $v_term;
            $p[] = $v_term;

            for ($x = 0; $x < count($additional_search_columns); $x++)
            {
                $crit = $crit . " OR " . $additional_search_columns[$x] . " LIKE CONCAT('%', ?, '%') ";
                $p[] = $v_term;
            }

            $crit = " ( " . $crit . " ) ";
        }
        
        if (isset($v_additional_crit))
        {
            $crit = "$crit $v_additional_crit";
            for ($x = 0; $x < count($v_additional_p); $x++)
            {
                $p[] = $v_additional_p[$x];
            }
        }
        
        $sql = $sql . " WHERE $crit";
        //print_r($p);die($sql);
        $rows = Data::FetchAll($sql, $p);
        $response = array(1, count($rows), $rows);

        return $response;
    }


    public static function _lookup($o = array(), $getExact = false, $additional_crit = '')
    {
        $tbl = static::$tblLookup;

        $v1 = isset($o['q']) ? $o['q'] : '';
        
        $filter = trim($v1);	
        $items = array();
        $keywords = explode(" ", $filter);
        $params = array();
        if ($getExact)
        {
            $sql = "SELECT * FROM $tbl WHERE name = ?";
            $params[] = $v1;
            if ($additional_crit != '')
                $sql .= " AND ($additional_crit)";
        }
        else
        {
            $sql = "SELECT * FROM $tbl";
            if (count($keywords) > 0)
            {
                $crit_a = " name LIKE '" . escape($keywords[0], 0) . "%'";
                $crit_b = sqlmultilike("name", $keywords, "AND");
                $crit_c = sqlmultilike("name", $keywords, "AND");
                
                if (count($keywords) == 1)
                {
                    $sql = $sql . " WHERE "; 
                    $sql = $sql . $crit_a . " OR code LIKE '%" . escape($keywords[0], 0) . "%'"; 
                
                    $sql = $sql . " UNION ALL ";
                    $sql = $sql . " SELECT * FROM $tbl";
                    $sql = $sql . " WHERE ";
                    $sql = $sql . $crit_b;
                    $sql = $sql . " AND NOT (" . $crit_a . ")";
                }
                else
                {
                    $sql = $sql . " WHERE ";
                    $sql = $sql . $crit_a;
                    $sql = $sql . " AND (" . sqlmultilike("name", $keywords, "AND") . ")";
                    
                    $sql = $sql . "UNION ALL ";
                    
                    $sql = $sql . " SELECT * FROM $tbl";
                    $sql = $sql . " WHERE ";
                    $sql = $sql . $crit_b;
                    $sql = $sql . " AND NOT (" . $crit_a . " AND (" . sqlmultilike("name", $keywords, "AND") . ")" . ")";
                }

                if ($additional_crit != '')
                $sql .= " AND ($additional_crit)";
            }
            else
            {
                if ($additional_crit != '')
                $sql .= " WHERE ($additional_crit)";
            }
        }

        $result = Data::execute($sql, $params);
        if (strlen($filter) <= static::$lookup_max_return_rows_short_chars)
            $max_rows = static::$lookup_max_return_rows_short;
        else
            $max_rows = static::$lookup_max_return_rows;
            
        if ($result)
        {
            $n=0;
            while ($row = Data::fetchAssoc($result))
            { 
                $items[$n] = $row;
                $items[$n]['id'] = $items[$n]['code'];
                $n++;
                
                if ($n > $max_rows - 1)
                    break;
            }
        }
        
        //$set_product_list = new productList($items);
        $response = array(1, count($items), $items);

        return $response;
    }

    public static function _search($o = array())
    {
        $response = Data::Search(static::$tblSelect, $o, 'OR');
        return $response;
    }

    public static function GetCount($o = array())
    {
        $tbl = static::$tblSelect;
        $count = -1;

        if (isset($o['id']))
        {
            $sql = "SELECT count(*) as c FROM $tbl WHERE code = ?";
            $p = array($o['id']);
        }
        elseif (isset($o['code']))
        {
            $sql = "SELECT count(*) as c FROM $tbl WHERE id = ?";
            $p = array($o['code']);
        }
        
        if ($row = Data::FetchSingle($sql, $p))
        {
            $count = $row['c'];
        }

        return $count;
    }

}

?>