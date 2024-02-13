<?php
 ini_set('display_errors', 1);
 ini_set('display_startup_errors', 1);
 error_reporting(E_ALL);
/**

Insercion de servicioss relacionados, genera una deuda para el cliente que no abone en termino. y genera el recargo por el no pago del mismo o interes en su caso.

**/

require_once ("../conection.php");

// Traigo los clientes que voy a generar
$sql = "select 
		rsc.id,
		s.costo as monto,
		rsc.fecha,
		s.periodo
		from clientes c
		 inner join relacion_servicio_cliente rsc
		 on c.id = rsc.cliente_id
			inner join servicios s
		    on s.id = rsc.servicios_id
		 WHERE periodo <> 0";

class estado_cuenta {

	public $_id;
	public $_fecha_cuenta;
	public $_estado;
	public $_monto;
	public $_relacion_servicio_cliente_id;
	public $_interes;
	public $_periodo;
	public $_fecha_pago;

	public function __construct($relacion_servicio_cliente,$monto,$fecha){
		
		$this->_id = NULL;
		$this->_fecha_cuenta = $fecha;
		$this->_estado = 0;
		$this->_monto = $monto;
		$this->_relacion_servicio_cliente_id = $relacion_servicio_cliente;
		$this->_interes = 250;
		$this->_periodo = $this->_getMes(date("m"));
		$this->_fecha_pago = NULL;
	}

	private function _getMes($numero){
		switch ($numero) {
			case '1': return "Enero";break;
			case '2': return "Febrero";break;
			case '3': return "Marzo";break;
			case '4': return "Abril";break;
			case '5': return "Mayo";break;
			case '6': return "Junio";break;
			case '7': return "Julio";break;
			case '8': return "Agosto";break;
			case '9': return "Septiembre";break;
			case '10': return "Octubre";break;
			case '11': return "Noviembre";break;
			case '12': return "Diciembre";break;																								
			default:
				# code...
				break;
		}
	}
}

$estados_cuenta = array();
$resultado = $conn->query($sql) or die(mysqli_error($conn)." Q=".$sql);
$fecha = date('Y-m-d');
if ($resultado->num_rows > 0) {
	echo $resultado->num_rows;
    while($row = $resultado->fetch_assoc()) {
    	// Busco la ultima facturacion si existe una menor al periodo, entonces lo facture
		$nuevafecha = strtotime ( '-'.$row["periodo"].' day' , strtotime ( $fecha ) ) ;
		$ultima_factura = date ( 'Y-m-d' , $nuevafecha );

		$query_por_periodo = "SELECT id FROM estados_contables WHERE relacion_servicio_cliente_id = {$row["id"]} AND fecha_cuenta > '{$ultima_factura}' LIMIT 1";
		$resultado_por_periodo = $conn->query($query_por_periodo) or die(mysqli_error($conn)." Q=".$query_por_periodo);
		if ($resultado_por_periodo->num_rows > 0) {
			$estado_cuenta =  new estado_cuenta($row["id"],$row["monto"],$ultima_factura);
			$estados_cuenta[] = $estado_cuenta;
		}
    }
}


foreach ($estados_cuenta as $key => $value) {
	$insercion = "INSERT INTO `estados_contables`
					(`id`,
					`fecha_cuenta`,
					`estado`,
					`monto`,
					`relacion_servicio_cliente_id`,
					`interes`,
					`periodo`,
					`fecha_pago`)
					VALUES
					(NULL,
					'{$value->_fecha_cuenta}',
					'{$value->_estado}',
					'{$value->_monto}',
					'{$value->_relacion_servicio_cliente_id}',
					'250',
					'".date("m")."',
					'{$value->_fecha_pago}');";
	$conn->query($insercion) or die(mysqli_error($conn)." Q=".$insercion);
}
exit();

?>