import pyodbc
from dao.base_dao import BaseDAO


class SQLServerDAO(BaseDAO):

    def __init__(self, settings: dict):
        conn_str = (
            f"DRIVER={settings['driver']};"
            f"SERVER={settings['server']};"
            f"DATABASE={settings['database']};"
            f"UID={settings['username']};"
            f"PWD={settings['password']}"
        )
        self.conn = pyodbc.connect(conn_str)
        self.conn.autocommit = True

    # ============================================================
    #   PRODUCTOS
    # ============================================================

    def get_all_products(self):
        cursor = self.conn.cursor()
        cursor.execute("SELECT id, nombre, precio, imagen FROM productos")
        return cursor.fetchall()

    def get_product_by_id(self, producto_id: int):
        cursor = self.conn.cursor()
        cursor.execute(
            "SELECT id, nombre, precio, imagen FROM productos WHERE id = ?",
            (producto_id,)
        )
        return cursor.fetchone()

    def create_product(self, nombre: str, precio: float, imagen: str):
        cursor = self.conn.cursor()
        cursor.execute(
            "INSERT INTO productos (nombre, precio, imagen) VALUES (?, ?, ?)",
            (nombre, precio, imagen)
        )
        cursor.execute("SELECT SCOPE_IDENTITY()")
        new_id = cursor.fetchone()[0]
        return new_id

    def update_product(self, producto_id: int, nombre: str, precio: float, imagen: str):
        cursor = self.conn.cursor()
        cursor.execute(
            "UPDATE productos SET nombre = ?, precio = ?, imagen = ? WHERE id = ?",
            (nombre, precio, imagen, producto_id)
        )

    def delete_product(self, producto_id: int):
        cursor = self.conn.cursor()
        cursor.execute("DELETE FROM productos WHERE id = ?", (producto_id,))

    # ============================================================
    #   USUARIOS
    # ============================================================

    def get_user_by_email(self, email: str):
        cursor = self.conn.cursor()
        cursor.execute("""
            SELECT id, nombre, email, password_hash, rol
            FROM usuarios
            WHERE email = ?
        """, (email,))
        return cursor.fetchone()

    def get_user_by_id(self, user_id: int):
        cursor = self.conn.cursor()
        cursor.execute("""
            SELECT id, nombre, email, password_hash, rol
            FROM usuarios
            WHERE id = ?
        """, (user_id,))
        return cursor.fetchone()

    def create_user(self, nombre: str, email: str, password_hash: str, rol: str):
        cursor = self.conn.cursor()
        cursor.execute("""
            INSERT INTO usuarios (nombre, email, password_hash, rol)
            VALUES (?, ?, ?, ?)
        """, (nombre, email, password_hash, rol))

    # ============================================================
    #   ÓRDENES (CARRITO → COMPRA REAL)
    # ============================================================

    def create_order(self, usuario_id: int, total: float):
        cursor = self.conn.cursor()
        cursor.execute("""
            INSERT INTO ordenes (usuario_id, total)
            VALUES (?, ?)
        """, (usuario_id, total))
        cursor.execute("SELECT SCOPE_IDENTITY()")
        orden_id = cursor.fetchone()[0]
        return int(orden_id)

    def add_order_detail(self, orden_id: int, producto_id: int, cantidad: int, precio_unitario: float):
        cursor = self.conn.cursor()
        cursor.execute("""
            INSERT INTO orden_detalle (orden_id, producto_id, cantidad, precio_unitario)
            VALUES (?, ?, ?, ?)
        """, (orden_id, producto_id, cantidad, precio_unitario))

    def get_all_orders(self):
        cursor = self.conn.cursor()
        cursor.execute("""
            SELECT o.id, u.nombre, o.fecha, o.total
            FROM ordenes o
            JOIN usuarios u ON o.usuario_id = u.id
            ORDER BY o.fecha DESC
        """)
        return cursor.fetchall()

    def get_order_details(self, orden_id: int):
        cursor = self.conn.cursor()
        cursor.execute("""
            SELECT p.nombre, d.cantidad, d.precio_unitario
            FROM orden_detalle d
            JOIN productos p ON d.producto_id = p.id
            WHERE d.orden_id = ?
        """, (orden_id,))
        return cursor.fetchall()
