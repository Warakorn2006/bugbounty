CREATE DATABASE IF NOT EXISTS bugbounty CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bugbounty;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) UNIQUE NOT NULL,
  email VARCHAR(100) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('user','company','admin') NOT NULL DEFAULT 'user',
  company_name VARCHAR(100) NULL,
  points INT DEFAULT 0,
  status ENUM('active','banned') DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE campaigns (
  id INT AUTO_INCREMENT PRIMARY KEY,
  company_id INT NOT NULL,
  title VARCHAR(200) NOT NULL,
  description TEXT NOT NULL,
  target_url VARCHAR(500),
  scope TEXT,
  rules TEXT,
  reward_sqli INT DEFAULT 500,
  reward_xss INT DEFAULT 200,
  reward_other INT DEFAULT 100,
  status ENUM('active','closed','pending') DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (company_id) REFERENCES users(id)
);

CREATE TABLE reports (
  id INT AUTO_INCREMENT PRIMARY KEY,
  campaign_id INT NOT NULL,
  user_id INT NOT NULL,
  title VARCHAR(200) NOT NULL,
  vulnerability_type ENUM('SQL Injection','XSS','CSRF','RCE','LFI','IDOR','Other') NOT NULL,
  severity ENUM('Low','Medium','High','Critical') NOT NULL,
  description TEXT NOT NULL,
  steps_to_reproduce TEXT NOT NULL,
  impact TEXT,
  poc_file VARCHAR(255),
  status ENUM('Pending','Triaged','Resolved','Duplicate','Rejected') DEFAULT 'Pending',
  points_awarded INT DEFAULT 0,
  company_comment TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (campaign_id) REFERENCES campaigns(id),
  FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE rewards (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  description TEXT,
  points_required INT NOT NULL,
  stock INT DEFAULT 0,
  image_url VARCHAR(255),
  category VARCHAR(50)
);

CREATE TABLE redemptions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  reward_id INT NOT NULL,
  points_spent INT NOT NULL,
  status ENUM('pending','fulfilled','cancelled') DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (reward_id) REFERENCES rewards(id)
);

CREATE TABLE security_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  action_type VARCHAR(50) NOT NULL,
  user_id INT NULL,
  ip_address VARCHAR(45),
  user_agent TEXT,
  details TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE disputes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  report_id INT NOT NULL,
  user_id INT NOT NULL,
  reason TEXT NOT NULL,
  status ENUM('open','resolved') DEFAULT 'open',
  admin_note TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (report_id) REFERENCES reports(id),
  FOREIGN KEY (user_id) REFERENCES users(id)
);

-- ============================================================
-- Default Accounts
-- Password for all default accounts: Admin@1234
-- Hash generated with password_hash('Admin@1234', PASSWORD_BCRYPT)
-- ============================================================

-- Admin account
INSERT INTO users (username, email, password, role) VALUES
('admin', 'admin@bugbounty.local', '$2y$10$TKh8H1.PfbuNIXle0Bn8hO8ug2DqjbfbCXE.1J4dR.F5VjQCHLGq6', 'admin');

-- Sample company account
INSERT INTO users (username, email, password, role, company_name) VALUES
('techcorp', 'contact@techcorp.com', '$2y$10$TKh8H1.PfbuNIXle0Bn8hO8ug2DqjbfbCXE.1J4dR.F5VjQCHLGq6', 'company', 'TechCorp Thailand');

-- Sample rewards
INSERT INTO rewards (name, description, points_required, stock, category) VALUES
('Amazon Gift Card 100฿', 'บัตรของขวัญ Amazon มูลค่า 100 บาท', 500, 50, 'gift-card'),
('T-Shirt BugHunter', 'เสื้อยืดลาย Bug Hunter ไซส์ M-XL', 800, 20, 'merchandise'),
('True Money 200฿', 'เติมเงิน True Money 200 บาท', 1000, 30, 'gift-card'),
('USB Security Key', 'YubiKey สำหรับ 2FA authentication', 3000, 10, 'hardware'),
('Premium Account 1 Month', 'บัญชี Premium เข้าถึง Advanced Courses 1 เดือน', 1500, 100, 'digital');

-- Sample active campaigns (company_id=2)
INSERT INTO campaigns (company_id, title, description, target_url, scope, rules, reward_sqli, reward_xss, reward_other, status) VALUES
(2, 'TechCorp Main Website Security Audit',
 'ต้องการให้นักวิจัยด้านความปลอดภัยทดสอบเว็บไซต์หลักของ TechCorp Thailand เพื่อค้นหาช่องโหว่ด้านความปลอดภัยและรายงานให้เราทราบ โดยมีรางวัลสำหรับช่องโหว่ที่ได้รับการยืนยัน',
 'https://www.techcorp.th',
 'www.techcorp.th, api.techcorp.th, auth.techcorp.th',
 'ห้ามโจมตีระบบ Production จริง | ห้าม DDoS/DoS | ห้าม Social Engineering | ต้องรายงานผ่านแพลตฟอร์มเท่านั้น | ห้ามเปิดเผยช่องโหว่ก่อนได้รับการแก้ไข',
 1000, 500, 200, 'active'),

(2, 'E-Commerce Platform Bug Hunt',
 'ค้นหาช่องโหว่ในระบบร้านค้าออนไลน์ของเรา โดยเฉพาะระบบชำระเงิน, ระบบบัญชีผู้ใช้ และ API endpoints ต่างๆ',
 'https://shop.techcorp.th',
 'shop.techcorp.th, checkout.techcorp.th',
 'ทดสอบด้วย Test Account ที่ได้รับเท่านั้น | ห้ามทดสอบกับ account ของ user จริง',
 2000, 800, 300, 'active');

-- ============================================================
-- DATABASE USER SETUP (Run these commands as MySQL root)
-- ============================================================
-- CREATE USER 'bugbounty_user'@'localhost' IDENTIFIED BY 'BugBounty@SecurePass123';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON bugbounty.* TO 'bugbounty_user'@'localhost';
-- FLUSH PRIVILEGES;

