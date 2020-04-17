<?php
namespace iQuote;
require_once 'vendor/autoload.php';

//require_once "config.php";

function dump($value)
{
    $sql = 'INSERT INTO dump (contents) VALUES (?)';
    $params = array($value);
    Data::Execute($sql, $params);
}

function sqlShow($params, $sql)
{
    print_r($params);die($sql);
}

function niceDate($date)
{
    $dateFormat = getSysVar('date_format');
    return date($dateFormat, strtotime($date));
}

function getCountries()
{
    return $this->getSysVar("countries");
}

function getSysVar($sysVarName)
{
    $sysValue = null;

    $sql = "SELECT * FROM system_vars WHERE code = ?";
    $params = array($sysVarName);
    $stmt = Data::execute($sql, $params);
    if ($obj = Data::fetchAssoc($stmt))
    {
        $sysValue = $obj["value"];
    }
    return $sysValue;
}

function setSysVar($sysVarName, $value)
{
    global $tblSysVars;
    $sysValue = null;

    $sql = "SELECT * FROM $tblSysVars WHERE code = ?";
    $params = array($sysVarName);
    $rows = Data::FetchAll($sql, $params);
    if (count($rows) > 0)
    {
        // Update
        $sql = "UPDATE $tblSysVars SET value = ? WHERE code = ?";
        $params = array($value, $sysVarName);
        Data::Execute($sql, $params);
    }
    else
    {
        // Insert
        $sql = "INSERT INTO $tblSysVars (code, value) VALUES (?, ?)";
        $params = array($sysVarName, $value);
        Data::Execute($sql, $params);
    }
    return $sysValue;
}

function getSysVarDataByKey($sysVarName, $cKey, $cValue, $specCol = null)
{
    $data = null;

    $sysValue = getSysVar($sysVarName);
    if (!is_null($sysValue))
    {
        $json = json_decode($sysValue, true);
        if (!is_null($json))
        {
            foreach ($json as $row)
            {
                if (strtoupper($row[$cKey]) == strtoupper($cValue))
                {
                    if (is_null($specCol))
                        $data = $row;
                    else
                        $data = $row[$specCol];
                }                        
            }
        }
    }

    return $data;
}

function generateDocNumber($docType, $params = array())
{
    $docNum = "";
   
    $internalParams = array('YY'=>date('y'), 'MM'=>date('m'));
    $doc_format = getSysVar("DOC_FORMAT");
    $docJson = json_decode(strtoupper($doc_format), true);
   //print_r($doc_format);die;
    if (isset($docJson[$docType]))
    {
        //print_r($doc_format);die;
       
        $docFmt = $docJson[$docType]["FMT"];
        $docZp = $docJson[$docType]["ZP"];
        $docRef = explode(".",$docJson[$docType]["REF"]);
        $docNum = $docFmt;
        
        foreach ($internalParams as $param=>$value)
        {
            $param = strtoupper($param);
            $docNum = str_replace("[$param]", $value, $docNum);
        }
        
        foreach ($params as $param=>$value)
        {
            $param = strtoupper($param);
            $docNum = str_replace("[$param]", $value, $docNum);
        }

        $docNum = str_replace("[COUNTER]","%", $docNum);
        $docNum = str_replace("'","''", $docNum);
        $sql = "SELECT TOP 1 $docRef[1] FROM $docRef[0] WHERE $docRef[1] LIKE '" . str_replace("'","''", $docNum) . "' ORDER BY $docRef[1] DESC";
        $stmt = Data::execute($sql);
        
        if ($obj = Data::fetchAssoc($stmt))
        {
            $id = $obj[$docRef[1]];
            // Strip id to counter only
            $fixed = explode("%", $docNum);
            for ($n=0; $n<count($fixed); $n++)
            {
                $id = str_replace($fixed[$n], "", $id);
            }
        }
        else
            $id = 0;

        $id ++;
        $id = str_pad($id, $docZp, "0", STR_PAD_LEFT);
        $docNum = str_replace("%", $id, $docNum);        
    }
    
    return $docNum;
}

function logError($errMessage)
{
    global $apiResponse;
    $apiResponse['data'] = array("errorMsg" => $errMessage);
    
    die ($apiResponse['data']['errorMsg']);
    echo $errMessage . "<br/>";
    die;
}

