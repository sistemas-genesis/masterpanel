<?php 

trait dd {
	public function dump($data){
		echo '<pre>',var_dump($data),'</pre>';
		die();
	}

	public function codifica($data){
		$fila = [];
		foreach ($data as $key => $value) {
			$fila[$key] = utf8_encode($value);
		}
		return $fila;
	}

	public function trans_fp_ventas($conexion,$empresa,$cliente,$periodo,$desde,$hasta,$documento){
		$query = "SELECT DISTINCT count(xml_forma_pago) AS xco FROM cxc_forma_pago,cxc_auxiliar,in_cabecera,
          in_cliente WHERE in_cabecera.empresa='{$empresa}' 
          AND cxc_auxiliar.empresa=in_cabecera.empresa and in_cliente.empresa=in_cabecera.empresa AND in_cliente.codigo=in_cabecera.pro_cli AND 
          in_cabecera.tipo='FC' AND cxc_auxiliar.tipo=in_cabecera.tipo AND cxc_auxiliar.documento=in_cabecera.documento 
          AND in_cliente.cedula_ruc='{$cliente}' AND in_cabecera.fecha>='{$desde}' AND in_cabecera.fecha<='{$hasta}' 
          AND cxc_forma_pago.empresa=cxc_auxiliar.empresa and cxc_forma_pago.secuencia=cxc_auxiliar.forma_pago 
          AND (in_cabecera.estacion IS NOT NULL AND trim(in_cabecera.estacion)<>'') AND NOT(in_cabecera.referencia IS NULL OR trim(in_cabecera.referencia) = '') AND in_cabecera.stado='V';";
		//$this->dump($query);
		$result_distic =  @odbc_exec($conexion, $query);
		$fp = odbc_fetch_array($result_distic);
		$xco = $fp['xco'];
		
		if ($xco == 0) {
			$query = "SELECT distinct count(formaPag) as kcon FROM tr_mo_forma_pago_venta where empresa = '{$empresa}' and periodo = '{$periodo}' and documento = '{$documento}';";
			$result =  @odbc_exec($conexion, $query);
			$otro = odbc_fetch_array($result);
			$kcon = $otro['kcon'];
			
			if ($kcon == 0) {
				$query = "SELECT parametro as xlpago from ge_parametros where empresa='{$empresa}' and codigo='1309';";
				$temp =  @odbc_exec($conexion, $query);
				$jp = odbc_fetch_array($temp);
				$xlpago = $jp['xlpago'];
				$query = "SELECT xml_forma_pago  as lforma_pago  FROM cxc_forma_pago where empresa='{$empresa}' and secuencia='{$xlpago}';";
				$result =  @odbc_exec($conexion, $query);
				$kp = odbc_fetch_array($result);
				$lforma_pago = $kp['lforma_pago'];
				$query = "INSERT INTO tr_mo_forma_pago_venta(empresa,periodo,formaPag,documento) values('{$empresa}','{$periodo}','{$lforma_pago}','{$documento}');";
				@odbc_exec($conexion, $query);
				// Error 006 Procesar transferencia a ventas
				if (odbc_error()) {
					echo json_encode(array(
						"success" => false,
						"mensaje" => "Se registraron errores intentando procesar transferencia forma pago COD 007"
					));
					odbc_rollback($conexion);
					return;
				}
			}
		}
		return true;
          
	}
	
