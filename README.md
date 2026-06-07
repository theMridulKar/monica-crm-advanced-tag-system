<p align="center">

![Monica’s Logo](https://user-images.githubusercontent.com/61099/242266547-63d98bd9-35f3-4dfe-92f4-a4a8dd75aa5c.png)

</p>
<h1 align="center">Document your life</h1>


## Monica is an open source personal relationship management system, that lets you document your life.

## Monica CRM: Tag System Extension

**1. Architecture & Approach**

- **Design Pattern:** Implemented Domain-Driven Design (DDD) with an AdvancedTagManager service layer.
- **Database:** Used a polymorphic taggables pivot table for seamless cross-model tagging.
**SQL Logic:** Achieved strict AND filtering using a single-statement Eloquent whereHas query (avoided application-level loops).

**2. Performance & Caching**

- **Redis Strategy:** Implemented 10-minute TTL caching for the /api/tags endpoint.
- **Auto-Invalidation:** Cache automatically clears on tag creation, updates, deletion, or attachment/detachment.

**3. Testing**
Implemented feature tests covering tag creation, filtering, deletion behavior, and cache invalidation.

## Laravel Sail Commands for Monica CRM

**1. Environment & Setup**

- **Copy Environment File:**
``cp .env.example.sail .env``
*  **Install Dependencies:**
``./vendor/bin/sail composer install``
- **Generate Application Key:**
``./vendor/bin/sail artisan key:generate``

**2. Database & Migrations**

*  **Run Migrations:**
``./vendor/bin/sail artisan migrate``

- **Seed Database:**
``./vendor/bin/sail artisan db:seed``
  
**3. Docker**
- **Up:**
``./vendor/bin/sail up -d``
- **Down:**
``./vendor/bin/sail down``
- **NPM Run:**
``./vendor/bin/sail npm install``


### Environment Configuration

Ensure your `.env` file includes these specific configurations to match the project's port requirements[cite: 1]:

```env
# User Permissions
WWWUSER=1000
WWWGROUP=1000
# Application Port
APP_PORT=8089
# Service Ports
FORWARD_DB_PORT=3307
FORWARD_REDIS_PORT=6380
FORWARD_MAILPIT_PORT=9099
FORWARD_MAILPIT_SMTP_PORT=2525
# URLs
APP_URL=http://localhost:8089
ASSET_URL=http://localhost:8089
VITE_PORT=5173
```


**Project Local URL:** http://localhost:8089
**Phpmyadmin Web View:** http://localhost:8081
**Test Command:** ``./vendor/bin/sail exec laravel.test php -d DB_CONNECTION=sqlite -d DB_DATABASE=:memory: vendor/bin/phpunit tests/Unit/Domains/Tag/ManageTags/Api/Controllers/TagControllerTest.php``