function formGet($url, $params, $headers=null)
{
    $hdr = array();
    $params  = json_encode($params);   
                                                                                                                        
    $ch = curl_init($url);
    array_push($hdr, 'Content-Type: application/json');
    array_push($hdr, 'Content-Length: ' . strlen($params));
    if (isset($headers))
    {
        foreach ($headers as $header)
        {
            array_push($hdr, $header);
        }

    }        
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $hdr);
    $data = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $result = array("code"=>$code, "data"=>$data);
    return $result;
}

function sumOfArrayVar($array, $element, $max_index = -1)
{
    $sum = 0;

    if ($max_index == -1)
        $max_index = count($array) - 1;

    for ($x = 0; $x <= $max_index; $x++)
        $sum = $sum + $array[$x][$element];

    return $sum;
}

function formPost($url, $params, $headers=null)
{
    $hdr = array();
    $params  = json_encode($params);   
                                                                                                                        
    $ch = curl_init($url);
    array_push($hdr, 'Content-Type: application/json');
    array_push($hdr, 'Content-Length: ' . strlen($params));
    if (isset($headers))
    {
        foreach ($headers as $header)
        {
            array_push($hdr, $header);
        }
    }        
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $hdr);
    $data = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $result = array("code"=>$code, "data"=>$data);
    return $result;
}

function isDate($date, $format = 'Y-m-d')
{
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) == $date;
}    

function getSysVarJson($id)
{
    $n = getSysVar($id);
    $n = json_decode($n);
    return $n;
}

function postcodeLookup($get)
{
    $value = isset($get['v']) ? $get['v'] : '';
    $limit = isset($get['limit']) ? $get['limit'] : '10';
    
    $value = trim($value);
    
    $data = array();
    if ($value != '')
    {
        $tblPostals = DT["POSTALS"];
        
        $sql = "SELECT TOP $limit postcode AS id, CONCAT(address_house_no, ' ', address_street) AS name FROM $tblPostals WHERE
                (
                    postcode LIKE CONCAT('%', ?,'%')
                    OR
                    CONCAT(address_house_no, ' ', address_street) LIKE CONCAT('%', ?,'%')
                )
                ";

        $params = array($value, $value);
        $data = Data::FetchAll($sql, $params);
    }
    
    return array(1, count($data), $data);
}

function hasVars($obj, $arr, &$response = '')
{
    $apiMsg = "";
    $apiData = null;

    if (!is_array($obj) && $obj != '')
    {
        $apiMsg = "Malformed POST";
    }
    else
    {
        $data = '';
        $count = count($arr);
        $x = 0;
        $missing = '';

        if ($count > 0)
        {
            $data = array();
            for ($n=0;$n<$count;$n++)
            {
                $varName = $arr[$n];
                $data[$varName] = "";

                if (isset($obj[$varName]))
                    $x++;
                else {
                    $missing = $missing . $varName . ' ';
                }
            }
        }

        if ($x != $count)
        {
            $apiMsg = "Incomplete parameters ($x / $count): $missing";
        }
    }

    if ($apiMsg != "")
    {
        $response = array(0, $apiMsg, $apiData, 200);
    }    

    return $apiMsg == "";
}

function setApiReturn($sApiCode, $sApiMsg = null, $sApiData = null, $sHttpResponseCode = 200)
{
    global $apiCode, $apiMsg, $apiData, $httpResponseCode;
    
    $httpResponseCode = $sHttpResponseCode;

    $apiCode = $sApiCode . "";
    $apiMsg = $sApiMsg;
    $apiData = $sApiData;
}

function apiResponse()
{
    global $apiCode, $apiMsg, $apiData, $httpResponseCode;
    return array("apiCode"=>$apiCode, "apiMsg"=>$apiMsg, "apiData"=>$apiData);
}

function setApiResponse($response)
{
    global $apiCode, $apiMsg, $apiData, $httpResponseCode;

    $apiCode = $response['apiCode'];
    $apiMsg = $response['apiMsg'];
    $apiData = $response['apiData'];
}

