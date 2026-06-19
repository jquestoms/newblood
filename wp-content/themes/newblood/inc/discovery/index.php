<?php
/**
 * New Blood Discovery module bootstrap.
 * Self-serve, config-driven client discovery form. See
 * docs/superpowers/specs/2026-06-19-newblood-discovery-form-design.md
 */
if ( ! defined( 'ABSPATH' ) ) exit;

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
// Later tasks append: routing.php, controller.php, submission.php, email.php
