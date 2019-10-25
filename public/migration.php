/*[03:54:20 p. m.][1792 ms]*/ ALTER TABLE `ventas` ADD COLUMN `estado` INT NULL COMMENT '1 - sin facturar; 2 - facturado; 3 - no se habilito factura electronica' AFTER `sucursal_id`; 
/*[02:46:27 p. m.][177 ms]*/ CREATE TABLE `factura`( `id` INT NOT NULL AUTO_INCREMENT, `sucursal_id` INT, `fecha` VARCHAR(20), `usuario_id` INT, `numero` INT, `cae` VARCHAR(50), PRIMARY KEY (`id`) ); 

/*[10:14:31 p. m.][116 ms]*/ ALTER TABLE `ventas` ADD COLUMN `factura_id` INT NULL AFTER `estado`; 
/*[10:21:03 p. m.][69 ms]*/ ALTER TABLE `factura` ADD COLUMN `total` VARCHAR(20) NULL AFTER `cae`; 
/*[10:33:08 p. m.][96 ms]*/ ALTER TABLE `factura` CHANGE `usuario_id` `usuario` VARCHAR(100) NULL; 
/*[04:42:46 p. m.][63 ms]*/ CREATE TABLE `perfil`( `id` INT NOT NULL AUTO_INCREMENT, `nombre` VARCHAR(200), `razon_social` VARCHAR(200), `direccion` VARCHAR(200), `mail` VARCHAR(200), `telefono` VARCHAR(200), `provincia` VARCHAR(200), `localidad` VARCHAR(200), `logo` VARCHAR(200), PRIMARY KEY (`id`) ); 
/*[12:55:54 p. m.][89 ms]*/ ALTER TABLE `factura` ADD COLUMN `pdf` VARCHAR(200) NULL AFTER `total`; 
