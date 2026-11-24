#!/usr/bin/env bash
# ==============================================================================
# APOLLO - Script Otomatisasi XAMPP, Apache, MySQL & Import Database di Ubuntu
# ==============================================================================

if [ "$EUID" -ne 0 ]; then
  echo "[-] Harap jalankan script ini dengan hak akses sudo:"
  echo "    sudo ./setup_xampp.sh"
  exit 1
fi

echo "[1/4] Menghentikan service Apache & MySQL native Ubuntu agar port 80 & 3306 bebas..."
systemctl stop apache2 2>/dev/null || true
systemctl stop mysql 2>/dev/null || true
systemctl stop mariadb 2>/dev/null || true

echo "[2/4] Menyalakan XAMPP Linux (/opt/lampp)..."
/opt/lampp/lampp start

# Pastikan symlink /var/www/html/apollo juga ada sebagai cadangan
mkdir -p /var/www/html
ln -sfn /opt/lampp/htdocs/apollo /var/www/html/apollo 2>/dev/null || true

echo "[3/4] Meng-import database.sql ke MySQL XAMPP (db_apollo)..."
# Tunggu soket mysql siap
sleep 2
/opt/lampp/bin/mysql -u root -e "CREATE DATABASE IF NOT EXISTS db_apollo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
/opt/lampp/bin/mysql -u root db_apollo < /opt/lampp/htdocs/apollo/database.sql

echo "[4/4] Memverifikasi tabel database di db_apollo..."
/opt/lampp/bin/mysql -u root db_apollo -e "SHOW TABLES;"

echo ""
echo "======================================================================"
echo " [OK] XAMPP & DATABASE BERHASIL DIAKTIFKAN!"
echo " Web APOLLO kini aktif dan dapat langsung dibuka di:"
echo " -> http://localhost/apollo"
echo " -> http://127.0.0.1:8000 (PHP Development Server)"
echo "======================================================================"