	public function tranfiere_gastos_sin_rt($empresa,$conexion,$desde, $hasta, $periodo){
		$query = "SELECT asiento,fecha_comp,ruc,sum(base_cero) as montobase_cero,sum(fmontobase) as fmontobase,documento,ivalor_imputable,
		cred_tributario,stacion,punto,factura,autorizacionf,serie,tipo_comprobante,tipo2,autorizacion,
		codigo_concepto_retencion,tipo_identificacion,sum(tarifaniva) as tarifaniva,sum(monto_ice) as monto_ice,sum(montoiva) as monto_iva,
		tipoProv,parteRel,pagoLocExt,paisEfecPago,aplicConvDobTrib,pagExtSujRetNorLeg,formaPag,max(impuesto) as xxiva,tipoRegi  
		FROM te_retenciones WHERE fecha_comp >='{$desde}' and fecha_comp <='{$hasta}'  
		and te_retenciones.tr_anexos is null and (te_retenciones.documentof is null or trim(te_retenciones.documentof)='')
		and not(te_retenciones.factura is null or trim(te_retenciones.factura) = '') and te_retenciones.empresa = '{$empresa}' and tipo='NIVA' 
		GROUP BY asiento,fecha_comp,ruc,documento,ivalor_imputable,cred_tributario,stacion,punto,factura,autorizacionf,serie,tipo_comprobante,
		tipo2,autorizacion,codigo_concepto_retencion,tipo_identificacion,tipoProv,parteRel,pagoLocExt,paisEfecPago,aplicConvDobTrib,
		pagExtSujRetNorLeg,formaPag,tipoRegi;";
		$result_distic =  @odbc_exec($conexion, $query);
		$cursor1 = odbc_fetch_array($result_distic);
		$backup_array = array();
		while ($fila = odbc_fetch_array($cursor1)) {
			$fila = codificar($fila);
			$backup_array[] = $fila;
		}
        for ($ft = 0; $ft < sizeof($backup_array); $ft++) {
			$xdocumento = $backup_array[$ft]['asiento'];
			$xfecha_comp = $backup_array[$ft]['fecha_comp'];
			$xruc = $backup_array[$ft]['ruc'];
			$xfmontobase_cero = $backup_array[$ft]['montobase_cero'];
			$xfmontobase = $backup_array[$ft]['fmontobase'];
			$fdocumento= $backup_array[$ft]['documento'];
			$xivalor_imputable= $backup_array[$ft]['ivalor_imputable'];
			$xcred_tributario= $backup_array[$ft]['cred_tributario'];
			$xestacion= $backup_array[$ft]['stacion'];
			$xpunto= $backup_array[$ft]['punto'];
			$xreferencia= $backup_array[$ft]['factura'];
			$xautorizacion= $backup_array[$ft]['autorizacionf'];
			$xpuntor = $backup_array[$ft]['serie'];
			$ltipo_comprobante = $backup_array[$ft]['tipo_comprobante'];
			$ktipo2 = $backup_array[$ft]['tipo2'];
			$ppautorizacion = $backup_array[$ft]['autorizacion'];
			$pretencion_cero = $backup_array[$ft]['codigo_concepto_retencion'];
			$xxtipo_identificacion = $backup_array[$ft]['tipo_identificacion'];
			$tarifaniva = $backup_array[$ft]['tarifaniva'];
			$montoice = $backup_array[$ft]['monto_ice'];
			$montoiva = $backup_array[$ft]['monto_iva'];
			$ltipoProv = $backup_array[$ft]['tipoProv'];
			$lparteRel = $backup_array[$ft]['parteRel'];
			$xpagoLocExt = $backup_array[$ft]['pagoLocExt'];
			$xpaisEfecPago = $backup_array[$ft]['paisEfecPago'];
			$xaplicConvDobTrib = $backup_array[$ft]['aplicConvDobTrib'];
			$xpagExtSujRetNorLeg = $backup_array[$ft]['pagExtSujRetNorLeg'];
			$xformaPag = $backup_array[$ft]['formaPag'];
			$xxiva = $backup_array[$ft]['xxiva'];
			$tipoRegi = $backup_array[$ft]['tipoRegi'];

			$xsercr = $fdocumento;
			if (is_null($xautorizacion) || empty($xautorizacion) || $xautorizacion == '') {
				echo json_encode(array(
					"success" => false,
					"mensaje" => "Atencion autorizacion es nula en el asiento N $xdocumento"
				));
				odbc_rollback($conexion);
				return false;
			}
			if (is_null($xestacion) || empty($xestacion) || $xestacion == '') {
				echo json_encode(array(
					"success" => false,
					"mensaje" => "Atencion estacion es nula en el asiento N $xdocumento"
				));
				odbc_rollback($conexion);
				return false;
			}
			if (is_null($xpunto) || empty($xpunto) || $xpunto == '') {
				echo json_encode(array(
					"success" => false,
					"mensaje" => "Atencion punto es nula en el asiento N $xdocumento"
				));
				odbc_rollback($conexion);
				return false;
			}
			(is_null($xivalor_imputable) || empty($xivalor_imputable)) ? $xivalor_imputable = 0 : $xivalor_imputable = $xivalor_imputable;
			if (is_null($ppautorizacion) || empty($ppautorizacion)) {
				$ppautorizacion = $this->pautorizacionu;
			}
			if($xxtipo_identificacion != '3'){
				if(strlen($xruc)==13){
					$xtipoproveedor='01';
					$lparteRel='NO';
				}elseif (strlen($xruc)==10) {
					$xtipoproveedor='02';
					$lparteRel='NO';
				}elseif (strlen($xruc)<10) {
					$xtipoproveedor='03';
					$ltipoProv='01';
					$lparteRel='NO';
				}
			}else{
				$xtipoproveedor='03';
				//INICIA ANEXO
				if ($xtipoproveedor=='03'){
					if (is_null($ltipoProv) || empty($ltipoProv)) {
						$ltipoProv='01';
						$lparteRel='NO';
					}
				//FIN ANEXO
				}
				
			}
			(is_null($xfmontobase_cero) || empty($xfmontobase_cero)) ? $xfmontobase_cero = 0 : $xfmontobase_cero = $xfmontobase_cero;
			$xbase_cro = $xfmontobase_cero;
			$xbase_iva = $xfmontobase;
			$xfmontobase = $xfmontobase - $xfmontobase_cero;
			(is_null($xbienes) || empty($xbienes)) ? $xbienes = 0 : $xbienes = $xbienes;
			(is_null($kmonto_ice) || empty($kmonto_ice)) ? $kmonto_ice = 0 : $kmonto_ice = $kmonto_ice;
			(is_null($xservicios) || empty($xservicios)) ? $xservicios = 0 : $xservicios = $xservicios;
			(is_null($xservicios100) || empty($xservicios100)) ? $xservicios100 = 0 : $xservicios100 = $xservicios100;
			(is_null($xbienes10) || empty($xbienes10)) ? $xbienes10 = 0 : $xbienes10 = $xbienes10;
			(is_null($xservicios20) || empty($xservicios20)) ? $xservicios20 = 0 : $xservicios20 = $xservicios20;
			(is_null($xbienes50) || empty($xbienes50)) ? $xbienes50 = 0 : $xbienes50 = $xbienes50;
			$r = $this->f_valida_cedula($xruc);
			if ($r == 1) {
	          echo json_encode(array("success" => false, "mensaje" => "El Ruc Esta  Incorrecto Asiento Nº $xdocumento, ERROR AL TRANSFERIR COMPRAS!"));
	          odbc_rollback($conexion);
	          return false;
	        } elseif ($r == 2) {
	          echo json_encode(array("success" => false, "mensaje" => "La Cedula Esta  Incorrecto Asiento Nº $xdocumento, ERROR AL TRANSFERIR COMPRAS!"));
	          odbc_rollback($conexion);
	          return false;
	        }
	        	if ($ltipo_comprobante == '01'){
	             	$xtipocomprobante='01';
	          }elseif ($ltipo_comprobante == '02'){
	             $xtipocomprobante='02';
	          }elseif ($ltipo_comprobante == '03'){
	             $xtipocomprobante='03';
	          }elseif ($ltipo_comprobante == '04'){
	            $xtipocomprobante='04';
	          }elseif ($ltipo_comprobante == '05'){
	            $xtipocomprobante='05';
	          }elseif ($ltipo_comprobante == '06'){
	            $xtipocomprobante='06';
	          }elseif ($ltipo_comprobante == '07'){
	            $xtipocomprobante='07';
	          }elseif ($ltipo_comprobante == '08'){
	            $xtipocomprobante='08';
	          }elseif ($ltipo_comprobante == '09'){
	            $xtipocomprobante='09';
	          }elseif ($ltipo_comprobante == '10'){
	            $xtipocomprobante='10';
	          }elseif ($ltipo_comprobante == '11'){
	             $xtipocomprobante='11';
	          }elseif ($ltipo_comprobante == '12'){
	            $xtipocomprobante='12';
	          }elseif ($ltipo_comprobante == '13'){
	            $xtipocomprobante='13';
	          }elseif ($ltipo_comprobante == '14'){
	            $xtipocomprobante='14';
	          }elseif ($ltipo_comprobante == '15'){
	            $xtipocomprobante='15';
	          }elseif ($ltipo_comprobante == '16'){
	            $xtipocomprobante='16';
	          }elseif ($ltipo_comprobante == '17'){
	            $xtipocomprobante='17';
	          }elseif ($ltipo_comprobante == '18'){
	            $xtipocomprobante='18';
	          }elseif ($ltipo_comprobante == '19'){
	            $xtipocomprobante='19';
	          }elseif ($ltipo_comprobante == '20'){
	            $xtipocomprobante='20';
	          }elseif ($ltipo_comprobante == '21'){
	            $xtipocomprobante='21';
	          }elseif ($ltipo_comprobante == '22'){
	            $xtipocomprobante='22';
	          }elseif ($ltipo_comprobante == '23'){
	            $xtipocomprobante='23';
	          }elseif ($ltipo_comprobante == '24'){
	            $xtipocomprobante='24';
	          }else{
	            $xtipocomprobante= $ltipo_comprobante;
	          }
	        if ($ltipo_comprobante == '02' && ($xcred_tributario == '01' || $xcred_tributario == '03' || $xcred_tributario == '06') ){
	            echo json_encode(array(
	              "success"=>false,
	              "mensaje"=>"Error en Asiento Nº $xdocumento<br>,El Tipo de Comprobante No Concuerda con el Sustento Ingresado, No Son Compatibles"
	            ));
	            odbc_rollback($conexion); 
	            return false;
          	}
          	if ($ltipo_comprobante == '03' &&  $xtipoproveedor=='01'){
            	echo json_encode(array(
	              "success"=>false,
	                "mensaje"=>"Error en Asiento Nº $xdocumento<br>,Si el Contribuyente Tiene Ruc No Puede Emitir Liquidaciones de Ventas, Solo Facturas"
	              ));
	              odbc_rollback($conexion); 
	              return false;
           	}
           	$query = "SELECT max(cast( documento as decimal)) as zxdocumento from tr_mo_compras where periodo='{$xperiodo}' and empresa='{$empresa}';";
	           $result=@odbc_exec($conexion, $query);
	            if(odbc_num_rows($result)>0){
	              $result = odbc_fetch_array($result);
	              $zxdocumento = $result['zxdocumento']+ 1;
	            }else{
	               $zxdocumento=0;
	            }
	        $query = "SELECT monto_ice as kmonto_ice from te_retenciones where  asiento='{$xdocumento}' and empresa='{$empresa}' and monto_ice is not null and te_retenciones.fecha_comp >= '{$desde}' and te_retenciones.fecha_comp <= '{$hasta}';";
            $result=@odbc_exec($conexion, $query);
            if(odbc_num_rows($result)==0){
              	$result = odbc_fetch_array($result);
              	$kmonto_ice = $result['kmonto_ice']+ 1;
            }else{
             	$kmonto_ice=0;
            }

            (is_null($xpagoLocExt) || empty($xpagoLocExt)) ? $xpagoLocExt = '01' : $xpagoLocExt = $xpagoLocExt;
            (is_null($xpaisEfecPago) || empty($xpaisEfecPago)) ? $xpaisEfecPago = 'NA' : $xpaisEfecPago = $xpaisEfecPago;
            (is_null($xaplicConvDobTrib) || empty($xaplicConvDobTrib)) ? $xaplicConvDobTrib = 'NA' : $xaplicConvDobTrib = $xaplicConvDobTrib;
            (is_null($xpagExtSujRetNorLeg) || empty($xpagExtSujRetNorLeg)) ? $xpagExtSujRetNorLeg = 'NA' : $xpagExtSujRetNorLeg = $xpagExtSujRetNorLeg;

            $query = "INSERT INTO tr_mo_compras(codsustento,tpldprov,tipocomprobante,idprov,fecharegistro,establecimiento,puntoemision,secuencial,fechaemision,autorizacion,baseimponible,baseimpgrav,porcentajeiva,porretbienes,valorretbienes,porretservicios,valorretservicios,empresa,periodo,documento,valoriva100,ice,base,establecimientor,puntoemisionr,secuencialr,autorizacionr,fechar,porRetServicios100,documentotr,stado,pagolocext,paisefecpago,aplicconvdobtrib,pagextsujretnorleg,tipoProv,baseImpExe,valRetBien10,valRetServ20,baseimpexereemb,totbasesimpreemb,porRetBienes10,porRetServicios20,parteRel,valretserv50,porRetServicios50,tipoRegi)
			VALUES ($xcred_tributario,$xtipoproveedor,$xtipocomprobante,$xruc,$xfecha_comp,$xestacion,$xpunto,$xreferencia,$xfecha_comp,$xautorizacion,$xbase_cro,$xbase_iva,$xxiva,$this->pbine,$xbienes,$this->pservicio,$xservicios,$empresa,$xperiodo,$zxdocumento,$xservicios100,0,$tarifaniva,'999','999','999999999',$ppautorizacion,$xfecha_comp,$this->piva100,$xdocumento,'C','01','NA','NA','NA',$ltipoProv,0,$xbienes10,$xservicios20,0,0,$this->pbine10,$this->pservicio20,
				$lparteRel,$xbienes50,$this->pbine50,$tipoRegi);";
			@odbc_exec($conexion, $query);
            if(odbc_error()){
              echo json_encode(array(
                "success"=>false,
                "mensaje"=>"Error al trasnferir gastos."
              ));
              odbc_rollback($conexion); 
              return false;
            }

            $lcalc_iva=$xbase_iva * ( dec($xxiva)   / 100);
			$xtotalc = $xbase_cro+ $xbase_iva+$kmonto_ice+$xbase_cro;

			if($ltipo_comprobante != '04'){
				if($xtotalc > 1000){
					if(is_null($xformaPag) || $xformaPag == ''){
						$query = "SELECT sum(valor) as kval from cxc_auxiliar where  documento='{$xdocumento}' and tipo='CP' and empresa='{$empresa}';";
						$result_temp =  @odbc_exec($conexion, $query);
						$valor = odbc_fetch_array($result_temp);
						$kval = $valor['kval'];
						(is_null($kval) || empty($kval)) ? $kval = 0 : $kval = $kval;
						if($kval == 0){
							$query = "INSERT INTO tr_mo_forma_pago_compra(empresa,periodo,documento,formapag) VALUES ('{$empresa}','{$periodo}','{$zxdocumento}','{$pforma_pago1}');";
							@odbc_exec($conexion, $query);
				            if(odbc_error()){
				              echo json_encode(array(
				                "success"=>false,
				                "mensaje"=>"Error al trasnferir gastos."
				              ));
				              odbc_rollback($conexion); 
				              return false;
				            }
						}else{
							$query = "INSERT INTO tr_mo_forma_pago_compra(empresa,periodo,documento,formapag) VALUES ('{$empresa}','{$periodo}','{$zxdocumento}','{$pforma_pago1}');";
							@odbc_exec($conexion, $query);
				            if(odbc_error()){
				              echo json_encode(array(
				                "success"=>false,
				                "mensaje"=>"Error al trasnferir gastos."
				              ));
				              odbc_rollback($conexion); 
				              return false;
				            }
						}
					}else{
						$query = "INSERT INTO tr_mo_forma_pago_compra(empresa,periodo,documento,formapag) VALUES ('{$empresa}','{$periodo}','{$zxdocumento}','{$xformaPag}');";
						@odbc_exec($conexion, $query);
				            if(odbc_error()){
				              echo json_encode(array(
				                "success"=>false,
				                "mensaje"=>"Error al trasnferir gastos."
				              ));
				              odbc_rollback($conexion); 
				              return false;
				            }
					}
				}
			}
			if($ltipo_comprobante != '41'){
				if($ltipo_comprobante != '04'){
					$xfmontobase2 = $xfmontobase + $tarifaniv;
					$query = "SELECT porcentaje as xportr from tr_ma_conceptos_retencion_air where codigo='{$pretencion_cero}';";
					$temp =  @odbc_exec($conexion, $query);
					$valor1 = odbc_fetch_array($temp);
					$xportr = $valor1['xportr'];
					$query = "INSERT INTO tr_mo_air(empresa,periodo,codRetAir,baseImpAir,porcentajeAir,documento) VALUES('{$empresa}','{$periodo}','{$pretencion_cero}','{$xfmontobase2}','{$xportr}','{$zxdocumento}');";
					@odbc_exec($conexion, $query);
				    if(odbc_error()){
				        echo json_encode(array(
				            "success"=>false,
				            "mensaje"=>"Error al trasnferir gastos."
				        ));
				        odbc_rollback($conexion); 
				        return false;
				    }

				}
			}else{
				$query = "INSERT INTO tr_mo_rembolsos_compra(empresa,periodo,documento,tpIdProvReemb,idProvReemb,tipoComprobanteReemb,establecimientoReemb,puntoEmisionReemb,secuencialReemb,fechaEmision,autorizacionReemb,baseImponibleReemb,baseImpGravReemb,baseNoGraIvaReemb,montoIceReemb,montoIvaRemb,baseImpExeReemb)
				values('{$empresa}','{$periodo}','{$zxdocumento}','{$xtipoproveedor}','{$xruc}','{$xtipocomprobante}','{$xestacion}','{$xpunto}','{$xreferencia}','{$xfecha_comp}','{$xautorizacion}','{$xfmontobase_cero}','{$xfmontobase}','{$tarifaniva}','{$montoice}','{$montoiva}',0);";
					@odbc_exec($conexion, $query);
				    if(odbc_error()){
				        echo json_encode(array(
				            "success"=>false,
				            "mensaje"=>"Error al trasnferir gastos."
				        ));
				        odbc_rollback($conexion); 
				        return false;
				    }
			}
			$query = "UPDATE te_retenciones SET tr_anexos='S' where empresa='{$empresa}' and asiento='{$xdocumento}' 
			and documento='{$fdocumento}' and te_retenciones.fecha_comp >= '{$desde}' and te_retenciones.fecha_comp <= '{$hasta}';";
			@odbc_exec($conexion, $query);
				if(odbc_error()){
				    echo json_encode(array(
				        "success"=>false,
				        "mensaje"=>"Error al trasnferir gastos."
				    ));
				    odbc_rollback($conexion); 
				    return false;
				 }
		}
		return true;
	}
	
}

