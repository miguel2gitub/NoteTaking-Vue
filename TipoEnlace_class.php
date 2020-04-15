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

	require_once ('c:/www/lib/markdown/Parsedown.php');
	require_once ('c:/www/lib/markdown//ParsedownExtra.php');


class TipoEnlace {

	private $conn;
	private $tabla;
	private $datosId;
	private $datosRuta;
	private $editable = true;						// TODO: deducir o asignar si usr puede editar
													// ver si controlarlo dese vue		
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
			if (is_null($this->datosId['modelo']) && !is_null($rs['modelo'])) {
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

		// TODO: rehacer este codigo es fnns para hacerlo mas legible

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

				$notas[] = [
					"id" 		=> $rs['id'], 
					"titulo" 	=> $rs['titulo'], 
					"nota"		=> $this->minl2br($rs['nota'])
				];

			} else {
				if (!$default) {
					$this->datosId['opcDef'] = $rs['id'];
					$this->datosId['titDef'] = $rs['titulo']; 
					$default = true;
				}
			}	
		}

		//$this->conn->free($qry);			TODO: freresub en mysqli::


		###
		###	Notas sobre el tema elegido
		###

		$txtbuscar = "";											// TODO: incluir

		if ($this->datosId['modelo'] != 0) {

			if ($txtbuscar != "") {									
				$sql = <<<SQL
					SELECT a.*, b.titulo as padre 
					FROM $this->tabla a, $this->tabla b 
					WHERE a.id_padre = b.id 
					AND a.nota LIKE '%$txtbuscar%'
					ORDER BY b.titulo
SQL;
			} else {

				$detalle = $this->datosId['modelo'] == 1 ? true : $this->datosId['detalle'];

				if ($this->datosId['opdef']) {
					$id = $this->datosId['opcDef'];
					if ($this->datosId['modelo']==2) {
						$this->datosId['titulo'] = $this->datosId['titDef'];
					}	
				} else {
					$id = $this->datosId['id'];
				}	

				$sql  = "SELECT * FROM " . $this->tabla ." WHERE id_padre = ".$id;
							
				if ($detalle) {
					$sql .= " OR id = ".$id;
					$orden = "id";
				} else {
					$orden = "titulo";
				}
				
				$sql .= " ORDER BY ".$orden." ASC";		
			}	

			$notas = [];

			$qry = $this->conn->query($sql);
			while($rs = $qry->fetch_assoc()) {
	
				if ($txtbuscar == "") {
		
					if ($detalle) {
						$notas[] = [
							"id" 		=> $rs['id'], 
							"titulo" 	=> $rs['titulo'], 
							"nota"		=> $this->minl2br($rs['nota'])
						];
					} else {
						$notas[] = [
							"id" 		=> $rs['id'], 
							"titulo" 	=> $rs['titulo'], 
						];	
					}
				} else {
					$notas[] = [
						"id" 		=> $rs['id'], 
						"titulo" 	=> $rs['pacre'] . ' - '.$rs['titulo'], 
					];	
				}		
			}	

			// add, no en original ya que se visualizaba al vuelo, 
			// t este lo hace la plantilla con datos recibidos
			if ($this->datosId['modelo'] == 2 && $detalle) {
				$this->datosId['modelo'] = 0;
			} 

			//$this->conn->free($qry);
		}
		
		return compact('opciones','notas');	
	}



	public function __toString() 
	{
		//$ret = "Id: ". $this->id . PHP_EOL;
		$ret = [ "datosId" => $this->datosId, "datosRuta" => $this->datosRuta];
        print "<hr>";
        echo '<pre>';print_r($ret);echo '</pre>';
		print "<hr>";
	}

	private function minl2brOLD($txt) {

		// para que no de error Offset not contained in string en strpos($txt, "[kkcode",$ixf);
		// pasa si txt termina en [kkcode] podria hacerlo con condicional right
		// pero que es mas simple y directo a�adiendo un spacios
		$txt .= " ";
		
		$out = "";
		$ixf = 0;

		$ixp = strpos($txt, "[kkcode",0);

		while ($ixp !== false) {	

			$out .= nl2br(substr($txt,$ixf,$ixp-$ixf-1));

			$ixf = strpos($txt, "]",$ixp);
			$lgj = substr($txt,$ixp+7,$ixf-$ixp-7);

			if ($lgj != "")
				$out .= "\n".'<pre class="brush:'.$lgj.'">';
					
			$ixp = $ixf+1;				
			$ixf = strpos($txt, "[kkcode]",$ixp);
			if ($lgj != "") {
				//$out .= htmlentities(substr($txt,$ixp,$ixf-$ixp));
				//ojo arriba los kkcode que tuvieran por ejempolo � no salian
				$out .= substr($txt,$ixp,$ixf-$ixp);
				$out .= "</pre>\n";
			} else
				$out .= "<br>".substr($txt,$ixp,$ixf-$ixp);

			$ixf = $ixf+9;	
			$ixp = strpos($txt, "[kkcode",$ixf);
		}

		
		$out .= nl2br(substr($txt, $ixf));


	/*
		  "/\[off\](.*?)\[\/off\]/is"	  
	<div onclick="vercapa('????')">Ver Codigo</div><div id='????' style='display:none;'>$1</div>

	*/

		//italic, enfatiz, subrayada
		$a = array(
		  "/\[i\](.*?)\[\/i\]/is",
		  "/\[b\](.*?)\[\/b\]/is",
		  "/\[u\](.*?)\[\/u\]/is",
		  "/\[url\](.*?)\[\/url\]/is"
		);
		
		$b = array(
		  "<i>$1</i>",
		  "<b>$1</b>",
		  "<u>$1</u>",
		  "<a href='$1' target='_blank'>$1</a>"
		);
		
	// 	  "<a class='links' href='$1' target='_blank'>$1</a>"
		
		$out = preg_replace($a, $b, $out);
		
		$cad = "/\[off\](.*?)\[\/off\]/is";
		$c = 0;
		$out = preg_replace_callback($cad,"fnoff",$out);
		
		
		return $out;
	}

	private function minl2br($txt) {

		$txt = str_replace('[kkcode]','```',$txt);

		//italic, enfatiz, subrayada
		$a = array(
		  "/\[kkcode(.*?)\]/is",	
		  "/\[i\](.*?)\[\/i\]/is",
		  "/\[b\](.*?)\[\/b\]/is",
		  "/\[u\](.*?)\[\/u\]/is",
		  "/\[url\](.*?)\[\/url\]/is"
		);
		
		$b = array(
		  "```$1",	
		  "<i>$1</i>",
		  "<b>$1</b>",
		  "<u>$1</u>",
		  "<a href='$1' target='_blank'>$1</a>"
		);
		
	// 	  "<a class='links' href='$1' target='_blank'>$1</a>"
		
		$out = preg_replace($a, $b, $txt);
		
		$cad = "/\[off\](.*?)\[\/off\]/is";
		$c = 0;
		$out = preg_replace_callback($cad,"fnoff",$out);


		$out = $this->markdown($out);				
		
		
		return $out;
	}


	private function markdown($txt) {

	//	$ret = ParsedownExtra::instance()->text($txt[1]);

		$kk = new ParsedownExtra();
		$kk->setBreaksEnabled(true);
		$kk->setSyntaxHighlighter(true);
		$ret = $kk->text($txt);
			
		return $ret;
	}	

}

function fnoff($repe)
{
	global $c;
	$c++;

	//$ret =  "<div onclick=\"vercapa('a$c')\">Ver/ocultar codigo</div><div id='a$c' style='display:none;'>".$repe[1]."</div>";
	$ret = "<a href=\"javascript:vercapa('a$c')\">";
	$ret .=  "<div>Ver/ocultar codigo</div></a><div id='a$c' style='display:none;'>".$repe[1]."</div>";
			
	return $ret;
}

?>