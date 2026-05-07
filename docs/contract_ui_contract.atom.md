---
id: contract_ui_contract
status: STABLE
type: CONTRACT
version: 1.0
layer: BUSINESS
priority: 1
tags: [governance, contract, ui]
parents:
  - [[shared:contract_upsilon_contract]]
dependents: []
human_name: BattleUI Contract
---

# New Atom

## INTENT
Establish the architectural standards and integration rules for the BattleUI Laravel/Vue application.

## THE RULE / LOGIC
- **State Management:** Authoritative player data must reside in the database; game session state is delegated to UpsilonAPI.
- **Auth:** Must implement secure JWT issuance and handle session lifecycle according to `[[module_frontend_session_management]]`.
- **Integration:** All engine interactions must go through the UpsilonAPI bridge via standard DTOs.
- **Frontend Quality:** Maintain component-based architecture in Vue, ensuring high comment density and ATD traceability.
- **Responsiveness:** Support multiple resolutions while maintaining tactical grid legibility.

## TECHNICAL INTERFACE
- **Code Tag:** `@spec-link [[ui_contract]]`
- **Related Atoms:** `[[module_frontend_session_management]]`, `[[shared:upsilon_contract]]`

## EXPECTATION
