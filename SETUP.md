# Cosmas Setup Guide - May 12, 2026

## Current Status
✅ Database schema created (inspections table)
✅ Inspection model created
✅ InspectionController with Claude Vision API integration
✅ Views created (upload, results, audit-log)
✅ Routes configured
✅ Base layout with Tailwind CSS

## Environment Setup

### 1. Configure ANTHROPIC_API_KEY
Edit `.env` and add your API key:
```
ANTHROPIC_API_KEY=sk-ant-...your-key-here...
```

### 2. Ensure MySQL is Running
The Docker MySQL container must be running on port 3306:
```
docker ps | grep mysql
```

### 3. Run Migrations (if not already done)
```
php artisan migrate
```

### 4. Seed Test Data
```
php artisan db:seed
```
This creates a test user (test@example.com)

### 5. Start Development Server
```
php artisan serve
```

The app will be available at: **http://localhost:8000**

## Application Flow

1. **Homepage** → Redirects to `/upload`
2. **Upload Page** (`/upload`) → Upload surgical instrument image
3. **Results Page** (`/results/{id}`) → View analysis results
4. **Audit Log** (`/audit-log`) → View all inspections

## API Integration

The InspectionController integrates with Claude Vision API using:
- Model: `claude-3-5-sonnet-20241022`
- Endpoint: `https://api.anthropic.com/v1/messages`
- Max tokens: 1024
- Vision capability: Base64 image encoding

## Database Schema

```
inspections
├── id (Primary Key)
├── user_id (Foreign Key → users)
├── image_path (string)
├── defect_type (string, nullable)
├── confidence (float, nullable) [0-100]
├── pass_fail (string) ['PASS', 'FAIL', 'PENDING', 'ERROR']
├── claude_reasoning (text, nullable)
├── bounding_box (json, nullable)
├── created_at
└── updated_at
```

## Next Steps

- [x] Database setup
- [x] Core inspection routes and controller
- [x] Views and layout
- [x] Claude Vision API integration
- [ ] Test with real images
- [ ] Add image storage symlink
- [ ] Build 5-agent orchestrator system
- [ ] Create FastAPI dashboard
- [ ] Deploy to AWS EC2
- [ ] Create GitHub commits
- [ ] Demo video and submission

## Running on Windows

From `C:\Users\danie\cosmas-app`:

```powershell
# Start MySQL Docker container
docker run -d --name mysql-cosmas -p 3306:3306 -e MYSQL_ROOT_PASSWORD=root -e MYSQL_DATABASE=cosmas mysql:8

# Run migrations
php artisan migrate

# Seed database
php artisan db:seed

# Start development server
php artisan serve

# In another terminal, if using Vite:
npm run dev
```

## Public Storage Symlink

Create the public storage symlink:
```
php artisan storage:link
```

This allows images to be served from `/public/storage/`.

## Testing Checklist

- [ ] Home page redirects to upload
- [ ] Image upload works
- [ ] Claude API key is valid
- [ ] Images are stored correctly
- [ ] Results page displays analysis
- [ ] Audit log shows all inspections
- [ ] Database queries work correctly