function parseArgs(&$post, $tableRef)
{
    $ok = false;
    $requiredCols = array();
    $optionalCols = array();

    // Get column definition from $tableRef
    $sql = "SELECT col.TABLE_CATALOG AS [Database]
    , col.TABLE_SCHEMA AS Owner
    , col.TABLE_NAME AS TableName
    , col.COLUMN_NAME AS ColumnName
    , col.ORDINAL_POSITION AS OrdinalPosition
    , col.COLUMN_DEFAULT AS DefaultSetting
    , col.DATA_TYPE AS DataType
    , col.CHARACTER_MAXIMUM_LENGTH AS MaxLength
    , col.DATETIME_PRECISION AS DatePrecision
    , CAST(CASE col.IS_NULLABLE
               WHEN 'NO' THEN 0
               ELSE 1
           END AS bit)AS IsNullable
    , COLUMNPROPERTY(OBJECT_ID('[' + col.TABLE_SCHEMA + '].[' + col.TABLE_NAME + ']'), col.COLUMN_NAME, 'IsIdentity')AS IsIdentity
    , COLUMNPROPERTY(OBJECT_ID('[' + col.TABLE_SCHEMA + '].[' + col.TABLE_NAME + ']'), col.COLUMN_NAME, 'IsComputed')AS IsComputed
    , CAST(ISNULL(pk.is_primary_key, 0)AS bit)AS IsPrimaryKey
    FROM INFORMATION_SCHEMA.COLUMNS AS col
      LEFT JOIN(SELECT SCHEMA_NAME(o.schema_id)AS TABLE_SCHEMA
                     , o.name AS TABLE_NAME
                     , c.name AS COLUMN_NAME
                     , i.is_primary_key
                  FROM sys.indexes AS i JOIN sys.index_columns AS ic ON i.object_id = ic.object_id
                                                                    AND i.index_id = ic.index_id
                                        JOIN sys.objects AS o ON i.object_id = o.object_id
                                        LEFT JOIN sys.columns AS c ON ic.object_id = c.object_id
                                                                  AND c.column_id = ic.column_id
                 WHERE i.is_primary_key = 1)AS pk ON col.TABLE_NAME = pk.TABLE_NAME
                                                 AND col.TABLE_SCHEMA = pk.TABLE_SCHEMA
                                                 AND col.COLUMN_NAME = pk.COLUMN_NAME
    WHERE col.TABLE_NAME = ?
    AND col.TABLE_SCHEMA = 'dbo'
    ORDER BY col.TABLE_NAME, col.ORDINAL_POSITION;";
    $params = array($tableRef);
    if ($stmt = Data::execute($sql, $params))
    {
        while ($row = Data::fetchAssoc($stmt))
        {
            if (!$row['IsIdentity'])
            {
                if (!$row['IsNullable']) 
                    $requiredCols[] = $row['ColumnName'];
                else
                    $optionalCols[] = $row['ColumnName'];
            }
        }
    }

    //print_r($requiredCols);die;
    if (hasVars($post, $requiredCols))
    {
        /*
        for ($n = 0; $n < count($optionalCols); $n++)
        {
            $col = $optionalCols[$n];
            if (!isset($post[$col]))
                $post[$col] = null;
        }
        */
        $ok = true;
    }

    return $ok;
}

function spreadAmount($spreadAmount, $spreadLength)
{
    $spread = array();

    $accrued = 0;
    for ($n = 1; $n <= $spreadLength; $n++)
    {
        //=ROUND(($B$5*D2)/$B$4,2)-F1
        $amount = round(($spreadAmount * $n) / $spreadLength, 2) - $accrued;
        $accrued = $accrued + $amount;

        $spread[] = $amount;
    }

    return $spread;
}
function saveMemberDocuments($post)
{
     global $tblMemberDocuments;

    $document_name = $post['document_name'];
    $upload_id = $post['upload_id'];
    $member_id = $post['member_id'];
            
    $sql = "INSERT INTO $tblMemberDocuments (document_name, upload_id, member_id) 
    VALUES 
    (?, ?, ?)";
    $params = array($document_name, $upload_id, $member_id);
    Data::Execute($sql,$params);

    $id = Data::GetLastInsertId();

    $response = array(1,"",$id);

    return $response;

}

