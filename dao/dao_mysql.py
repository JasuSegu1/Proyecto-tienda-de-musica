# dao/dao_mysql.py
import pymysql
from dao.base_dao import BaseDAO

class MySQLDAO(BaseDAO):

    def __init__(self, settings):
        self.conn = pymysql.connect(
            host=settings["host"],
            user=settings["user"],
            password=settings["password"],
            database=settings["database"],
            charset="utf8mb4"
        )

    def get_all_products(self):
        cursor = self.conn.cursor()
        cursor.execute("SELECT id, nombre, precio, imagen FROM productos")
        return cursor.fetchall()

    def get_product_by_id(self, producto_id):
        cursor = self.conn.cursor()
        cursor.execute(
            "SELECT id, nombre, precio, imagen FROM productos WHERE id=%s",
            (producto_id,))
        return cursor.fetchone()

    def add_product(self, nombre, precio, imagen):
        cursor = self.conn.cursor()
        cursor.execute(
            "INSERT INTO productos (nombre, precio, imagen) VALUES (%s, %s, %s)",
            (nombre, precio, imagen)
        )
        self.conn.commit()
        return cursor.lastrowid
