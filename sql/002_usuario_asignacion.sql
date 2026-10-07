CREATE TABLE IF NOT EXISTS usuario_asignacion (
 id_usuario INT NOT NULL PRIMARY KEY,
 id_caja INT NULL,
 dias VARCHAR(13) NOT NULL DEFAULT '',
 hora_inicio TIME NULL,
 hora_fin TIME NULL,
 actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 CONSTRAINT fk_asignacion_usuario FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario),
 CONSTRAINT fk_asignacion_caja FOREIGN KEY (id_caja) REFERENCES caja(id_caja),
 CONSTRAINT ck_asignacion_horas CHECK ((hora_inicio IS NULL AND hora_fin IS NULL AND dias='') OR (hora_inicio IS NOT NULL AND hora_fin IS NOT NULL AND hora_inicio<>hora_fin AND dias<>''))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
