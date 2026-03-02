<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

$data = ["result" => 0];
$target_path = time(). '.jpg';

if (isset($_POST["file"])){
	$imagedata = $_POST['file'];
	$imagedata =  str_replace('data:image/jpeg:base64', '', $imagedata);
	$imagedata =  str_replace('data:image/jpg:base64', '', $imagedata);
	$imagedata =  str_replace(' ', '+', $imagedata);
	$imagedata = base64_decode($imagedata);
	$imagen = $_POST["identificador"];
	file_put_contents($target_path, $imagedata);

	$data['result'] = $imagen;
	$data['image_url'] = 'http://mercado-artesanal.com.ar/upload_articles/'.$target_path;

}  

echo json_encode($data);
?>