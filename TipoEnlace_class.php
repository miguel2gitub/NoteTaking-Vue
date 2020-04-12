<?php
/*
Esta clase se encarga de obtener los datos y deducir como se muestran
segun primer padre que tenga un modelo de pagina definido

----------------------- o -----------------------
tipos de pagina

- null 
modelpag = primero en arbol arriba que no sea nulo


- tipo 3
devuelve opciones menu y notas
cada nota vuelve a abrir nuevo menu con opciones

- tipo 2
1ra vez devuelve opciones y notas
despues solo notas

- tipo 1
1ra vez devuelve opciones y notas
devuelve notas y su detalle

- tipo 0
(no va bien era para privado, parecido o = a 1)
pdte profundizar

----------------------- o -----------------------

json_encode($data, JSON_UNESCAPED_SLASHES);

https://es.vuejs.org/v2/cookbook/debugging-in-vscode.html

https://es.vuejs.org/v2/guide/computed.html
*/

class TipoEnlace {

	private $conn;
	private $tabla;
	private $datosId;
	private $datosRuta;

	function __construct($id, $tabla, $conn) 
	{
		$this->conn = $conn;
		$this->tabla = $tabla;

		if ($id == 0) {
			$this->datosId['id'] = 0;
			$this->datosId['modelo'] = 0;
			$this->datosId['titulo'] = 'Inicio';
			$this->datosId['idmnu'] = 0;
			$this->datosRuta = [];
		} else {	
			$this->buildData($id);
		}	
	}

	private function buildData($id) 
	{
		$this->datosId = $this->getDatosId($id);
		$this->datosRuta = $this->getDatosRuta();
	}

	private function getDatosId($id) 
	{
		$sql = <<<SQL
			SELECT id, titulo, id_padre, modelo, 
			(CASE WHEN nota IS NOT NULL AND modelo IS NULL THEN true ELSE false END) AS detalle 
			FROM $this->tabla WHERE id = $id
SQL;
		$rs = null;
		$qry = $this->conn->query($sql);
		if ($qry) {
			if ($qry->num_rows > 0) {
				$rs = $qry->fetch_assoc();

				if (is_null($rs['modelo'])) {
					$rs['idmnu'] = $rs['id_padre'];	
					$rs['opdef'] = false;				
				} else {
					$rs['idmnu'] = $id;
					$rs['opdef'] = true;
				}
				$rs['titpd'] = "";		// titulo del padre
			}
		}
		$qry->close(); //$this->conn->free($qry);

		return $rs;
	}

	private function getDatosRuta() 
	{
		$ret = [];	
		$idPadre = $this->datosId['id_padre'];

		while ($idPadre != 0) {
			$sql = "SELECT id, titulo, id_padre, modelo FROM ".$this->tabla." WHERE id = ".$idPadre;
			$qry = $this->conn->query($sql);
			$rs = $qry->fetch_assoc();			
			$ret[] = ["id" => $rs['id'], "titulo" => $rs['titulo']];

			// asignar modelo pagina en caso de que no lo tenga aun	
			if (is_null($this->datosId['modelo'] && !is_null($rs['modelo']))) {
				$this->datosId['modelo'] = $rs['modelo'];
				if ($rs['modelo'] == 2) {
					$this->datosId['idmnu'] = $rs['id'];
				}
			}

			// asignar el titulo del padre
			if ($this->datosId['titpd'] == "") {
				$this->datosId['titpd'] = $rs['titulo'];
			}

			$idPadre = $rs['id_padre'];	
		}
		$ret[] = ["id" => 0, "titulo" => "Inicio"];

		return array_reverse($ret);
	}


	public function getElementos() 
	{
		/*
		aqui habria que enviar el modelo a mostrar asi como las opciones y/o notas
		segun los datos recogidos hasta ahora

		*/
		$opcMenu = $this->getOpciones();

		//$ret = ["nota" => $this->datosId, "ruta" => $this->datosRuta], compact('opcMenu');
		//$ret = ["nota" => $this->datosId, "ruta" => $this->datosRuta, compact('opcMenu')];
		//$ret = compact($this->datosId, $this->datosRuta, $opcMenu);

		$ret = [
			"nota" => $this->datosId,
			"ruta" => $this->datosRuta,
			"opciones" => $opcMenu['opciones'],
			"notas"	=> $opcMenu['notas']
		];

		return $ret;
	}


	private function getOpciones() 
	{
		if ($this->datosId['modelo'] != 2) {
			$sql = <<<SQL
				SELECT id, titulo, comentarios, nota 
				FROM $this->tabla a 
				WHERE a.id_padre = {$this->datosId['idmnu']} AND acceso !='Privado' 
				ORDER BY titulo
SQL;
		} else {
			$sql = <<<SQL
				SELECT id, titulo, ( 
					SELECT count( id ) 
					FROM $this->tabla AS b 
					WHERE b.id_padre = a.id 
					) as nro 
				FROM  $this->tabla a 
				WHERE a.id_padre = {$this->datosId['idmnu']}
				ORDER BY nro DESC
SQL;
		}		

		$opciones = [];
		$notas = [];
		$default = false;

		$qry = $this->conn->query($sql);
		// $filas = $qry->num_rows;
		while($rs = $qry->fetch_assoc()) {
			$opciones[] = ["id"=>$rs['id'], "titulo"=>$rs['titulo'], "nro"=>$rs['nro']];

			if ($this->datosId['modelo'] == 0) {

				$notas[] = ["id"=>$rs['id'], "titulo"=>$rs['titulo'], "nota"=>$rs['nota']];

			} else {
				if (!$default == 0) {
					$this->datosId['opcDef'] = $rs['id'];
					$this->datosId['titDef'] = $rs['titulo']; 
					$default = true;
				}
			}	
		}

		return compact('opciones','notas');	
	}

	public function __toString() {

		//$ret = "Id: ". $this->id . PHP_EOL;
		$ret = [ "datosId" => $this->datosId, "datosRuta" => $this->datosRuta];
        print "<hr>";
        echo '<pre>';print_r($ret);echo '</pre>';
		print "<hr>";
	}
}

?>