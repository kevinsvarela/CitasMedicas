-- Tablas del Sistema
SET NAMES utf8mb4;

CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE
);

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    rol_id INT NOT NULL,
    activo BOOLEAN DEFAULT 1,
    tkRef VARCHAR(255),
    FOREIGN KEY (rol_id) REFERENCES roles(id)
);

CREATE TABLE pacientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    cedula VARCHAR(20) NOT NULL UNIQUE,
    nombre_completo VARCHAR(150) NOT NULL,
    fecha_nacimiento DATE NOT NULL,    
    telefono VARCHAR(20),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

CREATE TABLE especialidades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL
);

CREATE TABLE medicos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL, -- Vinculado para login
    especialidad_id INT NOT NULL,
    nombre_completo VARCHAR(150) NOT NULL,
    licencia VARCHAR(50) NOT NULL UNIQUE,
    telefono VARCHAR(20),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (especialidad_id) REFERENCES especialidades(id) 
);

CREATE TABLE administradores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT, -- Vinculado para login
    nombre_completo VARCHAR(150) NOT NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

CREATE TABLE citas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paciente_id INT NOT NULL,
    medico_id INT NOT NULL,
    fecha_hora DATETIME NOT NULL,
    estado ENUM('Programada', 'Completada', 'Cancelada') DEFAULT 'Programada',
    motivo TEXT,
    FOREIGN KEY (paciente_id) REFERENCES pacientes(id),
    FOREIGN KEY (medico_id) REFERENCES medicos(id)
);

-- Procedimiento Almacenado: Agendar Cita con validación
DELIMITER //
CREATE PROCEDURE sp_crear_cita(
    IN p_paciente_id INT,
    IN p_medico_id INT,
    IN p_fecha_hora DATETIME,
    IN p_motivo TEXT,
    OUT p_resultado VARCHAR(100)
)
BEGIN
    DECLARE v_existe INT;

    -- Verificar que el médico no tenga otra cita en esa hora exacta
    SELECT COUNT(*) INTO v_existe 
    FROM citas 
    WHERE medico_id = p_medico_id 
      AND fecha_hora = p_fecha_hora 
      AND estado = 'Programada';

    IF v_existe > 0 THEN
        SET p_resultado = 'Error: El médico ya tiene una cita en ese horario.';
    ELSE
        INSERT INTO citas (paciente_id, medico_id, fecha_hora, motivo)
        VALUES (p_paciente_id, p_medico_id, p_fecha_hora, p_motivo);
        SET p_resultado = 'Cita agendada exitosamente.';
    END IF;
END //

-- SP para obtener citas de un paciente
CREATE PROCEDURE sp_obtener_citas_paciente(
    IN p_paciente_id INT
)
BEGIN
    SELECT c.id, c.fecha_hora, c.estado, c.motivo, m.nombre_completo AS medico, e.nombre AS especialidad
    FROM citas c
    INNER JOIN medicos m ON c.medico_id = m.id
    INNER JOIN especialidades e ON m.especialidad_id = e.id
    WHERE c.paciente_id = p_paciente_id
    ORDER BY c.fecha_hora DESC;
END //

-- SP para obtener citas asignadas a un médico
CREATE PROCEDURE sp_obtener_citas_medico(
    IN m_medico_id INT
)
BEGIN   
    SELECT c.id, c.fecha_hora, c.estado, c.motivo, p.nombre_completo AS paciente
    FROM citas c
    INNER JOIN pacientes p ON c.paciente_id = p.id
    WHERE c.medico_id = m_medico_id
    ORDER BY c.fecha_hora DESC;
END //

DELIMITER ;

USE citas_medicas;

INSERT INTO roles(nombre) VALUES ('Admin');
INSERT INTO roles(nombre) VALUES ('Médico');
INSERT INTO roles(nombre) VALUES ('Paciente');


INSERT INTO especialidades(nombre) VALUES 
('Dermatología'),
('Medicina General'),
('Cardiología'),
('Pediatría');
