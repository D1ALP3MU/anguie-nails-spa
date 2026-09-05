-- =====================================================
-- DATABASE
-- =====================================================
DROP DATABASE IF EXISTS spa_db;

CREATE DATABASE spa_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE spa_db;

-- =====================================================
-- ROLES
-- =====================================================
CREATE TABLE roles (
    id_rol INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- USUARIOS
-- =====================================================
CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    id_rol INT NOT NULL,
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_rol)
        REFERENCES roles(id_rol)
);

-- =====================================================
-- CLIENTES
-- =====================================================
CREATE TABLE clientes (
    id_cliente INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL UNIQUE,
    telefono VARCHAR(20) NOT NULL,
    direccion VARCHAR(255),
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario)
        REFERENCES usuarios(id_usuario)
);

-- =====================================================
-- PROFESIONALES
-- =====================================================
CREATE TABLE profesionales (
    id_profesional INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    especialidad VARCHAR(100),
    telefono VARCHAR(20),
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- SERVICIOS
-- =====================================================
CREATE TABLE servicios (
    id_servicio INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    duracion INT NOT NULL,
    precio DECIMAL(10,2) NOT NULL,
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP
);

-- =====================================================
-- CITAS
-- =====================================================
CREATE TABLE citas (
    id_cita INT AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT NOT NULL,
    id_servicio INT NOT NULL,
    id_profesional INT NOT NULL,
    fecha DATE NOT NULL,
    hora TIME NOT NULL,
    estado ENUM(
        'pendiente',
        'confirmada',
        'cancelada',
        'completada'
	) DEFAULT 'pendiente',
    notas TEXT,
    -- Vale NULL cuando la cita está cancelada. Como MySQL admite
    -- NULL repetidos en un índice UNIQUE, solo las citas vigentes
    -- compiten por el horario y un turno cancelado vuelve a quedar libre.
    reserva_activa TINYINT
        GENERATED ALWAYS AS (
            IF(estado = 'cancelada', NULL, 1)
        ) STORED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_cliente)
        REFERENCES clientes(id_cliente),
    FOREIGN KEY (id_servicio)
        REFERENCES servicios(id_servicio),
    FOREIGN KEY (id_profesional)
        REFERENCES profesionales(id_profesional),
    -- Un profesional no puede tener dos citas vigentes en el mismo
    -- horario. El cruce por duración del servicio se valida además
    -- en AppointmentService.
    UNIQUE KEY uq_citas_agenda (
        id_profesional,
        fecha,
        hora,
        reserva_activa
    )
);

-- =====================================================
-- PAGOS
-- =====================================================
CREATE TABLE pagos (
    id_pago INT AUTO_INCREMENT PRIMARY KEY,
    id_cita INT NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    metodo ENUM(
        'efectivo',
        'tarjeta',
        'transferencia'
    ),
    estado ENUM(
        'pendiente',
        'pagado',
        'rechazado'
    ) DEFAULT 'pendiente',
    referencia_pago VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_cita)
        REFERENCES citas(id_cita)
);

-- =====================================================
-- PRODUCTOS
-- =====================================================
CREATE TABLE productos (
    id_producto INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    precio DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    imagen VARCHAR(255),
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP
);

-- =====================================================
-- CARRITO
-- =====================================================
CREATE TABLE carrito_items (
    id_item INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    id_producto INT NOT NULL,
    cantidad INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario)
        REFERENCES usuarios(id_usuario),
    FOREIGN KEY (id_producto)
        REFERENCES productos(id_producto)
);

-- =====================================================
-- PEDIDOS
-- =====================================================
CREATE TABLE pedidos (
    id_pedido INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    estado ENUM(
        'pendiente',
        'pagado',
        'cancelado'
    ) DEFAULT 'pendiente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario)
        REFERENCES usuarios(id_usuario)
);

-- =====================================================
-- PEDIDO ITEMS
-- =====================================================
CREATE TABLE pedido_items (
    id_item INT AUTO_INCREMENT PRIMARY KEY,
    id_pedido INT NOT NULL,
    id_producto INT NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (id_pedido)
        REFERENCES pedidos(id_pedido),
    FOREIGN KEY (id_producto)
        REFERENCES productos(id_producto)
);

-- =====================================================
-- INDEXES
-- =====================================================
CREATE INDEX idx_usuarios_email
ON usuarios(email);

CREATE INDEX idx_citas_fecha
ON citas(fecha);

CREATE INDEX idx_citas_estado
ON citas(estado);

CREATE INDEX idx_productos_nombre
ON productos(nombre);

CREATE INDEX idx_carrito_usuario
ON carrito_items(id_usuario);

CREATE INDEX idx_pedidos_usuario
ON pedidos(id_usuario);