function stado($categoria, $stado){
	switch ($categoria) {
		case 'factura_venta':
			switch ($stado) {
				case 'V':
					return 'Factura de Venta';
					break;
				case 'N':
					return 'Nota de Venta';
					break;
				case 'E':
					return 'Nota de Entrega';
					break;
				case 'A':
					return 'Venta Pago Anticipado';
					break;
			}
			break;
	}
}
function codificar($data)
{
	
	$fila = [];
	if (!$data)
		return $fila;
	foreach ($data as $key => $value) {
		$fila  [$key] = utf8_encode($value);
	}
	return $fila;
}

function NuevoCodigo($conexion, $tabla)
{
	$query ="SELECT count(*),max(cast(codigo as integer)) from ".$tabla." where ISNUMERIC(RIGHT( codigo, 1 )) = 1 AND ISNUMERIC(RIGHT( codigo, 2 )) = 1";
	$result = odbc_exec($conexion, $query);
	while(odbc_fetch_row($result))
	{
		if(odbc_result($result, 1)>0)
	    	return odbc_result($result, 2)+1;
		else
	 		return 1;
	}
}	

function NuevoCodigo1($conexion, $tabla)
{
	$query ="SELECT count(*),max(cast(codigo as integer)) from ".$tabla." where ISNUMERIC(RIGHT( codigo, 1 )) = 1 AND ISNUMERIC(RIGHT( codigo, 2 )) = 1";
	$result = odbc_exec($conexion, $query);
	while(odbc_fetch_row($result))
	{
		if(odbc_result($result, 1)>0)
	    	return odbc_result($result, 2)+1;
		else
	 		return 0;
	}
}

