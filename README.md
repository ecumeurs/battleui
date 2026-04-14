# BattleUI: UpsilonBattle Tactical Frontend

**BattleUI** is the player-facing interface for **UpsilonBattle**, a turn-based Tactical RPG. It provides the visual environment for combat, character management, and matchmaking.

Built with **Laravel**, **Vue.js**, and **Tailwind CSS**, it serves as the orchestration layer between the player and the core game engine.

## Getting Started

To run the complete BattleUI environment, you need to start three separate services. Ensure you are in the `battleui` directory before running these commands.

### 1. Start the Laravel API (Port 8000)
The Laravel backend handles authentication, matchmaking queues, and session persistence.
```bash
php artisan serve
```

### 2. Start the Reverb Server (Port 8080)
**Reverb** provides high-performance WebSocket communication, enabling real-time game state updates from the backend to the frontend.
```bash
php artisan reverb:start
```

### 3. Start the Vue.js Frontend (Port 5173)
The frontend uses **Vite** for fast development and hot module replacement.
### 4. Administrative Setup (Seeding)
To establish the initial system administrator, you must define a password and run the database seeder.
```bash
export ADMIN_INITIAL_PASSWORD="your_secure_password"
php artisan db:seed
```
This will create a default administrator at `admin@admin.com` with the `Admin` role.

#### Competitive Feed Population (Leaderboard Seeding)
To populate the system with realistic tactical telemetry for testing the competitive feed:
```bash
php artisan db:seed --class=LeaderboardSeeder
```
This seeder generates:
- **200 Combatants**: Unique user accounts with randomized names and credentials.
- **400 Matches**: 100 historical matches per battle mode (`1v1_PVP`, `2v2_PVP`, `1v1_PVE`, `2v2_PVE`).
- **Temporal Alignment**: All matches are conclused within the current weekly cycle (starting Sunday 00:01 UTC) to ensure the leaderboard is populated for active session testing.

> [!NOTE]
> **Production Deployment:** In a production environment, you don't run the Vite development server. Instead, you run `npm run build` once, and the resulting static assets are served directly by the Laravel API (via Apache, Nginx, or similar).

## Project Structure

This project is a hybrid application combining a Laravel backend with an Inertia.js-driven Vue.js frontend.

### Frontend (Vue.js)
The visual interface and player interactions are located in:
- **[resources/js/Pages](file:///workspace/battleui/resources/js/Pages)**: Main page views.
    - **Welcome.vue**: The public landing page showcasing the Upsilon Battle world and providing entry points for survivors.
    - **Auth/Login.vue**: A secure authentication interface for survivors to identify themselves using their credentials.
    - **Auth/Register.vue**: The entity initialization portal for creating accounts and generating initial character rosters.
    - **Dashboard.vue**: The tactical command hub for roster review, matchmaking, and identity management.
    - **BattleArena.vue**: The real-time combat theater driven by the Go engine via WebSockets.
- **[resources/js/Components](file:///workspace/battleui/resources/js/Components)**: Reusable UI elements.
    - **TacticalHeader.vue**: The top navigation and session management bridge.
    - **TacticalFooter.vue**: Terminal-style status bar for system telemetry.
    - **CharacterRoster.vue**: Modular management interface for character stats and progression.
    - **IdentitySection.vue**: Modular component for managing user personal data, credentials, and GDPR controls.
    - **ModalBox.vue**: Themed base component for "Neon in the Dust" interactive dialogs and portals.
    - **LeaderboardComponent.vue**: Modular competitive feed displaying global rankings, categorical splits (1v1/2v2), and the current user's neural signature.
    - **Modals/EditIdentityModal.vue**: Interactive portal for synchronizing user identity data (nickname, email, address).
    - **Modals/EditIdentityModal.vue**: Interactive portal for synchronizing user identity data (nickname, email, address).
    - **Modals/ChangePasswordModal.vue**: Secure credential rotation interface for updating authentication keys.
- **[resources/js/Components/Arena](file:///workspace/battleui/resources/js/Components/Arena)**: Core battle UI mechanics.
    - **IsoBoardGrid.vue**: The scalable 2:1 isometric grid mapped to engine coordinates.
    - **ActionPanel.vue**: State-driven execution panel to proxy command inputs directly back to the API.
    - **InitiativeTimeline.vue**: Visual representation of turn order dynamics.
    - **CharacterBattleCard.vue** & **CharacterPawn.vue**: On-board and off-board entity representations.
- **[resources/js/Layouts](file:///workspace/battleui/resources/js/Layouts)**: Wrapper components for consistent UI structure.
    - **TacticalLayout.vue**: The primary "Neon in the Dust" layout framework.
- **Core Frontend Logic**:
    - **[bootstrap.js](file:///workspace/battleui/resources/js/bootstrap.js)**: Initializes core browser-level utilities, including global Axios defaults and Laravel Echo.
    - **[app.js](file:///workspace/battleui/resources/js/app.js)**: Inertia.js entry point bootstrapping the Vue 3 application and plugin ecosystem.
    - **[services/auth.js](file:///workspace/battleui/resources/js/services/auth.js)**: Centralized authentication service and Axios interceptor for JWT management and token renewal.
    - **[services/game.js](file:///workspace/battleui/resources/js/services/game.js)**: Transport service proxying actions and Reverb WebSocket listeners to the Upsilon engine.

### Backend (Laravel)
The core logic, API endpoints, and data management are located in:
- **[app/Http/Controllers](file:///workspace/battleui/app/Http/Controllers)**: Handles request routing and business logic.
    - **API/LeaderboardController.php**: Manages competitive rankings with temporal filtering (weekly reset) and categorical mode aggregation.
- **[app/Services](file:///workspace/battleui/app/Services)**: Contains specialized logic like the `UpsilonApiService` for game engine communication.
- **[routes](file:///workspace/battleui/routes)**: Defines the web and API entry points.

## Testing & Verification
 
 For rapid verification of API flows without using a browser, the [UpsilonCLI](file:///workspace/upsiloncli) is the recommended tool. It allows you to:
 - **Simulate Player Journeys**: Run a full Register → Login → Matchmaking sequence interactively.
 - **Debug Matchmaking**: Join the queue as multiple users using separate terminal sessions to verify queue logic and `MatchFound` event delivery.
 - **Inspect Payloads**: Every action reveals the exact `curl` command and raw JSON response for deep troubleshooting.
 - **Automated Audits**: Run `upsiloncli --auto` to perform a smoke test of all primary user flows in seconds.
 
 ---
 
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
    - `@spec-link [[api_leaderboard]]`
- **Standardized Payloads**: ALL communication with the engine (HTTP and WebSocket) MUST be wrapped in the `[[api_standard_envelope]]` format for consistent request tracking and logging across systems.
- **Board State Standard**: Tactical payloads (returned by `GET /api/v1/game/{id}` and via `board.updated` WebSocket events) follow the standard envelope. For API, the board is in `data.game_state`. For WebSocket, the board is in `data`.

### API-First Playability
- **Requirement**: The system adheres to the `[[requirement_customer_api_first]]` standard, ensuring all game features are fully accessible via API.
- **Discovery**: A machine-readable `[[api_help_endpoint]]` is available at `/api/v1/help`, providing JSON-formatted documentation for every endpoint (Verb, URI, Intent, Input, and Output).
- **Synchronization**: API development and documentation are coupled. Any change to the Laravel API must be reflected in both the corresponding ATD atoms and the system help registry.

---
*Note: The Upsilon API is typically found at `http://localhost:8081/internal`. Ensure it's running prior starting a battle.*

**Keep this help endpoint and doc front in sync when updating laravel api endpoints.**
