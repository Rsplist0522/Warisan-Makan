# WarisanMakan – Culinary Heritage Tourism System

A web-based Progressive Web Application (PWA) built with **Laravel** to preserve, promote, and revitalize Malaysia's culinary heritage in line with the Visit Malaysia 2026 (VM2026) campaign.

**Course:** BMSE3004 Collaborative Development
**Programme:** RSW
**Practical Group:** RSW2S3G1
**Tutor:** Mr Muhammad Irsyad Bin Kamil Riadz

## Team Members

| Name | Role | Module Assigned |
|---|---|---|
| Chang Hui Yee | Development Lead | Blind Box Recommendation Module |
| Chua Yee Teng | Testing & Documentation Lead | Food Passport & Achievement Module |
| Edmund Teh Wei Han | Project Manager | Heritage Shop Tracking Module |
| Mok Chun Bing | Requirements Lead | Community Contribution & Vendor Submission Module |
| Soon Yen Ling | Design Lead | User Management Module |
| Tang Le Yi | Testing & Documentation Lead | Food Trail & Navigation Module |

## About the Project

WarisanMakan is an integrated digital platform that documents traditional food vendors, their family recipes, and cultural stories, while helping tourists discover authentic heritage food through gamified exploration, curated food trails, and an AI heritage assistant.

**UN SDG Alignment:** Goal 11 – Sustainable Cities and Communities (Target 11.4: protect and safeguard cultural and natural heritage).

## Tech Stack

- **Type of System:** Web-based Progressive Web Application (PWA)
- **Backend Framework:** Laravel (PHP)
- **Database:** MySQL (via Eloquent ORM)
- **Frontend:** Blade Templates
- **Version Control:** Git & GitHub

## Key Modules

1. **User Management Module** – registration, login, profile, preferences, auto-translation, account management
2. **Heritage Shop Tracking Module** – shop listing, search, shop profiles, founder/generation info, heritage stories
3. **Food Passport & Achievement Module** – GPS check-ins, badges, leaderboards, social sharing
4. **Food Trail & Navigation Module** – trail generation, map view, navigation routes, favourites
5. **Community Contribution & Vendor Submission Module** – vendor story submissions, media upload, moderation workflow

## Getting Started

### Prerequisites
- PHP >= 8.2
- Composer
- Node.js & NPM
- MySQL
- Git

### Installation

```bash
# Clone the repository
git clone https://github.com/<your-username>/warisan-makan.git
cd warisan-makan

# Install PHP dependencies
composer install

# Install JS dependencies
npm install

# Copy environment file and generate app key
cp .env.example .env
php artisan key:generate

# Configure your database in .env, then run migrations
php artisan migrate

# Build frontend assets
npm run dev

# Serve the application
php artisan serve
```

## Branching Convention

- `main` – stable, production-ready code
- `dev` – active development integration branch
- `feature/<module-name>` – individual module development (e.g. `feature/food-passport`)

## Project Timeline

Development runs from 18 June 2026 to 17 September 2026, covering proposal, requirements analysis, system design, module development, integration, testing, and final presentation. See the project Gantt chart in Appendix A of the proposal document for full details.

## License

This project is developed for academic purposes as part of the BMSE3004 Collaborative Development course.
