<?php 
set_time_limit(-1);
$op = $_GET["op"];
require_once('../../codificar.php');
switch($op)
{
    case "empresas": empresas();break;
    case "sucursales":sucursales();break;
    case "detalle":detalle();break;
    case "grafico":grafico();break;
    case "sucursales2":sucursales2();break;
    case "detalles":detalles();break;
    case "numeros":numeros();break;

}
 function empresas(){
 	require_once('../../../connectdb.php');
	$query = "SELECT * FROM empresa";
	$res=odbc_exec($connect, $query);
	if (odbc_num_rows($res) ==0) {
        echo json_encode(array(
        'success'=>false, 
        'msg'=>'No hay empresas para mostrar'.substr(odbc_errormsg($connect),35)
        ));
        odbc_close($connect);
        return;
    }
    $data= array();
     while ($fila = odbc_fetch_array($res))
    {
        $fila = codificar($fila);
        $data[] = $fila;
    }   
    if(odbc_error()){
        echo json_encode(array(
            'success'=>false, 
            'msg'=>'Ocurrió un error al obtener empresas '.substr(odbc_errormsg($connect),35)
        ));
    
    }
    else{
        echo json_encode(array(
            'success'=>true, 
            'data'=>$data
        ));
    }
    odbc_close($connect);
 }

 function sucursales(){
 	//require_once('../../../connectdb.php');
 	$data = json_decode(file_get_contents("php://input"));
    $host = $data->hostdb;
    $user = $data->userdb;
    $pass = $data->passdb;
    $connect = @odbc_connect($host, $user, $pass);
    if (!$connect){
        echo http_response_code(500);
        die();
    }

	$query = "SELECT * from ge_empresa order by codigo asc";
	$res=odbc_exec($connect, $query);
    //print_r($resfecha);return;
	if (odbc_num_rows($res) ==0) {
        echo json_encode(array(
        'success'=>false, 
        'msg'=>'No hay empresas para mostrar'.substr(odbc_errormsg($connect),35)
        ));
        odbc_close($connect);
        return;
    }
    $datos= array();
    while ($fila = odbc_fetch_array($res))
    {
        $fila = codificar($fila);
        $datos[] = $fila;
    }   
    if(odbc_error()){
        echo json_encode(array(
            'success'=>false, 
            'msg'=>'Ocurrió un error al obtener empresas '.substr(odbc_errormsg($connect),35)
        ));
    
    }
    else{
        echo json_encode(array(
            'success'=>true, 
            'datos'=>$datos,
        ));
    }
    odbc_close($connect);
 }
 function sucursales2(){
    require_once('../../../connectdb.php');
    $c=$_GET['c'];
    $query = "SELECT empresa, nombre_empresa, (SELECT TOP 1 codigo FROM ge_cabecera WHERE empresa='$c' ORDER BY codigo DESC) as codca, anio from ge_movimiento where nombre_empresa = '$c' and codca=cabecera group by cabecera, empresa, nombre_empresa, anio order by empresa desc";
    $res=odbc_exec($connect, $query);
    $fecha="SELECT TOP 1 fecha FROM ge_cabecera order by fecha desc";
    $resfecha=odbc_exec($connect, $fecha);
    $resfecha=odbc_fetch_array($resfecha);
    //print_r($resfecha);return;
    if (odbc_num_rows($res) ==0) {
        echo json_encode(array(
        'success'=>false, 
        'msg'=>'No hay empresas para mostrar'.substr(odbc_errormsg($connect),35)
        ));
        odbc_close($connect);
        return;
    }
    $datos= array();
     while ($fila = odbc_fetch_array($res))
    {
        $fila = codificar($fila);
        $datos[] = $fila;
    }   
    if(odbc_error()){
        echo json_encode(array(
            'success'=>false, 
            'msg'=>'Ocurrió un error al obtener empresas '.substr(odbc_errormsg($connect),35)
        ));
    
    }
    else{
        echo json_encode(array(
            'success'=>true, 
            'datos'=>$datos,
            'fecha'=>$resfecha['fecha'],
            'empresa'=>$c
        ));
    }
    odbc_close($connect);
 }

 function detalle(){
    error_reporting(0);
 	$data = json_decode(file_get_contents("php://input"));
 	$codigo = $data->empresa->codigo;
    $params = $data->params;
    $desde = $data->filtros->desde;
    $hasta = $data->filtros->hasta;
    $host = $params->hostdb;
    $user = $params->userdb;
    $pass = $params->passdb;
    $connect = @odbc_connect($host, $user, $pass);
    if (!$connect){
        echo http_response_code(500);
        die();
    }
    ////// Control de paramtetro para todas las empresas
    $query="SELECT codigo from ge_empresa";
    $res=odbc_exec($connect,$query);
    $empresas=array();
    while ($fila=odbc_fetch_array($res)) {
        $fila=codificar($fila);
        $empresas[]=$fila;
    };
    foreach ($empresas as $key => $value) {
        // print_r($value['codigo']);return;
        $emp=$value['codigo'];
        $query="SELECT parametro as estado_deuda FROM web_ge_parametros where empresa='$emp' and codigo='158'";
        $result=odbc_exec($connect, $query);
        if (odbc_num_rows($result)==0) {
            $query="INSERT INTO web_ge_parametros (codigo,descripcion,detalle,empresa,parametro) VALUES(158,'Opciones','Parametro de Notificacion de Deuda Panel Empresa','$emp','0');";
            odbc_exec($connect,$query);
        }
        /////// Valor deuda
        $valor_deuda=0;
        $query="SELECT parametro as valor_deuda FROM web_ge_parametros where empresa='$emp' and codigo='161'";
        $result=odbc_exec($connect, $query);
        if (odbc_num_rows($result)==0) {
            $query="INSERT INTO web_ge_parametros (codigo,descripcion,detalle,empresa,parametro) VALUES(161,'Opciones','Saldo Pendiente de deuda Panel Empresas','$emp',0);";
            odbc_exec($connect,$query);
        }
        /////// Notificacion deuda
        $notificacion_deuda='';
        $query="SELECT parametro as notificacion_deuda FROM web_ge_parametros where empresa='$emp' and codigo='172'";
        $result=odbc_exec($connect, $query);
        if (odbc_num_rows($result)==0) {
            $query="INSERT INTO web_ge_parametros (codigo,descripcion,detalle,empresa,parametro) VALUES(172,'Opciones','Mensaje para notificacion de cobranza','$emp','".'${valor}'."');";
            odbc_exec($connect,$query);
        }    
    }
    /////Consulta de parametros
    ///// Estado Deuda
    $query="SELECT parametro as estado_deuda FROM web_ge_parametros where empresa='$codigo' and codigo='158'";
    $result=odbc_exec($connect, $query);
    $result=odbc_fetch_array($result);
    $estado_deuda=$result['estado_deuda'];
    
    /////// Valor deuda
    $valor_deuda=0;
    $query="SELECT parametro as valor_deuda FROM web_ge_parametros where empresa='$codigo' and codigo='161'";
    $result=odbc_exec($connect, $query);
    $result=odbc_fetch_array($result);
    $valor_deuda=$result['valor_deuda'];
    /////// Notificacion deuda
    $notificacion_deuda='';
    $query="SELECT parametro as notificacion_deuda FROM web_ge_parametros where empresa='$codigo' and codigo='172'";
    $result=odbc_exec($connect, $query); 
    $result=odbc_fetch_array($result);
    $notificacion_deuda=$result['notificacion_deuda'];
    ///////Fin de consulta de parametros

    ////// Productos
	$query="SELECT count(*) as conteo FROM in_item where empresa='$codigo'";
    $result=odbc_exec($connect, $query);
    $result=odbc_fetch_array($result);
    $items=$result['conteo'];
    //print_r($items);return;

    ///// Proveedores 
	$query="SELECT count(*) as conteo FROM in_proveedor where empresa='$codigo'";
    $result=odbc_exec($connect, $query);
    $result=odbc_fetch_array($result);
    $proveedores=$result['conteo'];

    //// Clientes
    $query="SELECT count(*) as conteo FROM in_cliente where empresa='$codigo'";
    $result=odbc_exec($connect, $query);
    $result=odbc_fetch_array($result);
    $cliente=$result['conteo'];

    //// Ventas
    $query="SELECT count(documento) as conteo FROM in_cabecera where tipo='FC' and empresa='$codigo' and fecha between '$desde' and '$hasta'";
    $result=odbc_exec($connect, $query);
    $result=odbc_fetch_array($result);
    $ventas=$result['conteo'];

    //// Total Ventas
    $sumaventas=0;
    $query="SELECT documento FROM in_cabecera where tipo='FC' and empresa='$codigo' and fecha between '$desde' and '$hasta'";
    //print_r($subquery);return;
    $result=odbc_exec($connect, $query);   
    while ($fila = odbc_fetch_array($result)) {
        $docu=$fila['documento'];
        $venta="SELECT web_valor_factura('$codigo', '$docu', 'FC') valor";
        $total=odbc_exec($connect, $venta);
        $total=floatval(odbc_fetch_object($total)->valor);
        $sumaventas += $total;
    };

    //// Compras
    $query="SELECT count(documento) as conteo FROM in_cabecera where tipo='CP' and empresa='$codigo' and fecha between '$desde' and '$hasta'";
    $result=odbc_exec($connect, $query);
    $result=odbc_fetch_array($result);
    $compras=$result['conteo'];


    //// Total Compras
    $sumacompras=0;
    $query="SELECT documento FROM in_cabecera where tipo='CP' and empresa='$codigo' and fecha between '$desde' and '$hasta'";
    //print_r($subquery);return;
    $result=odbc_exec($connect, $query);   
    while ($fila = odbc_fetch_array($result)) {
        $docu=$fila['documento'];
        $compra="SELECT web_valor_factura('$codigo', '$docu', 'CP') valor";
        $total=odbc_exec($connect, $compra);
        $total=floatval(odbc_fetch_object($total)->valor);
        $sumacompras += $total;
    };

    //// Comprobantes de egreso
    $query="SELECT count(asiento) as conteo,MAX(fecha) as lastfecha FROM co_cabecera where empresa='$codigo' and tipo_documento='CE' and fecha between '$desde' and '$hasta'";
    $result=odbc_exec($connect, $query);
    $result=odbc_fetch_array($result);
    $co_egreso=$result;


    //// Comprobantes de ingreso
    $query="SELECT count(asiento) as conteo,MAX(fecha) as lastfecha FROM co_cabecera where empresa='$codigo' and tipo_documento='CI' and fecha between '$desde' and '$hasta'";
    $result=odbc_exec($connect, $query);
    $result=odbc_fetch_array($result);
    $co_ingreso=$result;

    //// Notas de Debito
    $query="SELECT count(asiento) as conteo,MAX(fecha) as lastfecha FROM co_cabecera where empresa='$codigo' and tipo_documento='ND' and fecha between '$desde' and '$hasta'";
    $result=odbc_exec($connect, $query);
    $result=odbc_fetch_array($result);
    $notas_debito=$result;

    //// Notas de credito
    $query="SELECT count(asiento) as conteo,MAX(fecha) as lastfecha FROM co_cabecera where empresa='$codigo' and tipo_documento='NC' and fecha between '$desde' and '$hasta'";
    $result=odbc_exec($connect, $query);
    $result=odbc_fetch_array($result);
    $notas_credito=$result;

    //// Comprobantes de Diario
    $query="SELECT count(asiento) as conteo,MAX(fecha) as lastfecha FROM co_cabecera where empresa='$codigo' and tipo_documento='CD' and fecha between '$desde' and '$hasta'";
    $result=odbc_exec($connect, $query);
    $result=odbc_fetch_array($result);
    $comp_diarios=$result;

    //// Ingreso Diario
    $query="SELECT isnull(sum(valor),'') as conteo FROM cxc_auxiliar where empresa='$codigo' and tipo='FC' and fechae between '$desde' and '$hasta'";
    $result=odbc_exec($connect, $query);
    $result=odbc_fetch_array($result);
    $ingreso_diario=$result['conteo'];

    ///// Documentos electronicos
    $query="SELECT count(documento) as conteo, MAX(fecha) as fechalast FROM in_cabecera where empresa='$codigo' and ce_autoriza!='' and fecha >= '$desde' and fecha <='$hasta'";
    //print_r($query);return;
    $result=odbc_exec($connect, $query);
    $result=odbc_fetch_array($result);
    $electronicos=$result;

    $query = "SELECT documento,contribuyente,ruc,ciudad,direccion,telefono,factura,
    fmontobase=(CASE WHEN base_cero = fmontobase AND tipo = 'IVA' THEN 0 ELSE 
    CASE WHEN base_cero <> fmontobase AND tipo = 'IVA' THEN fmontobase-base_cero ELSE 
    CASE WHEN base_cero = fmontobase AND tipo = 'NIVA' THEN base_cero ELSE 
    fmontobase END END END)
    ,fcod_retencion,ivalor_imputable,icod_retencion,porcentaje,tipo,fecha=fecha_comp,empresa,asiento,detalle,iporcentaje,valorfactura,tipo_comprobante,codigo_concepto_retencion,tipo_identificacion,
    valor_retenido=ROUND(fmontobase*porcentaje/100,2),
    ivalor_imputable*iporcentaje/100 monto_retenido,
    base_cero,cod_iva,pago,punto,stacion,cod_activo,autorizacionf,fechacaducidad,documentof
    FROM te_retenciones
    WHERE empresa = '$codigo' and fecha_comp >= '$desde' and fecha_comp <= '$hasta'";
    //print_r($query);return;
    $result = odbc_exec($connect, $query);
    $retenciones=0;
    while ($fila = odbc_fetch_array($result))
    {
        $fila = codificar($fila);
        $suma=$fila['valor_retenido']+$fila['monto_retenido'];
        $retenciones+=$suma;
    }      
    //print_r($query);return;
    $comienzo = new DateTime($desde);
    $final = new DateTime($hasta);
    // Necesitamos modificar la fecha final en 1 día para que aparezca en el bucle
    $final = $final->modify('+1 day');

    $intervalo = DateInterval::createFromDateString('1 day');
    $periodo = new DatePeriod($comienzo, $intervalo, $final);

    $ventasgra=array();
    $comprasgra=array();
    $ingresosgra=array();
    foreach ($periodo as $dt) {
        $fecha=$dt->format("Y-m-d\n");
        $query="SELECT documento FROM in_cabecera where tipo='CP' and empresa='$codigo' and fecha='$fecha'";
        $result=odbc_exec($connect, $query); 
        if (odbc_num_rows($result)==0) {
            $total=0;
            $elemento=array('fecha'=>$fecha,'Total'=>$total);
            $comprasgra[]=$elemento;
        } 
        while ($fila = odbc_fetch_array($result)) {
            $docu=$fila['documento'];
            $compra="SELECT web_valor_factura('$codigo', '$docu', 'CP') valor";
            $total=odbc_exec($connect, $compra);
            $total=floatval(odbc_fetch_object($total)->valor);
            $elemento=array('fecha'=>$fecha,'Total'=>$total);
            $comprasgra[]=$elemento;
        };

        $query="SELECT documento FROM in_cabecera where tipo='FC' and empresa='$codigo' and fecha='$fecha'";
        $result=odbc_exec($connect, $query);
        if (odbc_num_rows($result)==0) {
            $total=0;
            $elemento=array('fecha'=>$fecha,'Total'=>$total);
            $ventasgra[]=$elemento;
        }    
        while ($fila = odbc_fetch_array($result)) {
            $docu=$fila['documento'];
            $compra="SELECT web_valor_factura('$codigo', '$docu', 'FC') valor";
            $total=odbc_exec($connect, $compra);
            $total=floatval(odbc_fetch_object($total)->valor);
            $elemento=array('fecha'=>$fecha,'Total'=>$total);
            $ventasgra[]=$elemento;
        };

        $query="SELECT isnull(sum(valor),'') as conteo FROM cxc_auxiliar where empresa='$codigo' and tipo='FC' and fechae ='$fecha'";
        $result=odbc_exec($connect, $query);
        while ($fila = odbc_fetch_array($result)) {
            $suma=$fila['conteo'];
            $elemento=array('fecha'=>$fecha,'Total'=>$suma);
            $ingresosgra[]=$elemento;
        };

    }

    $ventasgra=groupByAndSum($ventasgra,'fecha',['Total']);
    $comprasgra=groupByAndSum($comprasgra,'fecha',['Total']);
    $ingresosgra=groupByAndSum($ingresosgra,'fecha',['Total']);
    if(odbc_error()){
        echo json_encode(array(
            'success'=>false, 
            'msg'=>'Ocurrió un error al obtener datos '.substr(odbc_errormsg($connect),35)
        ));
    
    }
    else{
        echo json_encode(array(
            'success'=>true, 
            'estado_deuda'=>$estado_deuda,
            'valor_deuda'=>$valor_deuda,
            'notificacion_deuda'=>$notificacion_deuda,
            'items'=>$items,
            'proveedores'=>$proveedores,
            'cliente'=>$cliente,
            'ventas'=>$ventas,
            'total_ventas'=>$sumaventas,
            'compras'=>$compras,
            'total_compras'=>$sumacompras,
            'co_egreso'=>$co_egreso,
            'co_ingreso'=>$co_ingreso,
            'notas_debito'=>$notas_debito,
            'notas_credito'=>$notas_credito,
            'comp_diarios'=>$comp_diarios,
            'ingreso_diario'=>$ingreso_diario,
            'ventasgra'=>$ventasgra,
            'comprasgra'=>$comprasgra,
            'ingresosgra'=>$ingresosgra,
            'retenciones'=>$retenciones,
            'electronicos'=>$electronicos
        ));
    }
    odbc_close($connect);
 }
 function detalles(){
    require_once('../../../connectdb.php');
    $c=$_GET['c'];
    $n=$_GET['n'];
    $d=$_GET['d'];
    


    /////// consulta para datos generales (items,proveedores,clientes)
    $query = "SELECT ROUND(c.cantidad,2)as cantidad, n.nombre, n.codigo, n.tipo, g.fecha
        FROM ge_movimiento c, ge_tipo n, ge_cabecera g 
        WHERE c.empresa = '$c' AND n.codigo = c.tipo and '$n'=nombre_empresa and c.cabecera='$d' and g.codigo=c.cabecera group by nombre, cantidad, n.codigo,n.tipo,g.fecha order by n.codigo asc, fecha desc";
    //print_r($query);return;
    $res=odbc_exec($connect, $query);
    if (odbc_num_rows($res) ==0) {
        echo json_encode(array(
        'success'=>false, 
        'msg'=>'No hay empresas para mostrar'.substr(odbc_errormsg($connect),35)
        ));
        odbc_close($connect);
        return;
    }
    $detalle= array();
     while ($fila = odbc_fetch_array($res))
    {
        $fila = codificar($fila);
        $lastfecha=$fila['fecha'];
        $detalle[] = $fila;
    }   
    $final = groupByAndSum($detalle, 'nombre', ['cantidad'] );

    ///////////////
    ///intervalo para obtener datos para el grafico 7 días
    $grafico=array();
    $formato = 'Y-m-d';
    //$fecha = DateTime::createFromFormat($formato, $lastfecha);
    $comienzo = DateTime::createFromFormat($formato, $lastfecha);
    $finalf = DateTime::createFromFormat($formato, $lastfecha);
    $comienzo = $comienzo->modify('-6 days');
    //$finalf = $finalf->modify('-1 days');
    $comienzo = $comienzo->format('Y-m-d');
    $finalf =  $finalf->format('Y-m-d');
    $qgrafico="SELECT ROUND(c.cantidad,2)as cantidad, n.nombre, n.codigo, n.tipo, g.fecha
        FROM ge_movimiento c, ge_tipo n, ge_cabecera g 
        WHERE c.empresa = '$c' AND n.codigo = c.tipo and '$n'=nombre_empresa and g.fecha BETWEEN '$comienzo' and '$finalf' and g.codigo=c.cabecera group by nombre, cantidad, n.codigo,n.tipo,g.fecha order by n.codigo asc, fecha asc";
    //print_r($qgrafico);return;
    $consul= odbc_exec($connect, $qgrafico);
    while ($row = odbc_fetch_array($consul)) {
        $grafico[]=$row;
    }
    ///////////

    ///////// datos para ingreso diario (ultimo disponible)
    $diario=array();
    $in_diario="SELECT top 1 ROUND(c.cantidad,2)as cantidad, n.nombre, n.codigo, n.tipo, g.fecha
        FROM ge_movimiento c, ge_tipo n, ge_cabecera g 
        WHERE c.empresa = '$c' AND n.codigo = c.tipo and '$n'=nombre_empresa and g.codigo=c.cabecera group by nombre, cantidad, n.codigo,n.tipo,g.fecha order by n.codigo desc, fecha desc";
    //print_r($in_diario);return;
    $indiario= odbc_exec($connect, $in_diario);
    while ($ind = odbc_fetch_array($indiario)) {
        $diario[]=$ind;
    }



    if(odbc_error()){
        echo json_encode(array(
            'success'=>false, 
            'msg'=>'Ocurrió un error al obtener empresas '.substr(odbc_errormsg($connect),35)
        ));
    
    }
    else{
        echo json_encode(array(
            'success'=>true, 
            'detalle'=>$final,
            'grafico'=>$grafico,
            'diario'=>$diario,
        ));
    }
    odbc_close($connect);
 }
 function numeros(){
    error_reporting(0);
 	$data = json_decode(file_get_contents("php://input"));
 	$codigo = $data->empresa->codigo;
    $params = $data->params;
    $desde = $data->filtros->desde;
    $hasta = $data->filtros->hasta;
    $host = $params->hostdb;
    $user = $params->userdb;
    $pass = $params->passdb;
    $connect = @odbc_connect($host, $user, $pass);
    if (!$connect){
        echo http_response_code(500);
        die();
    }
    $query="select distinct nombre, '(+593)'+STUFF(telefono,1,1,'') as numero from in_cliente where empresa='$codigo' and telefono like '09%'and telefono regexp '[0-9]{10}'";
    $res=odbc_exec($connect, $query);
    $data=array();
    while($fila=odbc_fetch_array($res)){
        $fila=codificar($fila);
        $data[]=$fila;
    };
    
    $query2="select distinct nombre, '(+593)'+STUFF(telefono,1,1,'') as numero from in_proveedor where empresa='$codigo' and telefono like '09%'and telefono regexp '[0-9]{10}'";
    $res2=odbc_exec($connect, $query2);
    $data2=array();
    while($fila2=odbc_fetch_array($res2)){
        $fila2=codificar($fila2);
        $data2[]=$fila2;
    };
    
    if(odbc_error()){
        echo json_encode(array(
            'success'=>false, 
            'msg'=>'Ocurrió un error al obtener datos '.substr(odbc_errormsg($connect),35)
        ));
    
    }
    else{
        echo json_encode(array(
            'success'=>true, 
            'clientes'=>$data,
            'proveedores'=>$data2,
        ));
    }
    odbc_close($connect);
 }
function grafico(){
    require_once('../../../connectdb.php');
}
?>