function NuevoCodigo2($conexion, $tabla, $campo)
{
	$query ="SELECT count(*),max(cast({$campo} as integer)) from ".$tabla." where ISNUMERIC(RIGHT( {$campo}, 1 )) = 1 AND ISNUMERIC(RIGHT( {$campo}, 2 )) = 1";
	$result = odbc_exec($conexion, $query);
	while(odbc_fetch_row($result))
	{
		if(odbc_result($result, 1)>0)
	    	return odbc_result($result, 2)+1;
		else
	 		return 1;
	}
} 

function NuevoCodigo3($conexion, $tabla, $campo, $tipo)
{
	$query ="SELECT count(*),max(cast({$campo} as integer)) from ".$tabla." where ISNUMERIC(RIGHT( {$campo}, 1 )) = 1 AND ISNUMERIC(RIGHT( {$campo}, 2 )) = 1 AND tipo = '{$tipo}'";
	$result = odbc_exec($conexion, $query);
	while(odbc_fetch_row($result))
	{
		if(odbc_result($result, 1)>0)
	    	return odbc_result($result, 2)+1;
		else
	 		return 1;
	}
} 

function Human($data){
	echo '<pre>',var_dump($data),'</pre>';
}

function NuevoCodigoDecimal($conexion, $tabla, $campo,  $tipoName, $tipo, $empresa)
{	
	$extra = (is_null($tipo) || empty($tipo)) ? "" : "AND ".$tipoName."='".$tipo."'";
	$query ="SELECT COUNT(*) c , MAX(CAST($campo AS DECIMAL)) m FROM $tabla WHERE empresa='$empresa' $extra";
	$result = odbc_exec($conexion, $query);
	return (odbc_result($result, 1)>0) ? odbc_result($result, 2)+1 : 1;
} 

