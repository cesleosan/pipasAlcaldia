CREATE TABLE IF NOT EXISTS usuario (
 id_usuario INT PRIMARY KEY AUTO_INCREMENT, nombre VARCHAR(100) NOT NULL, usuario VARCHAR(60) NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL, perfil INT NOT NULL CHECK (perfil IN (1,11)), activo TINYINT NOT NULL DEFAULT 1,
 creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS login_intento (
 clave CHAR(64) PRIMARY KEY, intentos INT NOT NULL DEFAULT 0, ventana DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS tipo_padron (id_tipo_padron INT PRIMARY KEY AUTO_INCREMENT, nombre VARCHAR(120) NOT NULL UNIQUE, activo TINYINT NOT NULL DEFAULT 1) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS caja (id_caja INT PRIMARY KEY AUTO_INCREMENT, nombre VARCHAR(120) NOT NULL UNIQUE, activo TINYINT NOT NULL DEFAULT 1) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS colonia (id_colonia INT PRIMARY KEY AUTO_INCREMENT, nombre VARCHAR(120) NOT NULL UNIQUE, activo TINYINT NOT NULL DEFAULT 1) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS autorizo (id_autorizo INT PRIMARY KEY AUTO_INCREMENT, nombre VARCHAR(120) NOT NULL UNIQUE, activo TINYINT NOT NULL DEFAULT 1) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS tipo_bloqueo (id_tipo_bloqueo INT PRIMARY KEY, nombre VARCHAR(100) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT IGNORE INTO tipo_bloqueo VALUES (1,'No entregó hoja rosa'),(2,'Compra con acta');
CREATE TABLE IF NOT EXISTS padron (
 id_padron INT PRIMARY KEY AUTO_INCREMENT, tipo_padron_id_tipo_padron INT NOT NULL,
 folio VARCHAR(11) UNIQUE, num_exp VARCHAR(11), paterno VARCHAR(45) NOT NULL, materno VARCHAR(45) NOT NULL DEFAULT '',
 nombre VARCHAR(45) NOT NULL, nombre_completo VARCHAR(190) NOT NULL, cotitular VARCHAR(300), curp CHAR(18) NOT NULL UNIQUE,
 telefono CHAR(10), email VARCHAR(100), fecha_alta DATE NOT NULL, id_caja INT NOT NULL,
 status_padron ENUM('0','1','2','3','4','5') NOT NULL DEFAULT '1', version INT NOT NULL DEFAULT 1,
 creado_por INT NOT NULL, actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (tipo_padron_id_tipo_padron) REFERENCES tipo_padron(id_tipo_padron), FOREIGN KEY (id_caja) REFERENCES caja(id_caja),
 FOREIGN KEY (creado_por) REFERENCES usuario(id_usuario), INDEX idx_nombre(nombre_completo), INDEX idx_status(status_padron)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS domicilio (
 id_domicilio INT PRIMARY KEY AUTO_INCREMENT, padron_id_padron INT NOT NULL UNIQUE, colonia_id_colonia INT NOT NULL,
 cp CHAR(5), calle VARCHAR(100) NOT NULL, num_ext_mza VARCHAR(50) NOT NULL, num_int_lote VARCHAR(50) NOT NULL DEFAULT '',
 num_familias INT NOT NULL CHECK(num_familias>0), num_habitantes INT NOT NULL CHECK(num_habitantes>0),
 observacion VARCHAR(300) NOT NULL DEFAULT '', croquis VARCHAR(300) NOT NULL DEFAULT '', tiempo_residencia VARCHAR(20) NOT NULL DEFAULT '',
 latitud DECIMAL(10,7), longitud DECIMAL(10,7), direccion_hash CHAR(64) NOT NULL UNIQUE,
 status_domicilio ENUM('0','1','2') NOT NULL DEFAULT '1',
 FOREIGN KEY (padron_id_padron) REFERENCES padron(id_padron), FOREIGN KEY (colonia_id_colonia) REFERENCES colonia(id_colonia)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS dotacion (
 id_dotacion INT PRIMARY KEY AUTO_INCREMENT, domicilio_id_domicilio INT NOT NULL, dotacion INT NOT NULL CHECK(dotacion BETWEEN 1 AND 10),
 tamano_cisterna INT NOT NULL CHECK(tamano_cisterna>0), tarifa DECIMAL(9,2) NOT NULL CHECK(tarifa>=0),
 status_dotacion ENUM('0','1','2') NOT NULL DEFAULT '1', creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 domicilio_vigente INT GENERATED ALWAYS AS (CASE WHEN status_dotacion='1' THEN domicilio_id_domicilio ELSE NULL END) STORED,
 UNIQUE KEY uq_dotacion_vigente(domicilio_vigente), FOREIGN KEY (domicilio_id_domicilio) REFERENCES domicilio(id_domicilio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS padron_bloqueado (
 id_padron_bloqueado INT PRIMARY KEY AUTO_INCREMENT, padron_id_padron INT NOT NULL, tipo_bloqueo_id_tipo_bloqueo INT NOT NULL,
 fecha_padron_bloqueado DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, fecha_padron_desbloqueo DATETIME,
 desc_padron_bloqueado VARCHAR(500) NOT NULL, justificacion_reactivacion VARCHAR(500), id_usuario INT NOT NULL, reactivado_por INT,
 status_padron_bloqueo ENUM('0','1','2') NOT NULL DEFAULT '1',
 padron_vigente INT GENERATED ALWAYS AS (CASE WHEN status_padron_bloqueo='1' THEN padron_id_padron ELSE NULL END) STORED,
 UNIQUE KEY uq_bloqueo_vigente(padron_vigente), FOREIGN KEY (padron_id_padron) REFERENCES padron(id_padron),
 FOREIGN KEY (tipo_bloqueo_id_tipo_bloqueo) REFERENCES tipo_bloqueo(id_tipo_bloqueo), FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario),
 FOREIGN KEY (reactivado_por) REFERENCES usuario(id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS baja_dotacion (
 id_baja_dotacion INT PRIMARY KEY AUTO_INCREMENT, dotacion_id_dotacion INT NOT NULL,
 fecha_baja_dotacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, desc_baja_dotacion VARCHAR(500) NOT NULL, id_usuario INT NOT NULL,
 FOREIGN KEY (dotacion_id_dotacion) REFERENCES dotacion(id_dotacion), FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS venta_extraordinaria (
 id_venta_extraordinaria INT PRIMARY KEY AUTO_INCREMENT, dotacion_id_dotacion INT NOT NULL, autorizo_id_autorizo INT NOT NULL,
 fecha_autorizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, num_dotacion INT NOT NULL CHECK(num_dotacion BETWEEN 1 AND 10),
 justificacion_venta VARCHAR(500) NOT NULL, id_usuario INT NOT NULL, status_venta_extraordinaria ENUM('0','1','2','3') NOT NULL DEFAULT '1',
 FOREIGN KEY (dotacion_id_dotacion) REFERENCES dotacion(id_dotacion), FOREIGN KEY (autorizo_id_autorizo) REFERENCES autorizo(id_autorizo),
 FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
-- Contrato de integración de solo lectura para servicios, no sustituye al sistema de caja.
CREATE TABLE IF NOT EXISTS servicio_historial (
 id_servicio INT PRIMARY KEY AUTO_INCREMENT, padron_id_padron INT NOT NULL, recibo VARCHAR(40) NOT NULL UNIQUE,
 fecha DATE NOT NULL, importe DECIMAL(9,2) NOT NULL, viajes INT NOT NULL DEFAULT 1,
 estado ENUM('Entregado','Sin papelería','No se entregó','Fuera de tiempo','Pendiente') NOT NULL,
 origen VARCHAR(100) NOT NULL, FOREIGN KEY (padron_id_padron) REFERENCES padron(id_padron), INDEX idx_fecha(fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS auditoria (
 id_auditoria BIGINT PRIMARY KEY AUTO_INCREMENT, padron_id_padron INT, id_usuario INT NOT NULL,
 accion VARCHAR(60) NOT NULL, detalle TEXT NOT NULL, creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (padron_id_padron) REFERENCES padron(id_padron), FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
