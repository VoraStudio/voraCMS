# Admin Spec — VoraCMS

## Requirements

### Requirement: Minimalist Corporate Action Buttons
The admin CRUD action buttons (toggle, edit, delete) MUST use a minimalist corporate style: transparent background, no borders, no glass/blur, muted neutral icon color by default, semantic color + subtle background only on hover. Toggle active MUST show a green tint by default to indicate ON state.

### Requirement: Visible Inactive Rows
Inactive user/project rows MUST be clearly distinguishable: row opacity reduced to 0.55, red-tinted background rgba(239,68,68,0.10) in dark (0.12 in light), cell content faded to opacity 0.20.

### Requirement: Reduced Projects Button Glow
The "Obrir projecte" button MUST have a subtle glow: box-shadow reduced from extreme values (60px/120px/200px) to moderate (24px default, 48px hover).

### Requirement: Projects Grouped by Client
The projects listing page MUST display projects grouped by user (client). Each group shows a user header (avatar, name, email, project count) followed by a table of their projects. Each row MUST be clickable and navigate to admin_switch_project.

### Requirement: Dashboard Latest Records
The admin dashboard MUST display 3 columns with the latest 5 users, 5 projects, and 5 content types. Each item MUST link to its respective management page.

### Requirement: Separated Button Styles in Style Guide
The styles.md MUST clearly separate icon buttons (minimalist corporate) from text buttons (neon frame) and form submit buttons.