function getParam($data, $val){
	for ($i=0; $i < sizeof($data); $i++) { 
		if($data[$i]->codigo == $val){
			if ($data[$i]->parametro == '')
				return false;
			else
				return $data[$i];
		}
	}
	return false;
}


function ordenafecha ($fecha) {
  $fecha = substr($fecha, 0, 10);
  $numeroDia = intval(date('d', strtotime($fecha)));
  $mes = date('F', strtotime($fecha));
  $anio = date('Y', strtotime($fecha));
  $meses_ES = array("Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre");
  $meses_EN = array("January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December");
  $nombreMes = str_replace($meses_EN, $meses_ES, $mes);
  return $numeroDia." de ".$nombreMes." del ".$anio;
}

function llenar_ceros($valor,$limite){
    if(is_null ($valor)||empty($valor)) $valor = "0";
    $valor = (string) $valor;
    $cadena = $valor;
	for($i=0;$i<$limite - strlen ($valor);$i++)
		$cadena =  '0' . $cadena;
    return $cadena;
} 

function getParams ($connect, $empresa, $params){
	$query ="SELECT codigo, parametro FROM ge_parametros WHERE empresa='$empresa' AND '$params' LIKE '%,'+codigo+',%'";
	$res = odbc_exec($connect, $query);
	$data = array();
	while($fila = odbc_fetch_array($res)){
		$fila = codificar($fila);
		$data[$fila['codigo']] = $fila;
	}
	return $data;
}