function upload($fileVars)
{
    global $MediaDir;
    
    $tblName = "medias";

    $upload_id = '';
    $response = array(0, "Empty upload");

    foreach ($fileVars as $key=>$value)
    {
        $data = $value;
        
        $upload_friendly_name = str_replace('_', ' ', $key);
        $name = $value['name'];
        $type = $value['type'];
        $tmp_name = $value['tmp_name'];
        $error = $value['error'];
        $size = $value['size'];

        $maxFileSize = 50000000;
        $allowedExtensions = array("jpg", "png", "pdf", "bmp", "gif", "mov", "mp3", "mp4");
        
        $target_dir = $MediaDir . 'products/'; //"E:/Workspace/SAGE9/api.bsys.sage9.com/wwwroot/uploads/";
        $fileExtension = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        if ($size <= $maxFileSize)
        {
            $alias = random_filename();
            $rndFilename = $alias . '.' . $fileExtension;
            $target_file = $target_dir . basename($rndFilename);
            $data['friendly_name'] = $upload_friendly_name;
            $data['code'] = $alias;
            $data['file_path'] = $target_file;
            $data['file_extension'] = $fileExtension;
                    
            if (move_uploaded_file($tmp_name, $target_file)) 
            {
                $response = Data::Insert($tblName, $data);
                if ($response[0] == 1)
                    $response = array(1, $response[1]);
                else
                    $response = array(0, "Error recording upload to database");
            } 
            else 
            {
                $response = array(0, "File upload failure - please try again");
            }
        }
        else
        {
            $response = array(0, "Filesize exceeds threshold of " . $maxFileSize / 1024 . "KB");
        }        
    }

    return $response;
}


function fileRemove($postVars)
{
    $response = array();

    if (hasVars($postVars, array('upload_id'), $response))
    {
        $tblName = DT['UPLOADS'];
        extract($postVars);
        
        $response = array();
        $sql = "SELECT * FROM $tblName WHERE upload_id = ?";
        $params = array($upload_id);
        $rows = Data::fetchAll($sql, $params);
        
        if (count($rows) > 0)
        {
            $response = array(1, '', '');
        }
    }
    
    return $response;
}

function random_filename($extension = '', $length = 30, $directory = '')
{
    // default to this files directory if empty...
    $dir = !empty($directory) && is_dir($directory) ? $directory : dirname(__FILE__);

    do {
        $key = '';
        $keys = array_merge(range(0, 9), range('a', 'z'));

        for ($i = 0; $i < $length; $i++) {
            $key .= $keys[array_rand($keys)];
        }
    } while (file_exists($dir . '/' . $key . (!empty($extension) ? '.' . $extension : '')));

    return $key . (!empty($extension) ? '.' . $extension : '');
}

function isJson($string) {
    return ((is_string($string) &&
            (is_object(json_decode($string)) ||
            is_array(json_decode($string))))) ? true : false;
}

function generateRandomNumString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $characters = '0123456789';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

function cleanNumber($value)
{
    $value = str_replace(",", "", $value);
    if (is_numeric($value))
        $value = (float)$value;
        
    return $value;
}

function dumpFile($value, $filename = 'general.txt', $overwrite = false)
{
    if (is_array($value))
        $value = json_encode($value);

    
    if (file_exists($filename))
        $current = $overwrite ? '' : file_get_contents($filename);
    else
        $current = '';

    $current .= "$value\r\n";
    file_put_contents($filename, $current);
}

/**
 * Function parse_condition
 * @param string $condition
 * @return bool
 */
function parse_condition($condition)
{
    // match {a} {condition} {b}
    preg_match('/.*(.*?)\s(.*?)\s(.*?)/sU', $condition, $condition_matches);

    // condition_matches[1] will be a, remove $, () and leading/tailing spaces
    $a = trim(str_replace(array('$','()'),'',$condition_matches[1]));

    // condition_matches[2] will be the operator
    $operator = $condition_matches[2];

    // condition_matches[3] will be b, remove $, () and leading/tailing spaces
    $b = trim(str_replace(array('$','()'),'',$condition_matches[3]));


    // It is advisable to pass variables into array or a "hive"
    // but in this example, let's just use global

    // Make variable's variable $$a accessible
    global $$a;
    // And for $$b too
    global $$b;

    $cmp1 = isset($$a)?($$a):($a);

    $cmp2 = isset($$b)?($$b):($b);

    switch($operator){
        case '==':
            return($cmp1 == $cmp2);
            break;
        case '!=':
            return($cmp1 != $cmp2);
            break;
        case '===':
            return($cmp1 === $cmp2);
            break;
        case '!==':
            return($cmp1 !== $cmp2);
            break;
        case '>':
            return($cmp1 > $cmp2);
            break;
        case '>=':
            return($cmp1 >= $cmp2);
            break;
    
        default:
            return false;
            break;
    }   
}

