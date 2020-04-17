<?php
namespace iQuote;
class system_vars
{
    public static function name()
    {
        $name = substr(get_called_class(), strrpos(get_called_class(), '\\') + 1);
        return $name;
    }

    public static function _create($vars)
    {
        $response = array(0);
        if (hasVars($vars, array('code','name'), $response) || 1)
        {
            if ($vars['id'] == '')
                $vars['id'] = null;
                
            $rows = Data::ExecuteSP(self::name(), "create", $vars);
            $response = array(1, count($rows), $rows);
        }

        return $response;
    }

    public static function _list($vars)
    {
        $rows = Data::ExecuteSP(self::name(), "list", $vars);
        $response = array(1, "Ok", $rows);
        
        return $response;
    }
    
    public static function _retrieve($vars)
    {
        $response = array(0);

        extract($vars, EXTR_PREFIX_ALL, "v");
        if (isset($v_code))
        {
            $sql = "SELECT * FROM system_vars WHERE code = ?";
            $p = array($v_code);
            if ($row = Data::FetchSingle($sql, $p))
            {
                if ($row['is_object'])
                    $response = array(1, 1, json_decode($row['value'], true));
                else
                    $response = array(1, 1, $row['value']);
            }
        }

        return $response;
    }

    public static function _update($vars)
    {
        $response = array(0);
        if (hasVars($vars, array('id'), $response))
        {
            $rows = Data::ExecuteSP(self::name(), "update", $vars);
            $response = array(1, count($rows), $rows);
        }
        return $response;
    }

    public static function _delete($vars)
    {
        $response = array(0);
        if (hasVars($vars, array('id'), $response))
        {
            $rows = Data::ExecuteSP(self::name(), "delete", $vars);
            $response = array(1, count($rows), $rows);
        }
        return $response;
    }


}

?>