function isToolbarDefault ($codigo) {
	$codigo = ','.$codigo.',';
	$default = ',2,14,15,20,22,210,213,219,303,309,310,314,507';
	return strrpos($default, $codigo) || false;
}

function nuevoCodigoEmpresa($conexion)
{
	$query ="SELECT COUNT(*) c , MAX(CAST(codigo AS DECIMAL)) m FROM ge_empresa";
	$result = odbc_exec($conexion, $query);
	$res = (odbc_result($result, 1)>0) ? odbc_result($result, 2)+1 : 1;
	return llenar_ceros($res, 3);
} 

function normalizeChars($s) {
    $replace = array(
        'ъ'=>'-', 'Ь'=>'-', 'Ъ'=>'-', 'ь'=>'-',
        'Ă'=>'A', 'Ą'=>'A', 'À'=>'A', 'Ã'=>'A', 'Á'=>'A', 'Æ'=>'A', 'Â'=>'A', 'Å'=>'A', 'Ä'=>'Ae',
        'Þ'=>'B',
        'Ć'=>'C', 'ץ'=>'C', 'Ç'=>'C',
        'È'=>'E', 'Ę'=>'E', 'É'=>'E', 'Ë'=>'E', 'Ê'=>'E',
        'Ğ'=>'G',
        'İ'=>'I', 'Ï'=>'I', 'Î'=>'I', 'Í'=>'I', 'Ì'=>'I',
        'Ł'=>'L',
        'Ñ'=>'N', 'Ń'=>'N',
        'Ø'=>'O', 'Ó'=>'O', 'Ò'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'Oe',
        'Ş'=>'S', 'Ś'=>'S', 'Ș'=>'S', 'Š'=>'S',
        'Ț'=>'T',
        'Ù'=>'U', 'Û'=>'U', 'Ú'=>'U', 'Ü'=>'Ue',
        'Ý'=>'Y',
        'Ź'=>'Z', 'Ž'=>'Z', 'Ż'=>'Z',
        'â'=>'a', 'ǎ'=>'a', 'ą'=>'a', 'á'=>'a', 'ă'=>'a', 'ã'=>'a', 'Ǎ'=>'a', 'а'=>'a', 'А'=>'a', 'å'=>'a', 'à'=>'a', 'א'=>'a', 'Ǻ'=>'a', 'Ā'=>'a', 'ǻ'=>'a', 'ā'=>'a', 'ä'=>'ae', 'æ'=>'ae', 'Ǽ'=>'ae', 'ǽ'=>'ae',
        'б'=>'b', 'ב'=>'b', 'Б'=>'b', 'þ'=>'b',
        'ĉ'=>'c', 'Ĉ'=>'c', 'Ċ'=>'c', 'ć'=>'c', 'ç'=>'c', 'ц'=>'c', 'צ'=>'c', 'ċ'=>'c', 'Ц'=>'c', 'Č'=>'c', 'č'=>'c', 'Ч'=>'ch', 'ч'=>'ch',
        'ד'=>'d', 'ď'=>'d', 'Đ'=>'d', 'Ď'=>'d', 'đ'=>'d', 'д'=>'d', 'Д'=>'D', 'ð'=>'d',
        'є'=>'e', 'ע'=>'e', 'е'=>'e', 'Е'=>'e', 'Ə'=>'e', 'ę'=>'e', 'ĕ'=>'e', 'ē'=>'e', 'Ē'=>'e', 'Ė'=>'e', 'ė'=>'e', 'ě'=>'e', 'Ě'=>'e', 'Є'=>'e', 'Ĕ'=>'e', 'ê'=>'e', 'ə'=>'e', 'è'=>'e', 'ë'=>'e', 'é'=>'e',
        'ф'=>'f', 'ƒ'=>'f', 'Ф'=>'f',
        'ġ'=>'g', 'Ģ'=>'g', 'Ġ'=>'g', 'Ĝ'=>'g', 'Г'=>'g', 'г'=>'g', 'ĝ'=>'g', 'ğ'=>'g', 'ג'=>'g', 'Ґ'=>'g', 'ґ'=>'g', 'ģ'=>'g',
        'ח'=>'h', 'ħ'=>'h', 'Х'=>'h', 'Ħ'=>'h', 'Ĥ'=>'h', 'ĥ'=>'h', 'х'=>'h', 'ה'=>'h',
        'î'=>'i', 'ï'=>'i', 'í'=>'i', 'ì'=>'i', 'į'=>'i', 'ĭ'=>'i', 'ı'=>'i', 'Ĭ'=>'i', 'И'=>'i', 'ĩ'=>'i', 'ǐ'=>'i', 'Ĩ'=>'i', 'Ǐ'=>'i', 'и'=>'i', 'Į'=>'i', 'י'=>'i', 'Ї'=>'i', 'Ī'=>'i', 'І'=>'i', 'ї'=>'i', 'і'=>'i', 'ī'=>'i', 'ĳ'=>'ij', 'Ĳ'=>'ij',
        'й'=>'j', 'Й'=>'j', 'Ĵ'=>'j', 'ĵ'=>'j', 'я'=>'ja', 'Я'=>'ja', 'Э'=>'je', 'э'=>'je', 'ё'=>'jo', 'Ё'=>'jo', 'ю'=>'ju', 'Ю'=>'ju',
        'ĸ'=>'k', 'כ'=>'k', 'Ķ'=>'k', 'К'=>'k', 'к'=>'k', 'ķ'=>'k', 'ך'=>'k',
        'Ŀ'=>'l', 'ŀ'=>'l', 'Л'=>'l', 'ł'=>'l', 'ļ'=>'l', 'ĺ'=>'l', 'Ĺ'=>'l', 'Ļ'=>'l', 'л'=>'l', 'Ľ'=>'l', 'ľ'=>'l', 'ל'=>'l',
        'מ'=>'m', 'М'=>'m', 'ם'=>'m', 'м'=>'m',
        'ñ'=>'n', 'н'=>'n', 'Ņ'=>'n', 'ן'=>'n', 'ŋ'=>'n', 'נ'=>'n', 'Н'=>'n', 'ń'=>'n', 'Ŋ'=>'n', 'ņ'=>'n', 'ŉ'=>'n', 'Ň'=>'n', 'ň'=>'n',
        'о'=>'o', 'О'=>'o', 'ő'=>'o', 'õ'=>'o', 'ô'=>'o', 'Ő'=>'o', 'ŏ'=>'o', 'Ŏ'=>'o', 'Ō'=>'o', 'ō'=>'o', 'ø'=>'o', 'ǿ'=>'o', 'ǒ'=>'o', 'ò'=>'o', 'Ǿ'=>'o', 'Ǒ'=>'o', 'ơ'=>'o', 'ó'=>'o', 'Ơ'=>'o', 'œ'=>'oe', 'Œ'=>'oe', 'ö'=>'oe',
        'פ'=>'p', 'ף'=>'p', 'п'=>'p', 'П'=>'p',
        'ק'=>'q',
        'ŕ'=>'r', 'ř'=>'r', 'Ř'=>'r', 'ŗ'=>'r', 'Ŗ'=>'r', 'ר'=>'r', 'Ŕ'=>'r', 'Р'=>'r', 'р'=>'r',
        'ș'=>'s', 'с'=>'s', 'Ŝ'=>'s', 'š'=>'s', 'ś'=>'s', 'ס'=>'s', 'ş'=>'s', 'С'=>'s', 'ŝ'=>'s', 'Щ'=>'sch', 'щ'=>'sch', 'ш'=>'sh', 'Ш'=>'sh', 'ß'=>'ss',
        'т'=>'t', 'ט'=>'t', 'ŧ'=>'t', 'ת'=>'t', 'ť'=>'t', 'ţ'=>'t', 'Ţ'=>'t', 'Т'=>'t', 'ț'=>'t', 'Ŧ'=>'t', 'Ť'=>'t', '™'=>'tm',
        'ū'=>'u', 'у'=>'u', 'Ũ'=>'u', 'ũ'=>'u', 'Ư'=>'u', 'ư'=>'u', 'Ū'=>'u', 'Ǔ'=>'u', 'ų'=>'u', 'Ų'=>'u', 'ŭ'=>'u', 'Ŭ'=>'u', 'Ů'=>'u', 'ů'=>'u', 'ű'=>'u', 'Ű'=>'u', 'Ǖ'=>'u', 'ǔ'=>'u', 'Ǜ'=>'u', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'У'=>'u', 'ǚ'=>'u', 'ǜ'=>'u', 'Ǚ'=>'u', 'Ǘ'=>'u', 'ǖ'=>'u', 'ǘ'=>'u', 'ü'=>'ue',
        'в'=>'v', 'ו'=>'v', 'В'=>'v',
        'ש'=>'w', 'ŵ'=>'w', 'Ŵ'=>'w',
        'ы'=>'y', 'ŷ'=>'y', 'ý'=>'y', 'ÿ'=>'y', 'Ÿ'=>'y', 'Ŷ'=>'y',
        'Ы'=>'y', 'ž'=>'z', 'З'=>'z', 'з'=>'z', 'ź'=>'z', 'ז'=>'z', 'ż'=>'z', 'ſ'=>'z', 'Ж'=>'zh', 'ж'=>'zh'
    );
    return strtr($s, $replace);
}	