function revisionNew($subject, $data)
{
    $tblRevisions = DT['REVISIONS'];
    $sql = "INSERT INTO $tblRevisions (subject, data)";
}

function ncDateAllowed($date)
{
    return true;
}

function raiseException($msg = "")
{
    
}

function archiveData($tblSource, $colId, $colValue, $remarks = null)
{
    $sql = "SELECT * FROM $tblSource WHERE $colId = ?";
    $params = array($colValue);
    $rows = Data::FetchAll($sql, $params);
    
    $id = "";
    
    if (count($rows) > 0)
    {
        $data = json_encode($rows[0]);
        $tblArchives = DT['ARCHIVES'];
        $sql = "INSERT INTO $tblArchives (source_table, source_id_col, source_id_value, contents, remarks) VALUES (?, ?, ?, ?, ?)";
        $params = array($tblSource, $colId, $colValue, $data, $remarks);
        Data::Execute($sql, $params);   
        $id = Data::GetLastInsertId();
    }

    return $id;
}

function DownloadFile($file_alias)
{    
    global $tblUploads;

    $sql = "SELECT * FROM $tblUploads WHERE file_alias = ?";
    $params = array($file_alias);
    $rows = Data::FetchAll($sql, $params);

    if (count($rows) > 0)
    {
        $file_path = $rows[0]['file_path'];
        $file_name = $rows[0]['name'];

        if (file_exists($file_path))
        {
            if(false !== ($handler = fopen($file_path, 'r')))
            {
                
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename='.basename($file_name));
                header('Content-Transfer-Encoding: binary');
                header('Expires: 0');
                header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                header('Pragma: public');
                header('Content-Length: ' . filesize($file_path)); //Remove

                ob_clean();
                flush();
                readfile($file_path);
            }
            die;
        }
    }
    die;
}

function arrayOrderBy(array &$arr, $order = null) 
{
    if (is_null($order)) {
        return $arr;
    }
    $orders = explode(',', $order);
    usort($arr, function($a, $b) use($orders) {
        $result = array();
        foreach ($orders as $value) {
            list($field, $sort) = array_map('trim', explode(' ', trim($value)));
            if (!(isset($a[$field]) && isset($b[$field]))) {
                continue;
            }
            if (strcasecmp($sort, 'desc') === 0) {
                $tmp = $a;
                $a = $b;
                $b = $tmp;
            }
            if (is_numeric($a[$field]) && is_numeric($b[$field]) ) {
                $result[] = $a[$field] - $b[$field];
            } else {
                $result[] = strcmp($a[$field], $b[$field]);
            }
        }
        return implode('', $result);
    });
    return $arr;
}
function findInArray($array, $colname, $value)
{
    $index = -1;

    for ($x = 0; $x < count($array); $x++)
    {
        if ($array[$x][$colname] == $value)
        {
            $index = $x;
            break;
        }           
    }

    return $index;
}

function getDownloadUrl($upload_id)
{
    global $tblUploads;

    $downloadUrl = '';

    $sql = "SELECT * FROM $tblUploads WHERE upload_id = ?";
    $params = array($upload_id);
    $rows = Data::FetchAll($sql, $params);

    if (count($rows) > 0)
    {
        $downloadUrl = getBaseUrl() . 'download?' . $rows[0]['file_alias'];
    }

    return $downloadUrl;
}

function testMode()
{
    return true;
}

function getBaseUrl() 
{
    // output: /myproject/index.php
    $currentPath = $_SERVER['PHP_SELF']; 

    // output: Array ( [dirname] => /myproject [basename] => index.php [extension] => php [filename] => index ) 
    $pathInfo = pathinfo($currentPath); 

    // output: localhost
    $hostName = $_SERVER['HTTP_HOST']; 

    // output: http://
    $protocol = strtolower(substr($_SERVER["SERVER_PROTOCOL"],0,5))=='https'?'https':'http';
    // Hardcode for now
    $protocol = strtolower('https');

    // return: http://localhost/myproject/
    $url = $protocol.'://'.$hostName.$pathInfo['dirname'];
    if (substr($url, -1, 1) == '\\')
    {
        $url = substr($url, 0, strlen($url) - 1);
    }
    $url = $url . '/';
    return $url;
}

