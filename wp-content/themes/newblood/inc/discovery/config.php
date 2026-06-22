<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * All configured discovery instances, keyed by URL slug.
 * A new client = a new entry here. OHDBalt is instance #1.
 */
function nb_discovery_instances(): array {
    return array(
        'overhead-door' => array(
            'slug'        => 'overhead-door',
            'client_name' => 'Overhead Door Company of Baltimore',
            'logo'        => '', // root-relative path once a logo asset is placed; '' hides it
            'recipient'   => 'joms@newblood.com',
            'welcome'     => array(
                'title' => 'Let’s build your plan around you',
                'intro' => 'Thank you for the chance to put together the full picture. The questions below take about 10 minutes — your answers shape a plan built around Overhead Door, not a template.',
            ),
            'services' => array(
                array( 'key' => 'website',          'group' => 'get_found', 'label' => 'Website design & user experience',        'hint' => 'How the site looks, feels, and guides visitors.' ),
                array( 'key' => 'seo_aeo',          'group' => 'get_found', 'label' => 'Search & AI-answer visibility (SEO/AEO)', 'hint' => 'Being found in Google search and in AI answers like ChatGPT.' ),
                array( 'key' => 'brand_creative',   'group' => 'get_found', 'label' => 'Brand & creative',                        'hint' => 'Logo, photography, and video that present the brand well.' ),
                array( 'key' => 'lead_capture',     'group' => 'convert',   'label' => 'Lead capture & conversion',               'hint' => 'Turning visitors into inquiries — forms, funnels, calls-to-action.' ),
                array( 'key' => 'reviews',          'group' => 'convert',   'label' => 'Reviews & online reputation',             'hint' => 'Earning, showcasing, and responding to reviews.' ),
                array( 'key' => 'content',          'group' => 'convert',   'label' => 'Content',                                 'hint' => 'Service pages, FAQs, and fresh content over time.' ),
                array( 'key' => 'hosting_security', 'group' => 'operate',   'label' => 'Hosting, security & maintenance',         'hint' => 'Keeping the site fast, online, secure, and up to date.' ),
                array( 'key' => 'crm',              'group' => 'operate',   'label' => 'CRM / customer & job pipeline',           'hint' => 'One place to track customers and jobs from inquiry to close.' ),
                array( 'key' => 'customer_comms',   'group' => 'operate',   'label' => 'Customer communication',                  'hint' => 'Following up with leads and customers by email and text.' ),
                array( 'key' => 'automation_ai',    'group' => 'operate',   'label' => 'Automation & AI assistants',              'hint' => 'Automated routing and on-site AI chat that answers and books.' ),
                array( 'key' => 'lead_gen',         'group' => 'grow',      'label' => 'Lead generation',                         'hint' => 'Driving new prospects through paid search and social ads.' ),
                array( 'key' => 'reporting',        'group' => 'grow',      'label' => 'Reporting & analytics',                   'hint' => 'Clear reporting on what’s working and what it’s producing.' ),
            ),
            'goal_vectors' => array(
                array( 'key' => 'residential_commercial', 'left' => 'More residential',     'right' => 'More commercial' ),
                array( 'key' => 'leads_volume_quality',   'left' => 'More leads (volume)',  'right' => 'Better leads (quality)' ),
                array( 'key' => 'topline_lean',           'left' => 'Grow the top line',    'right' => 'Run leaner' ),
                array( 'key' => 'defend_expand',          'left' => 'Defend our territory', 'right' => 'Expand into new areas' ),
                array( 'key' => 'handson_managed',        'left' => 'We stay hands-on',     'right' => 'Fully managed for us' ),
            ),
            'timeline_options' => array( 'As soon as possible', 'Within 1–3 months', '3–6 months', 'Just exploring' ),
            'section_copy' => array(
                'priorities' => array( 'What matters most', 'Rate how important each capability is to you. Where it’s critical, we’ll ask how well it’s handled today.' ),
                'goals'      => array( 'Where you’re headed', 'A few questions about the direction of the business.' ),
                'systems'    => array( 'What’s in place today', 'Light context on your current systems — a sentence each is plenty.' ),
                'direction'  => array( 'Direction & timing', 'How you’re thinking about this work.' ),
                'open'       => array( 'Anything else', 'The floor is yours.' ),
            ),
        ),
    );
}

/**
 * Look up one instance by slug. Returns null if unknown.
 */
function nb_discovery_get_instance( $slug ): ?array {
    $all = nb_discovery_instances();
    return isset( $all[ $slug ] ) ? $all[ $slug ] : null;
}

/**
 * Ordered capability clusters for the priorities section. Key => display label.
 */
function nb_discovery_service_groups(): array {
    return array(
        'get_found' => 'Get found',
        'convert'   => 'Convert',
        'operate'   => 'Operate',
        'grow'      => 'Grow',
    );
}
