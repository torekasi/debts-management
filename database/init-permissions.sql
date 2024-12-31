-- Create default database user and grant permissions
CREATE USER IF NOT EXISTS 'debt_user'@'%' IDENTIFIED BY 'debt_password';
GRANT ALL PRIVILEGES ON mydebts.* TO 'debt_user'@'%';
FLUSH PRIVILEGES;
