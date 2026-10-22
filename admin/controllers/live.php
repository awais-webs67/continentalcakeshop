<?php
// Checked by every open admin page every few seconds: badge counts, a change marker (so lists
// can refresh themselves) and the newest order, message and custom request for pop-up notices.
$admin = require_admin();
session_write_close();
json_out(admin_live_state($admin));
