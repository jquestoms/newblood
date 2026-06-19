<?php
/**
 * New Blood Discovery module bootstrap.
 * Self-serve, config-driven client discovery form. See
 * docs/superpowers/specs/2026-06-19-newblood-discovery-form-design.md
 */
if ( ! defined( 'ABSPATH' ) ) exit;

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/routing.php';
require_once __DIR__ . '/view.php';
require_once __DIR__ . '/controller.php';
require_once __DIR__ . '/submission.php';
// Later tasks append: email.php
