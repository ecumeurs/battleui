# BattleUI: UpsilonBattle Tactical Frontend

**BattleUI** is the player-facing interface for **UpsilonBattle**, a turn-based Tactical RPG. It provides the visual environment for combat, character management, and matchmaking.

Built with **Laravel**, **Vue.js**, and **Tailwind CSS**, it serves as the orchestration layer between the player and the core game engine.

## Getting Started

To run the complete BattleUI environment, you need to start three separate services. Ensure you are in the `battleui` directory before running these commands.

### 1. Start the Laravel API
The Laravel backend handles authentication, matchmaking queues, and session persistence.
```bash
php artisan serve
```

### 2. Start the Reverb Server
**Reverb** provides high-performance WebSocket communication, enabling real-time game state updates from the backend to the frontend.
```bash
php artisan reverb:start
```

### 3. Start the Vue.js Frontend
The frontend uses **Vite** for fast development and hot module replacement.
```bash
npm run dev
```

## Project Structure

This project is a hybrid application combining a Laravel backend with an Inertia.js-driven Vue.js frontend.

### Frontend (Vue.js)
The visual interface and player interactions are located in:
- **[resources/js/Pages](file:///workspace/battleui/resources/js/Pages)**: Main page views.
    - **Welcome.vue**: The public landing page showcasing the Upsilon Battle world and providing entry points for survivors.
    - **Auth/Login.vue**: A secure authentication interface for survivors to identify themselves using their credentials.
    - **Auth/Register.vue**: The entity initialization portal for creating accounts and generating initial character rosters.
    - **Dashboard.vue**: The tactical command hub for roster review and matchmaking (Under Construction).
- **[resources/js/Components](file:///workspace/battleui/resources/js/Components)**: Reusable UI elements.
- **[resources/js/Layouts](file:///workspace/battleui/resources/js/Layouts)**: Wrapper components for consistent UI structure.

### Backend (Laravel)
The core logic, API endpoints, and data management are located in:
- **[app/Http/Controllers](file:///workspace/battleui/app/Http/Controllers)**: Handles request routing and business logic.
- **[app/Services](file:///workspace/battleui/app/Services)**: Contains specialized logic like the `UpsilonApiService` for game engine communication.
- **[routes](file:///workspace/battleui/routes)**: Defines the web and API entry points.

## Architecture & Integration

BattleUI acts as a bridge between the persistent database and the stateless game logic engine.

### Database Connection
- **PostgreSQL**: BattleUI connects to a PostgreSQL instance (configured in `.env`) to store user accounts, character stats, match histories, and leaderboard rankings. It manages the long-term state of the game world.

### Upsilon API Relationship
- **Game Engine (Go)**: While BattleUI handles the metadata and sessions, the actual "brain" of combat resides in the **Upsilon API** (Go backend, running on port **8081**).
- **Communication**: BattleUI communicates with the Upsilon API via the `UpsilonApiService`. It sends match initiation requests and proxies player actions (move, attack, pass) to the engine.
- **Traceability**: All interactions follow the ATD (Atomic Traceable Documentation) framework, specifically implementing:
    - `@spec-link [[api_go_battle_engine]]`
    - `@spec-link [[api_go_battle_start]]`
    - `@spec-link [[api_go_battle_action]]`
- **Standardized Payloads**: Communication with the engine is wrapped in the `[[api_standard_envelope]]` format for consistent request tracking and logging across systems.

---
*Note: The Upsilon API is typically found at `http://localhost:8081/internal`. Ensure it's running prior starting a battle.* 
