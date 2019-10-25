<?php
if (!isset($_COOKIE["kiosco"])) {
    header('Location: /');
}

require_once ("conection.php");
$sql = "SELECT *
        FROM clientes";
if (isset($_GET["identificador"])) $sql .= " WHERE id = ".intval($_GET["identificador"]);
if (isset($_GET["term"])) $sql .= " WHERE razon_social like '%".$_GET["term"]."%' ";
$sql .= " order by id DESC ";
$resultado = $conn->query($sql) or die($conn->error." --- ".$sql);
$datos = '{"data":"no data"}';
if ($resultado->num_rows > 0) {
    // output data of each row
    if ($resultado->num_rows > 1) {
        $arrResponse = array();
        while ($row = $resultado->fetch_assoc()) {
            $row["value"] = $row["razon_social"]." (".$row["cuit"].")";
            $arrResponse[] = $row;
        }
        echo json_encode($arrResponse);
    }else
        if ($row = $resultado->fetch_assoc()) {
            $row["value"] = $row["razon_social"];
            if (isset($_GET["term"])) echo json_encode(array($row));
            else echo json_encode($row);
        }else{ 
            echo "{}"; 
        }
}else{ 
    echo "{}"; 
}
$conn->close();

exit();
?>