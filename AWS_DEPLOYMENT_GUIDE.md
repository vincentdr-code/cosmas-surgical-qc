# Cosmas AWS Deployment Guide - May 12, 2026

**Goal:** Deploy Cosmas to AWS Free Tier (EC2 t2.micro + RDS)  
**Timeline:** 2-3 hours setup, then immediate deployment  
**Cost:** Free tier eligible (fully free for 12 months)

---

## Phase 1: Prepare AWS Account (15 min)

### 1.1 Sign in to AWS Console
- Go to https://aws.amazon.com
- Sign in or create account
- Enable MFA on root account (recommended)
- Switch to EC2 dashboard

### 1.2 Choose Region
- **Select: us-east-1** (N. Virginia) - largest free tier support
- Consistency check: All resources created in same region

---

## Phase 2: Create EC2 Instance (30 min)

### 2.1 Launch Instance
1. EC2 Dashboard → **Instances** → **Launch Instances**
2. **Name:** cosmas-app
3. **OS Image:** Ubuntu 22.04 LTS (Free tier eligible)
4. **Instance Type:** t2.micro (Free tier eligible - 750 hrs/month)
5. **Key Pair:**
   - Create new: `cosmas-keypair`
   - Download and save to `C:\Users\danie\cosmas-keypair.pem`
   - **IMPORTANT:** Keep this safe - you'll need it to SSH

### 2.2 Configure Network
- **VPC:** Default VPC
- **Subnet:** Default subnet
- **Auto-assign public IP:** Enable
- **Security Group:** Create new
  - Name: `cosmas-sg`
  - Inbound rules:
    ```
    SSH (22)     | Anywhere (0.0.0.0/0)
    HTTP (80)    | Anywhere (0.0.0.0/0)
    HTTPS (443)  | Anywhere (0.0.0.0/0)
    MySQL (3306) | Custom (will add RDS SG later)
    ```

### 2.3 Storage
- **Volume:** 20 GB gp3 (free tier)
- **Delete on termination:** Yes

### 2.4 Launch
- Click **Launch Instance**
- Wait for instance to reach "running" state (2-3 min)
- Copy **Public IPv4 address** (e.g., 54.123.45.67)

---

## Phase 3: Set Up RDS Database (30 min)

### 3.1 Create RDS Instance
1. RDS Dashboard → **Databases** → **Create Database**
2. **Engine:** MySQL 8.0.35 (free tier eligible)
3. **DB Instance Identifier:** cosmas-db
4. **Master Username:** admin
5. **Master Password:** Generate strong password, save it
6. **Instance Class:** db.t3.micro (free tier eligible)
7. **Storage:** 20 GB gp2, no autoscaling
8. **Availability:** Single AZ (save costs)

### 3.2 Configure Network
- **VPC:** Default (same as EC2)
- **Public Accessibility:** No (EC2 accesses internally)
- **Security Group:** Create new
  - Name: `cosmas-rds-sg`
  - Inbound: MySQL 3306 from cosmas-sg only
  - Outbound: Default allow all

### 3.3 Create
- Click **Create database**
- Wait for instance to reach "Available" state (5-10 min)
- Copy **Endpoint** (e.g., cosmas-db.abc123.us-east-1.rds.amazonaws.com)

### 3.4 Initial Setup
1. From EC2, connect to RDS and create database:
   ```bash
   mysql -h cosmas-db.abc123.us-east-1.rds.amazonaws.com -u admin -p
   CREATE DATABASE cosmas;
   EXIT;
   ```

---

## Phase 4: Create S3 Bucket (15 min)

### 4.1 Create Bucket
1. S3 Dashboard → **Buckets** → **Create Bucket**
2. **Bucket Name:** cosmas-instruments-[TIMESTAMP]
   - (S3 names must be globally unique)
3. **Region:** us-east-1
4. **Block Public Access:** Uncheck "Block public access"
   - (Images need to be public)

### 4.2 Configure Permissions
1. Bucket → **Permissions** tab
2. **Block Public Access:** All unchecked
3. **Bucket Policy:** Add:
   ```json
   {
     "Version": "2012-10-17",
     "Statement": [
       {
         "Sid": "PublicRead",
         "Effect": "Allow",
         "Principal": "*",
         "Action": "s3:GetObject",
         "Resource": "arn:aws:s3:::cosmas-instruments-*/*"
       }
     ]
   }
   ```

### 4.3 Copy Bucket Name
- Save: `cosmas-instruments-[TIMESTAMP]`

---

## Phase 5: Connect EC2 and Deploy (45 min)

### 5.1 SSH into EC2
```bash
# Windows PowerShell or Git Bash
$key = "C:\Users\danie\cosmas-keypair.pem"
ssh -i $key ubuntu@54.123.45.67
```

### 5.2 Install Dependencies
```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install PHP 8.2 and extensions
sudo apt install -y php8.2-fpm php8.2-cli php8.2-mysql php8.2-curl php8.2-gd php8.2-xml php8.2-zip

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Node.js
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs

# Install Git
sudo apt install -y git

# Install Nginx
sudo apt install -y nginx

# MySQL client (to test RDS)
sudo apt install -y mysql-client
```

