-- Create test database and grant privileges
CREATE DATABASE IF NOT EXISTS `currency_ticker_test` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON `currency_ticker_test`.* TO 'currency_user'@'%';
FLUSH PRIVILEGES;


