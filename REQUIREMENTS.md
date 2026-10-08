# Table Tennis Ranking & Match Scoring System - Requirements Specification

## 1. Overview
The **Table Tennis Ranking & Match Scoring System** is a mobile-first competitive web application designed to manage dynamic **Ranking Lists**, record official matches, log set-by-set scores, and dynamically maintain **ELO-based player rankings** across multiple customizable formats (Best of 3, Best of 5, Best of 7, and Doubles).

All leaderboards, matches, and player profiles are **publicly accessible** without login. Admins and Managers log into the management portal to configure ranking lists, register players, and officiate matches.

---

## 2. Member & Profile Data Model

### Member Attributes
- **Full Name** (Required)
- **Email Address** (Required)
- **Phone Number** (Required, **Hidden from public profiles**, visible only to Admins/Managers)
- **Address** (Optional / Required)
- **Date of Birth** (Optional)
- **Status** (`Active`, `Inactive`)
- **Avatar / Profile Photo** (Optional)

### Public vs. Management Access
- **Public (No Login Required):** Anyone can view all Ranking Lists, Leaderboards, Player Profiles (with phone hidden), Match Histories, Set Scores, and Head-to-Head records.
- **Admin / Manager Portal (Login Required):**
  - **Admins:** Manage user accounts, create/configure Ranking Lists, edit match records, configure system ELO settings.
  - **Managers:** Register members, initiate ranking matches, enter set-by-set scores using the mobile scorekeeper interface.
- *Note:* Members do not need to log in for Phase 1.

---

## 3. 📱 UI/UX Architecture: Mobile App-First Design

- **App-Like Navigation Shell:**
  - Fixed **Bottom Navigation Bar (Dock)** for mobile devices (Leaderboards, Matches, Player Profiles, Admin Login link).
  - Clean header with active Ranking List selector and search filter.
- **Mobile Scorekeeper Interface (For Managers):**
  - High-contrast, large touch-friendly buttons for fast set score updates right at the table.
  - Score steppers (+/- buttons), quick set completion confirmation.
- **Public Leaderboards & Cards:**
  - Mobile app style cards with rank badges (#1, #2, #3 podium icons), ELO rating pills, win streak badges, and player avatar graphics.
  - Public profile page displaying player stats, ELO chart, and match history (with phone number explicitly hidden).

---

## 4. Core Functional Modules

### Module 1: Admin-Managed Ranking Lists
- **Dynamic Creation:** Admins create independent Ranking Lists.
- **Configuration Parameters:**
  - **Title / Name:** (e.g. "Premier Best of 5 Singles", "Weekend Doubles Ladder")
  - **Match Type:** Singles or Doubles
  - **Set Rules:** Best of 3 (First to 2), Best of 5 (First to 3), Best of 7 (First to 4)
  - **Initial ELO & K-Factor:** Custom or default (e.g., Initial 1200, K-Factor 32)
  - **Status:** Active, Draft, or Archived

### Module 2: Mobile Match Scorekeeping Engine (Manager Access)
- Select active Ranking List and participating players/pairs.
- Large numerical steppers / inputs for fast score entry on mobile phones.
- Real-time score validation (11 points standard, win by 2).
- Automatic set & match winner detection.

### Module 3: ELO Rating Engine
- Standard ELO recalculation post-match:
  $$E_A = \frac{1}{1 + 10^{(R_B - R_A)/400}}$$
  $$R'_A = R_A + K \times (S_A - E_A)$$
- Audit log of pre-match ELO, post-match ELO, and rating diff ($\Delta ELO$) per player per match.

### Module 4: Public Leaderboards & Player Profiles
- Multi-list viewable leaderboards.
- Public player profile pages (showing name, email, address, DOB if set, ELO ratings, match history; phone number hidden).

---

## 5. Technology Stack & Database Schema Design

- **Backend:** Laravel 13.x (PHP 8.3+)
- **Admin & Manager Management Panel:** Filament v3
- **Public Mobile Frontend UI:** Tailwind CSS, Alpine.js, Blade Components (Mobile App Shell layout)
- **Database Schema Entities:**
  - `users` (id, name, email, password, role [`admin`, `manager`, `member`], phone, address, date_of_birth, status)
  - `ranking_lists` (id, title, type, format, initial_elo, k_factor, is_active)
  - `player_rankings` (id, ranking_list_id, user_id/pair_id, elo_rating, matches_played, wins, losses)
  - `matches` (id, ranking_list_id, player1_id, player2_id, pair1_id, pair2_id, winner_id, manager_id, status, p1_sets_won, p2_sets_won, played_at)
  - `match_sets` (id, match_id, set_number, p1_score, p2_score, winner_id)
  - `elo_histories` (id, ranking_list_id, match_id, user_id, old_elo, new_elo, elo_change)
