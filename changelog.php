# Patchlog - Notebook-BAR v1.3

## 🚀 Version 1.3 (Current)
**Release Date:**
<?php echo date("F j, Y"); ?>

### 🔧 Core System Logic
- **Database Schema Refactoring**:
- Renamed primary keys for consistency: `id` -> `user_id` (users), `note_id` (notes), `category_id` (categories).
- Normalized `pages` table to use composite key (`note_id`, `page_number`).
- **Data Access Layer**:
- Updated `includes/data_access.php` to align with new schema.
- Enhanced `save_note` logic to prevent duplication on updates.

### 👤 User Experience
- **Guest Mode Fixes**:
- Resolved `note_id` compatibility issues for guest sessions.
- Fixed category ID assignment for guest users.
- **Pinning Logic**:
- Fixed a bug where pinning a note created a duplicate blank note.
- **Navigation**:
- Improved redirect logic when opening invalid or missing notes.

### 🔒 Security & Admin
- **User Verification**: Updated login and registration to use strict ID checks.
- **Admin Panel**: synchronized admin dashboard, user actions, and migration tools with new schema.

---

## 📅 Version 1.2
- **Instant Pagination**: Client-side page switching without reloads.
- **Enhanced Editor**: 1,800 char limit, Tab support, Live counters.
- **Smart Saving**: Bulk save for all pages.

## 📅 Version 1.1
- **Multi-Page Support**: Split long notes into pages.
- **Guest Mode**: Try before you sign up.
- **Security Word**: Password recovery mechanism.