function readExcelIntoArray($filename)
{
    $array = array();

    $data = new Spreadsheet_Excel_Reader($filename);
    $sheet = $data->sheets[0];

    $numRows = $sheet['numRows'];
    $numCols = $sheet['numCols'];

    //echo json_encode($sheet);die;

	if ($numRows > 0)
	{
        for($y=1; $y<=$numRows; $y++) // loop used to get each row of the sheet
        {
            for($x=1; $x<=$numCols; $x++) // This loop is created to get data in a table format.
            {
                if (isset($sheet['cells'][$y][$x]))
                    $value = $sheet['cells'][$y][$x];
                else
                    $value = '';

                $row[$x - 1] = $value;
            }
            $array[] = $row;            
        }
    }
    
    return $array;
}

function AddYYMM($yymm, $offset)
{
    $yy = (int)\substr($yymm, 0, 2);
    $mm = (int)\substr($yymm, 2, 2);

    $mm = $mm + $offset;
    if ($mm == 0)
    {
        $mm = '12';
        $yy--;
    }
    elseif ($mm == 13)
    {
        $mm = '01';
        $yy++;
    }
    elseif ($mm < 10)
    {
        $mm = '0' . $mm;
    }
    $yymm = $yy . $mm;
    return $yymm;
}

function isValidEmail($email)
{
    $is_valid = (filter_var($email, FILTER_VALIDATE_EMAIL));
    
    return $is_valid;
}

function isValidMobileNumber($number)
{
    $is_valid = 0;

    if (strlen($number) == 8)
    {
        if (is_numeric($number))
        {
            $first_digit = substr($number, 0, 1);
            if ($first_digit == '8' || $first_digit == '9')
            {
                $is_valid = 1;
            }
        }
    }

    return $is_valid;
}

function email($email, $contents, $subject)
{
    if ($contents != '')
    {
        $headers = 'From: sales@biggeek.toys' . "\r\n" .
            'Reply-To: sales@biggeek.toys' . "\r\n" .
            'Content-Type: text/html; charset=UTF-8' . "\r\n";

        mail($email, $subject, $contents, $headers);
    }    
}
function email_notification($from_email,$to_email,$contents, $subject)
{
    if ($contents != '')
    {
        $headers = 'From: '. $from_email . "\r\n" .
            'Reply-To: '.$from_email . "\r\n" .
            'Content-Type: text/html; charset=UTF-8' . "\r\n";

        mail($to_email, $subject, $contents, $headers);
    }    
}
function contactUsEmail($bg_email, $contents, $subject,$customer_email)
{
    $response = '';

    if ($contents != '')
    {
        $headers = 'From: '. $customer_email . "\r\n" .
            'Reply-To: '.$customer_email . "\r\n" .
            'Content-Type: text/html; charset=UTF-8' . "\r\n";

        $response = mail($bg_email, $subject, $contents, $headers);
    }   
    
    return $response;
}

function sms($mobile, $contents, $country_code = '65')
{
    if (strlen($mobile) == 8)
    {
        $mobile = "$country_code$mobile";
    }

    $basic  = new \Nexmo\Client\Credentials\Basic("b7e374a7", "ASkjgflkrefehgfuy54890r5");
    $client = new \Nexmo\Client($basic);

    $message = $client->message()->send([
        'to' => $mobile,
        'from' => 'BigGeekToys',
        'text' => $contents
    ]);
}

function escape($value, $encloseQuotes = true)
{
    $value = str_replace("'", "''", $value);
    if ($encloseQuotes)
        $value = "'" . $value . "'";
    
    return $value;
}

function sqlmultilike($col, $items, $operator)
{
    $sql = "";

    $c = count($items);
    
    for ($i = 0; $i < $c; $i++)
    {
        if ($sql != '')
            $sql = $sql . " " . $operator . " ";
        
        $sql = $sql . " " . $col . " LIKE '%" . escape($items[$i], false) . "%'";
    }

    return $sql;
}        

function RowExists($tblName, $col, $value)
{
    $is_exist = false;

    $sql = "SELECT COUNT(*) AS c FROM $tblName WHERE $col = ?";
    $p = array($value);
    if ($row = Data::FetchSingle($sql, $p))
    {
        $is_exist = $row['c'] > 0;
    }

    return $is_exist;
}

?>