function empty_is_null ($data, $properties){
	$data = json_decode(json_encode($data), true);


	foreach ($properties as $i) {
		$data[$i] = (isset($data[$i])) ? $data[$i] : '';
	}
	return json_decode(json_encode($data));
}

function identificacion2tipo ($identificacion){
	if ($identificacion == '9999999999999') 
		return '07';
    switch (strlen($identificacion)){
        case 10: 
            return '05';
        case 13:
            return '04';
        default: 
            return false;
    }
}


function getIndex ($json, $clave, $valor) {
	$status = 0;
	for ($i=0; $i < sizeof($json); $i++) { 
		if ($json[$i][$clave] == $valor)
			return $i;
		else $status ++;
	}
	if ($status == sizeof($json)) return -1;
}

function groupByAndSum ($data, $key, $sumKeys){

    $keys = array();
    $res = array();

    foreach ($data as $i) {
    	if (array_search($i[$key], $keys) === false)
    		$keys[] = $i[$key];
    }

    foreach ($keys as $index => $i) {
    	$aux = array(
    		'data' => array(),
    		'sums' => array()
    	);
    	$aux[$key] = $i;
    	foreach ($sumKeys as $j) {
	    	$aux['sums'][$j] = 0;
		}
		$res[] = $aux;
    }

    foreach ($data as $i) {
    	$index = getIndex($res, $key, $i[$key]);
    	foreach ($sumKeys as $j) {
	    	$res[$index]['sums'][$j] += floatval($i[$j]);
		}
		$res[$index]['data'][] = $i;
    }
       
    return $res;
	
}

 function groupBy ($data, $key){
    $keys = array();
    $res = array();

    foreach ($data as $i) {
    	if(array_search($i[$key], $keys) === false)
    		$keys[] = $i[$key];
    }

    foreach ($keys as $i) {
		$res[][$i] = array();
    }

    foreach ($data as $i) {
		$res[$i[$key]] = $i;
    }

    return $res;
}


