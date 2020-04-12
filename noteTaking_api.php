<?php
/*
localhost/intranet/apps/noteTaking/noteTaking_api.php?id=0	
http://localhost/intranet/varios/vue/menus/mnu4_02_api.php?id=1
*/

include ('C:\www\apps\notasNew\config_inc.php');
include ('TipoEnlace_class.php');
/*
$grupos = 
	[ "grupos" =>
		[
			["id" =>1, "titulo" => "php"],
			["id" =>2, "titulo" => "Javascript"] 
		]	
	];
*/



$conn=new mysqli(C_HOST,C_USR,C_PWD, C_BD);	
mysqli_query($conn, "SET NAMES utf8");

$tabla = "vs_temas";
$id = $_GET['id'] ?? 0;

$enlace = new TipoEnlace($id, $tabla,$conn);

$arr = $enlace->getElementos();

$datos = [ "datos" => $arr];

$ret = json_encode($datos);

header('Content-Type: application/json');
echo utf8_decode($ret);


/*
$conn=new mysqli(C_HOST,C_USR,C_PWD, C_BD);	
mysqli_query($conn, "SET NAMES utf8");

$tabla = "vs_temas";
$id = $_GET['id'] ?? 0;

$sql = <<<ABC
SELECT id, titulo, ( 
	SELECT count( id ) 
	FROM $tabla AS b 
	WHERE b.id_padre = a.id 
	) as nro 
FROM $tabla a 
WHERE a.id_padre= $id
ORDER BY nro DESC
ABC;


$qry = $conn->query($sql);

if (!$qry) {
	echo "consulta false<br>";
} else {
	if ($qry->num_rows == 0) {
		echo "no hay resultados<br>";
	} else {
		$arr = [];
		while($rs = $qry->fetch_assoc()) { 		
			$arr[] = $rs;
		}	
	}

}

$conn->close();

$grupos = [ "grupos" => $arr];


$resp = json_encode($grupos);

header('Content-Type: application/json');
echo utf8_decode($resp);
*/

