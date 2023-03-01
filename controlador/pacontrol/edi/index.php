<?php 
set_time_limit(-1);
$op = $_GET["op"];
require_once('../../codificar.php');
switch($op)
{
    case "actualiza": actualiza();break;
}

function actualiza(){
    error_reporting(0);
    require_once('../../../connectdb.php'); 
    odbc_autocommit($connect, FALSE);
    $data = json_decode(file_get_contents("php://input"));
    $empresa = $data->empresa->codigo;
    $params = $data->params;
    $host = $params->hostdb;
    $user = $params->userdb;
    $pass = $params->passdb;
    $estado_deuda=$data->estado_deuda->id;
    $valor_deuda=$data->valor_deuda?$data->valor_deuda:0;
    $notificacion_deuda=$data->notificacion_deuda;
    $aplica_todas=$data->aplica_todas;
    //print_r($estado_deuda);return
    $connect = @odbc_connect($host, $user, $pass);
    if (!$connect){
        echo http_response_code(500);
        die();
    }
    if ($aplica_todas=='1') {
        $query="UPDATE web_ge_parametros set parametro='$estado_deuda' where codigo='158';
                UPDATE web_ge_parametros set parametro='$valor_deuda' where codigo='161';
                UPDATE web_ge_parametros set parametro='$notificacion_deuda' where codigo='172';
        ";
    }else{
        $query="UPDATE web_ge_parametros set parametro='$estado_deuda' where codigo='158' and empresa='$empresa';
                UPDATE web_ge_parametros set parametro='$valor_deuda' where codigo='161' and empresa='$empresa';
                UPDATE web_ge_parametros set parametro='$notificacion_deuda' where codigo='172' and empresa='$empresa';
        ";
    }
    //print_r($query);return;
    odbc_exec($connect, utf8_decode($query));

    if(odbc_error()){
        odbc_rollback($connect);
        echo json_encode(array(
            'success'=>false, 
            'msg'=>'No se pudo actualizar estado! '.substr(odbc_errormsg($connect),35)
        ));
        return;
    }
    else{
        odbc_commit($connect);
        echo json_encode(array(
            'success'=>true, 
            'msg'=>'Estado actualizado exitosamente!'
        ));
    }    

    odbc_close($connect);
}

?>
