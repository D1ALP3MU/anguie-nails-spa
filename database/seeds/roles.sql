-- Los identificadores son explícitos porque App\Constants\Roles
-- los referencia por número: ADMIN = 1, CLIENT = 2, EMPLOYEE = 3.
-- Si se dejan al autoincremento, la constante y la base pueden
-- desincronizarse y las comprobaciones de rol dejan de coincidir.

INSERT INTO roles (id_rol, nombre)
VALUES
(1, 'Administrador'),
(2, 'Cliente'),
(3, 'Empleado');