### 5.3 Clone and Set Up Cosmas
```bash
# Clone repo (ensure it's public on GitHub first)
cd /var/www
sudo git clone https://github.com/yourusername/cosmas.git
cd cosmas

# Set permissions
sudo chown -R ubuntu:ubuntu /var/www/cosmas

# Install PHP dependencies
composer install --no-dev

# Install Node dependencies
npm install
npm run build

# Create .env
cp .env.example .env
nano .env
# Update:
# APP_URL=http://54.123.45.67
# DB_HOST=cosmas-db.abc123.us-east-1.rds.amazonaws.com
# DB_USERNAME=admin
# DB_PASSWORD=[your-password]
# DB_DATABASE=cosmas
# ANTHROPIC_API_KEY=[your-key]
# AWS_ACCESS_KEY_ID=[from IAM]
# AWS_SECRET_ACCESS_KEY=[from IAM]
# AWS_DEFAULT_REGION=us-east-1
# AWS_BUCKET=cosmas-instruments-[timestamp]

# Generate app key
php artisan key:generate

# Run migrations
php artisan migrate --force

# Seed database
php artisan db:seed

# Create storage symlink
php artisan storage:link
```

### 5.4 Configure Nginx
```bash
# Create Nginx config
sudo nano /etc/nginx/sites-available/cosmas
```

Paste:
```nginx
server {
    listen 80 default_server;
    server_name _;
    root /var/www/cosmas/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

Enable and restart:
```bash
sudo ln -s /etc/nginx/sites-available/cosmas /etc/nginx/sites-enabled/
sudo rm /etc/nginx/sites-enabled/default
sudo systemctl restart nginx php8.2-fpm
```

---

## Phase 6: Update Laravel for AWS S3 (15 min)

### 6.1 Update Storage Config
Edit `config/filesystems.php`:
```php
'default' => env('FILESYSTEM_DISK', 's3'),  // Change from 'local'

's3' => [
    'driver' => 's3',
    'key' => env('AWS_ACCESS_KEY_ID'),
    'secret' => env('AWS_SECRET_ACCESS_KEY'),
    'region' => env('AWS_DEFAULT_REGION'),
    'bucket' => env('AWS_BUCKET'),
    'url' => env('AWS_URL'),
    'visibility' => 'public',
],
```

### 6.2 Install AWS SDK
```bash
composer require aws/aws-sdk-php
```

### 6.3 Create IAM User for S3 Access
1. IAM Dashboard → **Users** → **Create User**
2. Name: `cosmas-app`
3. Attach policy: `AmazonS3FullAccess`
4. Create access key
5. Copy Access Key ID and Secret Access Key
6. Add to `.env`:
   ```
   AWS_ACCESS_KEY_ID=AKIA...
   AWS_SECRET_ACCESS_KEY=...
   ```

---

## Phase 7: Verify Deployment (15 min)

### 7.1 Health Check
```bash
curl http://54.123.45.67/api/health
```

Should return:
```json
{"status":"ok","timestamp":"...","service":"Cosmas API v1.0"}
```

### 7.2 Test Upload via API
```bash
curl -X POST http://54.123.45.67/api/inspections \
  -F "image=@/path/to/image.jpg" \
  -F "user_id=1"
```

### 7.3 View in Browser
- Go to `http://54.123.45.67`
- Upload image
- Verify results display
- Check S3 bucket for stored image

---

## Phase 8: Domain Setup (Optional, 10 min)

### 8.1 Get Domain
- Buy from Route 53 or external registrar
- Point A record to EC2 public IP

### 8.2 SSL Certificate
```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d yourdomain.com
```

---

## Troubleshooting

| Issue | Solution |
|-------|----------|
| Can't SSH | Check security group allows port 22, verify key pair permissions: `chmod 400 cosmas-keypair.pem` |
| Database won't connect | Check RDS security group allows EC2 security group inbound on port 3306 |
| S3 upload fails | Verify IAM permissions, check `.env` AWS credentials |
| Images not displaying | Check S3 bucket public access is enabled, verify bucket policy |
| 502 Bad Gateway | Check PHP-FPM is running: `sudo systemctl status php8.2-fpm` |

---

## Cost Estimate (Free Tier)

- **EC2 t2.micro:** 750 hrs/month free (~1 instance free)
- **RDS db.t3.micro:** 750 hrs/month free (~1 instance free)
- **S3:** 5 GB storage free, 20,000 GET requests free
- **Data Transfer:** 1 GB/month free outbound

**Total: $0/month for first 12 months** (if within free tier limits)

---

## Next Steps After Deployment

1. ✅ Verify app works on AWS
2. ✅ Push code to GitHub
3. ✅ Update README with deployment steps
4. ⏭️ Start orchestrator agents (Week 2)
5. ⏭️ Create demo video
6. ⏭️ Submit to competition

---

## Quick Reference: Important Endpoints

```
Web:  http://[PUBLIC-IP]
API:  http://[PUBLIC-IP]/api/inspections
Health: http://[PUBLIC-IP]/api/health
Upload: POST http://[PUBLIC-IP]/api/inspections
```

---

**Estimated Total Time:** 2-3 hours for initial setup  
**Next Deployment:** Just `git pull && php artisan migrate`
