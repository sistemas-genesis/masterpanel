<?php set_time_limit(120);
$comienzo = new DateTime('01-01-2020');
$final = new DateTime('31-01-2020');
// Necesitamos modificar la fecha final en 1 día para que aparezca en el bucle
$final = $final->modify('+1 day');

$intervalo = DateInterval::createFromDateString('1 day');
$periodo = new DatePeriod($comienzo, $intervalo, $final);

foreach ($periodo as $dt) {
    $fecha=$dt->format("Y-m-d\n");
    //print_r($fecha);
    require_once('connectdb.php');
	require_once('controlador/codificar.php');
	$empresas=array();
	$tipos=array();
	$suma=0;
	$query="SELECT * FROM ge_tipo";
		$resul=odbc_exec($connect, $query);
		//$tipos=odbc_fetch_array($res;
		while ($subfila = odbc_fetch_array($resul))
		    {
		       $subfila = codificar($subfila);
		       $tipos[] = $subfila;
		    } 
	odbc_close($connect);
	$query="SELECT * FROM empresa";
		$resul=odbc_exec($connect, $query);
		//$tipos=odbc_fetch_array($res;
		while ($subfila = odbc_fetch_array($resul))
		    {
		       $subfila = codificar($subfila);
		       $empresas[] = $subfila;
		    } 
	odbc_close($connect);
	foreach ($empresas as $o) {
        $h=$o['hostdb'];
		$u=$o['userdb'];
		$p=$o['passdb'];
		$c = @odbc_connect($h, $u, $p); 
		$insquery="INSERT INTO ge_cabecera (empresa,cod_empresa, fecha) values ('{$o['nombre']}','{$o['codigo']}','$fecha')";
		$res1=odbc_exec($connect, $insquery);
		$codigo="SELECT @@identity as id";
		$codigo= odbc_exec($connect, $codigo);
		$codigo=odbc_fetch_array($codigo);
		$code=$codigo['id'];
		//print_r($code);
			foreach ($tipos as $i) {
				switch ($i['codigo']) {
					case 1 :
						$subquery="SELECT count(i.codigo) as conteo, i.empresa, periodo FROM in_item i, ge_empresa e where e.codigo=i.empresa GROUP BY i.empresa,periodo";
						//print_r($subquery);return;
						$result=odbc_exec($c, $subquery);
							while ($subfila2 = odbc_fetch_array($result)) {
								$insquery="INSERT INTO ge_movimiento (empresa, tipo,cantidad,cabecera,nombre_empresa, anio) VALUES ('{$subfila2['empresa']}','{$i['codigo']}','{$subfila2['conteo']}', '$code', '{$o['nombre']}','{$subfila2['periodo']}'  )";
								$res2=odbc_exec($connect, $insquery);
							}
					break;

					case 2 :
						$subquery="SELECT count(i.codigo) as conteo, i.empresa, periodo FROM in_proveedor i, ge_empresa e where e.codigo=i.empresa GROUP BY i.empresa,periodo";
						$result=odbc_exec($c, $subquery);
							while ($subfila2 = odbc_fetch_array($result)) {
								$insquery="INSERT INTO ge_movimiento (empresa, tipo,cantidad,cabecera,nombre_empresa, anio) VALUES ('{$subfila2['empresa']}','{$i['codigo']}','{$subfila2['conteo']}', '$code', '{$o['nombre']}','{$subfila2['periodo']}' )";
								$res=odbc_exec($connect, $insquery);
							}
						break;
					case 3:
						$subquery="SELECT count(i.codigo) as conteo, i.empresa, periodo FROM in_cliente i, ge_empresa e where i.empresa=e.codigo GROUP BY i.empresa, periodo";
						$result=odbc_exec($c, $subquery);
							while ($subfila2 = odbc_fetch_array($result)) {
								$insquery="INSERT INTO ge_movimiento (empresa, tipo,cantidad,cabecera,nombre_empresa, anio) VALUES ('{$subfila2['empresa']}','{$i['codigo']}','{$subfila2['conteo']}', '$code', '{$o['nombre']}','{$subfila2['periodo']}' )";
								$res=odbc_exec($connect, $insquery);
							}
					break;

					case 4:
						$subquery="SELECT count(documento) as conteo, i.empresa, e.periodo FROM in_cabecera i, ge_empresa e where tipo='FC'and i.empresa=e.codigo and fecha = '$fecha' GROUP BY i.empresa, periodo";
						$result=odbc_exec($c, $subquery);
							while ($subfila2 = odbc_fetch_array($result)) {
								$insquery="INSERT INTO ge_movimiento (empresa, tipo,cantidad,cabecera,nombre_empresa, anio) VALUES ('{$subfila2['empresa']}','{$i['codigo']}','{$subfila2['conteo']}', '$code', '{$o['nombre']}','{$subfila2['periodo']}' )";
								$res=odbc_exec($connect, $insquery);
							}
					break;

					case 5:
						$arreglo=array();
						$row="SELECT c.empresa, g.periodo FROM in_cabecera c, ge_empresa g 
						where c.tipo='FC' and c.empresa = g.codigo 
						GROUP BY c.empresa, periodo";
						$fil=odbc_exec($c, $row);
						while ($arreglo = odbc_fetch_array($fil)) {
							$suma=0;
							$subquery="SELECT documento, i.empresa, periodo FROM in_cabecera i, ge_empresa e where tipo='FC' and i.empresa='{$arreglo['empresa']}' and i.empresa=e.codigo and fecha = '$fecha'";
							//print_r($subquery);return;
							$result=odbc_exec($c, $subquery);	
							while ($subfila2 = odbc_fetch_array($result)) {
								$venta="SELECT web_valor_factura('{$subfila2['empresa']}', '{$subfila2['documento']}', 'FC') valor";
								$total=odbc_exec($c, $venta);
								$total=floatval(odbc_fetch_object($total)->valor);
								$suma += $total;
							};
						$insquery="INSERT INTO ge_movimiento (empresa, tipo,cantidad,cabecera,nombre_empresa, anio) VALUES ('{$arreglo['empresa']}','{$i['codigo']}','$suma', '$code', '{$o['nombre']}','{$arreglo['periodo']}' )";
						$res=odbc_exec($connect, $insquery);
						}
						
					break;

					case 6:
						$subquery="SELECT count(documento) as conteo, i.empresa, periodo FROM in_cabecera i, ge_empresa e where tipo='CP' and i.empresa=e.codigo and fecha = '$fecha' GROUP BY i.empresa, periodo";
						$result=odbc_exec($c, $subquery);
							while ($subfila2 = odbc_fetch_array($result)) {
								$insquery="INSERT INTO ge_movimiento (empresa, tipo,cantidad,cabecera,nombre_empresa, anio) VALUES ('{$subfila2['empresa']}','{$i['codigo']}','{$subfila2['conteo']}', '$code', '{$o['nombre']}','{$subfila2['periodo']}' )";
								$res=odbc_exec($connect, $insquery);
							}
					break;

					case 7:
						$arreglo=array();
						$row="SELECT c.empresa, periodo FROM in_cabecera c, ge_empresa g where c.tipo='CP' and c.empresa=g.codigo GROUP BY c.empresa, periodo";
						$fil=odbc_exec($c, $row);
						while ($arreglo = odbc_fetch_array($fil)) {
							$suma=0;
							$subquery="SELECT documento, i.empresa, periodo FROM in_cabecera i, ge_empresa e where tipo='CP' and i.empresa='{$arreglo['empresa']}' and i.empresa=e.codigo AND fecha='$fecha'";
							$result=odbc_exec($c, $subquery);	
							while ($subfila2 = odbc_fetch_array($result)) {
								$venta="CALL web_valor_factura('{$subfila2['documento']}','{$subfila2['empresa']}', 'FC')";
								$total=odbc_exec($c, $venta);
								$suma += $total;
							};
						$insquery="INSERT INTO ge_movimiento (empresa, tipo,cantidad,cabecera,nombre_empresa, anio) VALUES ('{$arreglo['empresa']}','{$i['codigo']}','$suma', '$code', '{$o['nombre']}','{$arreglo['periodo']}' )";
						$res=odbc_exec($connect, $insquery);
						}
					break;

					case 8:
						$subquery="SELECT count(asiento) as conteo, i.empresa, periodo FROM co_cabecera i, ge_empresa e WHERE tipo_documento='CE' and i.empresa=e.codigo and fecha = '$fecha' GROUP BY i.empresa, periodo";
						$result=odbc_exec($c, $subquery);
							while ($subfila2 = odbc_fetch_array($result)) {
								$insquery="INSERT INTO ge_movimiento (empresa, tipo,cantidad,cabecera,nombre_empresa, anio) VALUES ('{$subfila2['empresa']}','{$i['codigo']}','{$subfila2['conteo']}', '$code', '{$o['nombre']}','{$subfila2['periodo']}' )";
								$res=odbc_exec($connect, $insquery);
							}
					break;

					case 9:
						$subquery="SELECT count(asiento) as conteo, i.empresa, periodo FROM co_cabecera i, ge_empresa e where tipo_documento='CI' AND i.empresa=e.codigo and fecha = '$fecha' GROUP BY i.empresa, periodo";
						$result=odbc_exec($c, $subquery);
							while ($subfila2 = odbc_fetch_array($result)) {
								$insquery="INSERT INTO ge_movimiento (empresa, tipo,cantidad,cabecera,nombre_empresa, anio) VALUES ('{$subfila2['empresa']}','{$i['codigo']}','{$subfila2['conteo']}', '$code', '{$o['nombre']}','{$subfila2['periodo']}' )";
								$res=odbc_exec($connect, $insquery);
							}
					break;

					case 10:
						$subquery="SELECT count(asiento) as conteo, i.empresa, periodo FROM co_cabecera i, ge_empresa e where tipo_documento='ND' and i.empresa=e.codigo and fecha = '$fecha' GROUP BY i.empresa, periodo";
						$result=odbc_exec($c, $subquery);
							while ($subfila2 = odbc_fetch_array($result)) {
								$insquery="INSERT INTO ge_movimiento (empresa, tipo,cantidad,cabecera,nombre_empresa, anio) VALUES ('{$subfila2['empresa']}','{$i['codigo']}','{$subfila2['conteo']}', '$code', '{$o['nombre']}','{$subfila2['periodo']}' )";
								$res=odbc_exec($connect, $insquery);
							}
					break;

					case 11:
						$subquery="SELECT count(asiento) as conteo, i.empresa, periodo FROM co_cabecera i, ge_empresa e where tipo_documento='NC' and i.empresa=e.codigo and fecha = '$fecha' GROUP BY i.empresa, periodo";
						$result=odbc_exec($c, $subquery);
							while ($subfila2 = odbc_fetch_array($result)) {
								$insquery="INSERT INTO ge_movimiento (empresa, tipo,cantidad,cabecera,nombre_empresa, anio) VALUES ('{$subfila2['empresa']}','{$i['codigo']}','{$subfila2['conteo']}', '$code', '{$o['nombre']}','{$subfila2['periodo']}' )";
								$res=odbc_exec($connect, $insquery);
							}
					break;

					case 12:
						$subquery="SELECT count(asiento) as conteo, i.empresa, periodo FROM co_cabecera i, ge_empresa e where tipo_documento='CD' and i.empresa=e.codigo and fecha = '$fecha' GROUP BY i.empresa, periodo";
						$result=odbc_exec($c, $subquery);
							while ($subfila2 = odbc_fetch_array($result)) {
								$insquery="INSERT INTO ge_movimiento (empresa, tipo,cantidad,cabecera,nombre_empresa, anio) VALUES ('{$subfila2['empresa']}','{$i['codigo']}','{$subfila2['conteo']}', '$code', '{$o['nombre']}','{$subfila2['periodo']}' )";
								$res=odbc_exec($connect, $insquery);
							}
					break;

					case 13:
						$subquery="SELECT sum(valor) as conteo, i.empresa, periodo FROM cxc_auxiliar i, ge_empresa e where tipo='FC' and i.empresa=e.codigo and fechae='$fecha' GROUP BY i.empresa, periodo";
						$result=odbc_exec($c, $subquery);
							while ($subfila2 = odbc_fetch_array($result)) {
								$insquery="INSERT INTO ge_movimiento (empresa, tipo,cantidad,cabecera,nombre_empresa, anio) VALUES ('{$subfila2['empresa']}','{$i['codigo']}','{$subfila2['conteo']}', '$code', '{$o['nombre']}','{$subfila2['periodo']}' )";
								$res=odbc_exec($connect, $insquery);
							}
					break;

					default:
						
						break;
				}
			}
		odbc_close($c);
    } 
	

odbc_close($connect);
}
 ?>