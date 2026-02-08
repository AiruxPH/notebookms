AntiGravity Refactoring Prompt
Objective: Refactor the PHP codebase to align with the new database schema where primary keys have been renamed and the pages table structure has been normalized.

Database Schema Changes:

1. Table users: Primary key id is now user_id.

2. Table notes: Primary key id is now note_id.

3. Table categories: Primary key id is now category_id.

4. Table pages:

    The standalone id column has been removed.

    Records are now uniquely identified by the combination of note_id and page_number.

Refactoring Requirements:

1. SQL Query Updates: Update all SELECT, INSERT, UPDATE, and DELETE statements across all files to use the new column names: user_id, note_id, and category_id.

2. Associative Array Keys: Update all PHP code that accesses database results to use the new keys (e.g., change $row['id'] to $row['note_id'], $row['user_id'], or $row['category_id'] depending on the table).

3. Page Logic Migration:

    Update functions like save_note_page(), get_note_page(), and get_note_page_count() to identify records using WHERE note_id = ? AND page_number = ? instead of a single id.

    Remove any logic that expects an auto-incrementing ID from the pages table.

4. Category Logic:

    In get_categories(), ensure the query correctly fetches records where user_id matches the current user OR equals 0 (the system user for global defaults).

5. Session & Variable Consistency:

    Ensure $_SESSION['user_id'], $_POST['note_id'], and $_GET['id'] handling is consistent with these new database field names.

6. Admin Panel Synchronization: Update @admin/dashboard.php, @admin/user_action.php, and @AJAX files (like @ajax_notes.php) to use the renamed user_id and note_id fields for user management and note moderation.

Priority Files for Refactoring:

1. @includes/data_access.php

2. @notepad.php

3. @admin/dashboard.php

4. @login.php & @register.php