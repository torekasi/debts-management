-- Grant all privileges to the user
CREATE USER IF NOT EXISTS 'rc126893_mydebts'@'%' IDENTIFIED BY 'Malaysia@2413';
GRANT ALL PRIVILEGES ON rc126893_mydebts.* TO 'rc126893_mydebts'@'%';
FLUSH PRIVILEGES;
