# CosmicHub.Online

A comprehensive astrological platform for generating personalized reports and insights with built-in monitoring and observability.

## 🌟 Project Status

### ✅ Implemented Features (as of June 2025)

#### Core Functionality
- [x] User authentication and profile management (JWT)
- [x] Astrological report generation with AI integration (OpenAI)
- [x] Report management system (CRUD operations)
- [x] Export functionality (PDF/CSV)
- [x] Credit-based system for report generation
- [x] Referral program
- [x] Responsive UI with Material-UI
- [x] Stripe payment integration (one-time payments)
- [x] Backend and frontend monorepo deployment on Railway
- [x] Static file serving: backend serves frontend in production
- [x] Health checks, monitoring endpoints, and Sentry integration
- [x] Environment-based configuration and secret management
- [x] Error handling, logging, and debugging improvements
- [x] Docker/Nixpacks deployment support
- [x] Automated setup scripts for dev environment

#### Technical Features
- [x] ES Module support throughout the application
- [x] Consistent date/time formatting (USA format)
- [x] API documentation and OpenAPI support
- [x] Environment-based configuration
- [x] CI/CD pipeline (GitHub Actions)
- [x] Railway and Vercel deployment support
- [x] Comprehensive `.dockerignore` for fast builds

### 🚧 In Progress / Next Up

#### Core Features
- [ ] **Subscription-based premium content** (highest priority)
- [ ] Advanced report types (compatibility, transit, etc.)
- [ ] Interactive birth chart visualization
- [ ] Mobile app development (React Native)

#### Technical Enhancements
- [ ] Comprehensive test coverage (unit, integration, E2E)
- [ ] Performance optimizations (frontend bundle, backend caching)
- [ ] Enhanced monitoring and observability (Prometheus, Grafana)
- [ ] CI/CD pipeline improvements

## 🏆 **Recommended Next Work Target**

**Implement subscription-based premium content:**
- Add recurring Stripe subscription support (backend and frontend)
- UI for users to subscribe, manage, and access premium content
- Enforce access control for premium features
- Test subscription flows and payment webhooks

**Why:** This is the core monetization feature and unlocks premium value for users.

---

## 🛠 Tech Stack

### Frontend
- **Framework**: React 18
- **UI Library**: Material-UI (MUI v5)
- **State Management**: React Context API
- **Routing**: React Router v6
- **Form Handling**: React Hook Form
- **HTTP Client**: Axios
- **Date Handling**: date-fns

### Backend
- **Runtime**: Node.js 20.x
- **Framework**: Express.js
- **Database**: MongoDB with Mongoose ODM
- **Authentication**: JWT
- **Payments**: Stripe Integration
- **AI Integration**: OpenAI API
- **PDF Generation**: PDFKit
- **Caching**: node-cache

### DevOps & Infrastructure
- **Version Control**: Git & GitHub
- **Frontend Hosting**: Vercel
- **Backend Hosting**: Railway
- **Database**: Railway MongoDB
- **Containerization**: Docker
- **CI/CD**: GitHub Actions
- **Monitoring**: Winston logging, Sentry, Prometheus

## 🚀 Quick Start

### Prerequisites

- Node.js 20.x or higher
- npm 9.x or higher
- Git
- MongoDB (local installation or Docker)

### Automated Setup (Recommended)

#### Windows (PowerShell)
```powershell
# Run the automated setup script
powershell -ExecutionPolicy Bypass -File scripts/setup-dev-environment.ps1

# Start development servers
powershell -ExecutionPolicy Bypass -File scripts/start-dev.ps1
```

#### Linux/macOS (Bash)
```bash
# Make scripts executable
chmod +x scripts/*.sh

# Run the automated setup script
./scripts/setup-dev-environment.sh

# Start development servers
./scripts/start-dev.sh
```

### Manual Setup

1. **Clone the repository**
   ```bash
   git clone https://github.com/kdsmith18542/CosmicHub.Online.git
   cd CosmicHub.Online
   ```

2. **Install dependencies**
   ```bash
   # Install backend dependencies
   cd backend
   npm install
   
   # Install frontend dependencies
   cd ../frontend
   npm install
   ```

3. **Environment Setup**
   - Copy `.env.example` to `.env` in both frontend and backend directories
   - Update the environment variables with your configuration

4. **Start Development Servers**
   ```bash
   # Start backend (from backend directory)
   npm run dev
   
   # Start frontend (from frontend directory)
   npm run dev
   ```

## 📚 Documentation

- [Development Guide](./DEVELOPMENT.md)
- [API Documentation](./API_AUTH_README.md)
- [Date Formatting Guide](./frontend/src/utils/date-formatting-README.md)
- [Export Functionality](./frontend/src/components/exports/README.md)
- [Deployment Guide](./DEPLOYMENT.md)

## 🗄️ Database Setup

### Local Development
1. **Install MongoDB locally** from [MongoDB Documentation](https://docs.mongodb.com/manual/installation/)
2. **Start MongoDB**: `mongod`
3. **The application will automatically create the database**

### Docker (Alternative)
```bash
docker run -d -p 27017:27017 --name mongodb mongo:6.0
```

### Railway (Production)
1. Create a Railway project
2. Add MongoDB service
3. Use the provided `DATABASE_URL`

## 🔧 Development Scripts

### Setup Scripts
- `scripts/setup-dev-environment.ps1` - Windows PowerShell setup
- `scripts/setup-dev-environment.sh` - Linux/macOS bash setup

### Development Scripts
- `scripts/start-dev.ps1` - Start development servers (Windows)
- `scripts/start-dev.sh` - Start development servers (Linux/macOS)

### Available Commands
```bash
# Backend
cd backend
npm run dev          # Start development server
npm test            # Run tests
npm run lint        # Run linter
npm run build       # Build for production

# Frontend
cd frontend
npm run dev         # Start development server
npm test           # Run tests
npm run build      # Build for production
```

## 🌐 Access Points

- **Frontend**: http://localhost:3000
- **Backend API**: http://localhost:5000
- **API Documentation**: http://localhost:5000/api-docs

## 🤝 Contributing

1. Fork the project
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- All contributors who have helped shape this project
- Open source libraries and tools used in this project
- The astrological community for inspiration and knowledge sharing

---

## 🚦 Deployment & Monitoring

- Backend and frontend deployed as a monorepo on Railway
- Health checks, metrics, and Sentry error tracking enabled
- See `GO_LIVE_CHECKLIST.md` and `RAILWAY_DEPLOYMENT_CHECKLIST.md` for production readiness

---

## 📌 Work Targets & Next Steps

- [ ] **Subscription-based premium content** (highest priority)
- [ ] Advanced report types and interactive features
- [ ] Comprehensive test coverage
- [ ] Performance and monitoring enhancements
- [ ] Finalize go-live checklist and production DNS/SSL
