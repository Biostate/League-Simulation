# Football League Simulation

A web application for simulating football league tournaments with match results, standings, and championship predictions.

## About

This application allows users to create and manage football tournaments with customizable teams. The system simulates matches based on team strengths, tracks league standings, and provides championship predictions after the 4th week. Users can play matches week by week or simulate all remaining matches at once.

### Features

- **Tournament Management**: Create tournaments and add teams with customizable strength ratings
- **Match Simulation**: Automatically simulate matches based on team strengths with home advantage and randomness factors
- **League Standings**: Real-time league table with points, wins, draws, losses, and goal difference
- **Championship Predictions**: AI-powered predictions showing each team's probability of winning after week 4
- **Match Editing**: Edit match results and automatically recalculate standings
- **Week Management**: Play matches week by week or simulate all remaining weeks at once
- **Rollback Functionality**: Rollback to previous weeks to undo match results
- **User Authentication**: Registration and login system for managing your tournaments

## Test Application

**Live Demo**: [https://league-simulation-main-vy7aj4.laravel.cloud/](https://league-simulation-main-vy7aj4.laravel.cloud/)

Registration is open - you can create an account and start creating tournaments!

## Technology Stack

### Backend
- **PHP** 8.4.16
- **Laravel** 12
- **Laravel Fortify** (Authentication)
- **Laravel Inertia** v2 (Server-side routing)
- **Spatie Laravel Data** (Data Transfer Objects)
- **Spatie Media Library** (Media management)

### Frontend
- **Vue.js** 3
- **Inertia.js** v2 (Client-side framework)
- **TypeScript**
- **Tailwind CSS** v4
- **Laravel Wayfinder** (Type-safe route generation)
- **Reka UI** (UI components)
- **Lucide Vue** (Icons)

### Development Tools
- **Pest** v4 (Testing framework)
- **PHPUnit** v12
- **Laravel Pint** (Code formatting)
- **ESLint** v9
- **Prettier** v3
- **Vite** (Build tool)

### Database
- **SQLite** (default) or MySQL/PostgreSQL

## Setup & Development

### Prerequisites

- PHP 8.2 or higher
- Composer
- Node.js and npm
- SQLite (or MySQL/PostgreSQL)

### Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd football
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install Node dependencies**
   ```bash
   npm install
   ```

4. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Database setup**
   ```bash
   php artisan migrate
   ```

6. **Build frontend assets**
   ```bash
   npm run build
   ```

### Development

**Using Laravel Herd** (recommended):
```bash
composer run dev-herd
```

**Using PHP built-in server**:
```bash
composer run dev
```

This will start:
- PHP development server
- Queue worker
- Laravel Pail (logs)
- Vite dev server

The application will be available at `http://localhost` (Herd) or `http://localhost:8000` (built-in server).

### Testing

Run all tests:
```bash
php artisan test --compact
```

Run specific test file:
```bash
php artisan test --compact tests/Feature/ExampleTest.php
```

Run tests with filter:
```bash
php artisan test --compact --filter=testName
```

### Code Quality

Format PHP code:
```bash
vendor/bin/pint --dirty
```

Format JavaScript/Vue code:
```bash
npm run format
```

Lint JavaScript/Vue code:
```bash
npm run lint
```

## License

MIT