function referencia_facturacion($empresa = NULL, $documento = NULL, $connect = NULL, $e=NULL, $p=NULL, $t=NULL, $s=NULL){
    $empresa = (is_null($empresa)) ? $_GET['empresa'] : $empresa;
    $e = (is_null($e)) ? $_GET['e'] : $e;
    $p = (is_null($p)) ? $_GET['p'] : $p;
    $t = (is_null($t)) ? $_GET['t'] : $t;

    if (!isset($connect))
        require_once('../../../connectdb.php');

    if (isset($_GET['s']))
        $s = " AND stado = '{$_GET['s']}' ";
    elseif (!is_null($s))
        $s = " AND stado = '$s' ";
    else
        $s = "";
    $query = "SELECT IFNULL((SELECT MAX(CAST(referencia AS INT)) FROM in_cabecera WHERE empresa = '{$empresa}' AND punto = '{$p}' AND estacion = '{$e}' $s  AND tipo = '{$t}'), 0, (SELECT MAX(CAST(referencia AS INT)) res FROM in_cabecera WHERE empresa = '{$empresa}' AND punto = '{$p}' AND estacion = '{$e}' AND tipo = '{$t}' $s )) res";

    $res = odbc_fetch_array(odbc_exec($connect, $query))['res'];     
    return $res; 
    odbc_close($connect);
}
?>
