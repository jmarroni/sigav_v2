<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

require_once ("../conection.php");
$data_array = ["result" => "0"];
$target_path = time(). '.jpg';

if (isset($_POST["file"])){




	$id = $_POST["identificador"];
	$imagedata = $_POST['file'];
	//$imagedata = base64_decode($imagedata);
	//$source = imagecreatefromstring($imagedata);
	// remove the part that we don't need from the provided image and decode it
	$data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $imagedata));

	$imageName = $id."_".$target_path;
	// Save the image in a defined path
	file_put_contents($imageName,$data);
	$sql = "DELETE FROM `imagen_producto` WHERE productos_id = $id;";
	if ($conn->query($sql) === TRUE) {
		$data_array['result'] = $id;
	} else {
		$data_array['result'] = "Error";
		echo "Error: " . $sql . "<br>" . $conn->error;
	}
	$data_array['result'] = $id;
	$data_array['image_url'] = 'http://todo-kiosco.sigav.com.ar/upload_articles/'.$imageName;
	//$data_array['image_url'] = 'http://local.sigav.com.ar/upload_articles/'.$imageName;
  	$sql = "INSERT INTO `imagen_producto` VALUES(NULL,'{$data_array['image_url']}',{$id});";
	if ($conn->query($sql) === TRUE) {
		$data_array['result'] = $id;
	} else {
		$data_array['result'] = "Error";
		echo "Error: " . $sql . "<br>" . $conn->error;
	}
	$conn->close();
}

echo json_encode($data_array);
?>