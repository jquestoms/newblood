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
            'systems_questions' => array(
                array( 'key' => 'crm',             'label' => 'Do you use a CRM today? If so, which one?',            'short' => 'CRM today',            'type' => 'text' ),
                array( 'key' => 'lead_handling',   'label' => 'When a web lead comes in today, what happens?',        'short' => 'Lead handling today',  'type' => 'textarea', 'rows' => 3 ),
                array( 'key' => 'leads_per_month', 'label' => 'Roughly how many web leads a month right now?',        'short' => 'Web leads / month',    'type' => 'text',
                       'hint' => "Ballpark is fine — and if you\u{2019}re not tracking this yet, just say so." ),
                array( 'key' => 'reviews_system',  'label' => 'Your reviews live in which system?',                   'short' => 'Reviews system',       'type' => 'text' ),
                array( 'key' => 'call_tracking',   'label' => 'Any call-tracking / attribution in place? (e.g., Enspire)', 'short' => 'Call tracking',   'type' => 'text' ),
                array( 'key' => 'gbp_access',      'label' => 'Can you grant manager access to your Google Business Profile?', 'short' => 'Google Business Profile access', 'type' => 'radio',
                       'options' => array( 'yes' => 'Yes', 'no' => 'No', 'unsure' => 'Not sure' ), 'default' => 'unsure' ),
                array( 'key' => 'territories',     'label' => 'Which locations / territories should the plan cover?', 'short' => 'Territories',          'type' => 'textarea', 'rows' => 2 ),
            ),
            'section_copy' => array(
                'priorities' => array( 'What matters most', 'Rate how important each capability is to you. Where it’s critical, we’ll ask how well it’s handled today.' ),
                'goals'      => array( 'Where you’re headed', 'A few questions about the direction of the business.' ),
                'systems'    => array( 'What’s in place today', 'Light context on your current systems — a sentence each is plenty.' ),
                'direction'  => array( 'Direction & timing', 'How you’re thinking about this work.' ),
                'open'       => array( 'Anything else', 'The floor is yours.' ),
            ),
        ),
        'calindman' => array(
            'slug'        => 'calindman',
            'client_name' => 'C.A. Lindman, Inc.',
            'logo'        => '/wp-content/themes/newblood/assets/img/discovery-calindman.png',
            'recipient'   => 'joms@newblood.com',
            'welcome'     => array(
                'title' => 'Let’s build this around C.A. Lindman',
                'intro' => 'Thank you for the chance to help shape what’s next for C.A. Lindman online. The questions below take about 10 minutes — your answers shape a plan built around CAL and CRW, not a template.',
            ),
            'services' => array(
                array( 'key' => 'website',          'group' => 'get_found', 'label' => 'Website design & user experience',        'hint' => 'How the site looks, feels, and guides visitors.' ),
                array( 'key' => 'seo_aeo',          'group' => 'get_found', 'label' => 'Search & AI-answer visibility (SEO/AEO)', 'hint' => 'Being found in Google search and in AI answers like ChatGPT.' ),
                array( 'key' => 'brand_creative',   'group' => 'get_found', 'label' => 'Brand & creative',                        'hint' => 'Project photography and video that present the company well.' ),
                array( 'key' => 'portfolio',        'group' => 'convert',   'label' => 'Project portfolio & case studies',        'hint' => 'Showcasing completed work — galleries, before/after, project stories.' ),
                array( 'key' => 'lead_capture',     'group' => 'convert',   'label' => 'Lead capture & conversion',               'hint' => 'Turning visitors into project inquiries and RFPs.' ),
                array( 'key' => 'reviews',          'group' => 'convert',   'label' => 'Reviews, reputation & references',        'hint' => 'Earning and showcasing reviews and client references.' ),
                array( 'key' => 'hosting_security', 'group' => 'operate',   'label' => 'Hosting, security & maintenance',         'hint' => 'Keeping the site fast, online, secure, and up to date.' ),
                array( 'key' => 'crm',              'group' => 'operate',   'label' => 'CRM / bid & project pipeline',            'hint' => 'One place to track opportunities from inquiry to close.' ),
                array( 'key' => 'customer_comms',   'group' => 'operate',   'label' => 'Client communication',                    'hint' => 'Following up with prospects and clients by email and text.' ),
                array( 'key' => 'recruiting',       'group' => 'grow',      'label' => 'Recruiting & careers',                    'hint' => 'Attracting field talent and making hiring easier.' ),
                array( 'key' => 'lead_gen',         'group' => 'grow',      'label' => 'Lead generation',                         'hint' => 'Driving new prospects through paid search and social ads.' ),
                array( 'key' => 'reporting',        'group' => 'grow',      'label' => 'Reporting & analytics',                   'hint' => 'Clear reporting on what’s working and what it’s producing.' ),
            ),
            'goal_vectors' => array(
                array( 'key' => 'volume_fit',      'left' => 'More project volume',     'right' => 'Better-fit projects' ),
                array( 'key' => 'deepen_expand',   'left' => 'Deepen current markets',  'right' => 'Expand into new regions' ),
                array( 'key' => 'cal_crw',         'left' => 'Focus on CAL',            'right' => 'One plan across CAL + CRW' ),
                array( 'key' => 'topline_lean',    'left' => 'Grow the top line',       'right' => 'Run leaner' ),
                array( 'key' => 'handson_managed', 'left' => 'We stay hands-on',        'right' => 'Fully managed for us' ),
            ),
            'timeline_options' => array( 'As soon as possible', 'Within 1–3 months', '3–6 months', 'Just exploring' ),
            'systems_questions' => array(
                array( 'key' => 'pipeline_tracking', 'label' => 'How do you track leads, bids, and projects today? (CRM, spreadsheets, something else)', 'short' => 'Pipeline tracking today', 'type' => 'text' ),
                array( 'key' => 'lead_handling',     'label' => 'When a project inquiry comes in through the website today, what happens?',              'short' => 'Inquiry handling today',  'type' => 'textarea', 'rows' => 3 ),
                array( 'key' => 'lead_sources',      'label' => 'Where do most new project opportunities come from today? (referrals, repeat clients, search, bid lists…)', 'short' => 'Where opportunities come from', 'type' => 'text' ),
                array( 'key' => 'photo_library',     'label' => 'Where do project photos and job documentation live today?',                             'short' => 'Project photo library',   'type' => 'text' ),
                array( 'key' => 'gbp_access',        'label' => 'Can you grant manager access to your Google Business Profile(s)?',                      'short' => 'Google Business Profile access', 'type' => 'radio',
                       'options' => array( 'yes' => 'Yes', 'no' => 'No', 'unsure' => 'Not sure' ), 'default' => 'unsure' ),
                array( 'key' => 'coverage',          'label' => 'Which companies and locations should this plan cover? (C.A. Lindman, CRW, both)',       'short' => 'Coverage (CAL / CRW)',    'type' => 'textarea', 'rows' => 2 ),
            ),
            'section_copy' => array(
                'priorities' => array( 'What matters most', 'Rate how important each capability is to you. Where it’s critical, we’ll ask how well it’s handled today.' ),
                'goals'      => array( 'Where you’re headed', 'A few questions about the direction of the business.' ),
                'systems'    => array( 'What’s in place today', 'Light context on your current systems — a sentence each is plenty.' ),
                'direction'  => array( 'Direction & timing', 'How you’re thinking about this work.' ),
                'open'       => array( 'Anything else', 'The floor is yours.' ),
            ),
        ),
        'mdproperties' => array(
            'slug'        => 'mdproperties',
            'client_name' => 'Maryland Properties',
            'logo'        => '/wp-content/themes/newblood/assets/img/discovery-mdproperties.png',
            'recipient'   => 'joms@newblood.com',
            'welcome'     => array(
                'title' => 'Let’s build this around Maryland Properties',
                'intro' => 'Thank you for the chance to help shape the new mdpropertiesllc.com. The questions below take about ten minutes, and most are one tap. Your answers shape a site built around how you actually buy houses, not a template.',
            ),
            // Optional per-instance copy (house rule: no em dashes in client-facing text). Other instances keep the module defaults.
            'skip'     => 'Answer what you can. Skip anything that doesn’t apply, and we’ll fill the gaps when we talk.',
            'thankyou' => 'We’ll read every answer and build the plan around them. Jeremy will be in touch to walk you through it.',
            'services' => array(
                array( 'key' => 'situations',       'group' => 'get_found', 'label' => 'Speaking to each seller’s situation',           'hint' => 'A page for each reason someone calls you: an inherited house, behind on payments, tenants, a divorce, a move, more repairs than the house is worth.' ),
                array( 'key' => 'local_expert',     'group' => 'get_found', 'label' => 'Showing you know Maryland, county by county',    'hint' => 'Pages for the counties and towns you buy in, with the kinds of houses you see there.' ),
                array( 'key' => 'seo_aeo',          'group' => 'get_found', 'label' => 'Being found in Google and in AI answers',         'hint' => 'Showing up when someone in Howard County searches “sell my house as-is” or asks ChatGPT who buys houses for cash.' ),
                array( 'key' => 'show_work',        'group' => 'convert',   'label' => 'Showing your work',                              'hint' => 'Houses you bought as-is and brought back, room by room, with the photos to prove it.' ),
                array( 'key' => 'offer_math',       'group' => 'convert',   'label' => 'Explaining how the offer is figured',            'hint' => 'The math behind a cash offer, with numbers a seller can move around and see for themselves.' ),
                array( 'key' => 'easy_contact',     'group' => 'convert',   'label' => 'Easy ways to reach you',                         'hint' => 'A two-minute offer form, a call button on phones, a contact page, and a way for someone to send you a house.' ),
                array( 'key' => 'lead_inbox',       'group' => 'operate',   'label' => 'Seeing your leads in one place',                 'hint' => 'Every request on one screen: where it came from, where it stands, what you offered, what it closed at. No CRM to buy.' ),
                array( 'key' => 'self_updates',     'group' => 'operate',   'label' => 'Adding a house from your phone',                 'hint' => 'Posting a new project and its photos yourself, with no web person in the loop.' ),
                array( 'key' => 'followup',         'group' => 'operate',   'label' => 'Fast follow-up with sellers',                    'hint' => 'The seller hears back right away, knows when to expect the offer, and nothing slips through the cracks.' ),
                array( 'key' => 'hosting_security', 'group' => 'operate',   'label' => 'Hosting, security & maintenance',                'hint' => 'The site stays fast, online, secure and up to date, and you never have to think about it.' ),
                array( 'key' => 'trust_brand',      'group' => 'grow',      'label' => 'Your story, and one consistent look',            'hint' => 'Who you are, what sellers say about you, and one look that carries across the website, Google, Facebook, signs and cards.' ),
                array( 'key' => 'referrals',        'group' => 'grow',      'label' => 'Referrals and introductions',                    'hint' => 'Making it easy for a neighbor, an attorney or a past seller to send you a house, and knowing who sent it.' ),
            ),
            'goal_vectors' => array(
                array( 'key' => 'volume_fit',         'left' => 'More houses a year',                 'right' => 'Better-fit houses' ),
                array( 'key' => 'deepen_expand',      'left' => 'Deepen the counties I know',         'right' => 'Reach new counties' ),
                array( 'key' => 'handson_managed',    'left' => 'I update the site myself',           'right' => 'New Blood runs it for me' ),
                array( 'key' => 'polished_plain',     'left' => 'Polished',                           'right' => 'Plain-spoken' ),
                array( 'key' => 'real_stock',         'left' => 'Photos of houses we bought',         'right' => 'Stock photos' ),
                array( 'key' => 'numbers_story',      'left' => 'Numbers up front',                   'right' => 'Story first' ),
                array( 'key' => 'formal_neighborly',  'left' => 'Formal',                             'right' => 'Neighborly' ),
                array( 'key' => 'modern_traditional', 'left' => 'Modern',                             'right' => 'Traditional' ),
                array( 'key' => 'quiet_bold',         'left' => 'Quiet colors (navy, gold, cream)',   'right' => 'Bold colors (bright, high contrast)' ),
            ),
            'timeline_options' => array( 'As soon as possible', 'Within 1 to 3 months', '3 to 6 months', 'Just exploring' ),
            'systems_questions' => array(
                array( 'key' => 'lead_sources',    'label' => 'Where do most of your houses come from today? (signs, referrals, the website, mailers, word of mouth)', 'short' => 'Where houses come from',  'type' => 'text' ),
                array( 'key' => 'lead_handling',   'label' => 'When someone fills out the form on your website today, what happens next?',                            'short' => 'Lead handling today',     'type' => 'textarea', 'rows' => 3 ),
                array( 'key' => 'leads_per_month', 'label' => 'Roughly how many sellers a month reach you through the website right now?',                            'short' => 'Web leads / month',       'type' => 'text',
                       'hint' => 'Ballpark is fine. If you’re not tracking it, just say so.' ),
                array( 'key' => 'callback_speed',  'label' => 'How fast do you usually get back to a seller?',                                                          'short' => 'Callback speed',          'type' => 'radio',
                       'options' => array( 'hour' => 'Within the hour', 'same_day' => 'Same day', 'day' => 'Within 24 hours', 'varies' => 'It varies' ), 'default' => 'varies' ),
                array( 'key' => 'crm',             'label' => 'Where do your leads live today? (a CRM, a spreadsheet, your phone, your inbox)',                        'short' => 'Where leads live',        'type' => 'text' ),
                array( 'key' => 'gbp_access',      'label' => 'Do you have the login for your Google Business Profile? (the listing that shows on Google Maps)',       'short' => 'Google Business Profile access', 'type' => 'radio',
                       'options' => array( 'yes' => 'Yes', 'no' => 'No', 'unsure' => 'Not sure' ), 'default' => 'unsure' ),
                array( 'key' => 'counties',        'label' => 'Which counties do you actually buy in today? And is Washington, DC still a yes?',                        'short' => 'Counties bought in',      'type' => 'textarea', 'rows' => 2 ),
                array( 'key' => 'phones',          'label' => 'Which phone number should sellers call? (the old site shows 443-257-0017 and 410-744-0287)',             'short' => 'Phone to publish',        'type' => 'text' ),
                array( 'key' => 'feedback_mode',   'label' => 'When you look at a draft of the new site, how would you rather leave notes?',                           'short' => 'How to leave feedback',   'type' => 'radio',
                       'options' => array( 'onpage' => 'Tap the page and type right there', 'email' => 'Email', 'call' => 'A phone call', 'any' => 'Whatever is easiest' ), 'default' => 'any' ),
            ),
            'section_copy' => array(
                'priorities' => array( 'What matters most', 'Tap how important each one is to you. Where it’s important, we’ll ask how well the current site handles it today.' ),
                'goals'      => array( 'Where you’re headed', 'A few quick picks about the business, then how the new site should feel. No preference is a fine answer.' ),
                'systems'    => array( 'What’s in place today', 'Light context on how sellers reach you now. A few words each is plenty.' ),
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
