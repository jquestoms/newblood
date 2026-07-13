<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Render one segmented button group. $opts is an ordered array of [ value => label ].
 * $selected (string|null) pre-selects a button by value. $data_key (string|null),
 * when set, is emitted as data-key on the group so JS can identify it.
 */
function nb_discovery_seg( array $classes, array $opts, $aria_label, $selected = null, $data_key = null ) {
    $cls = implode( ' ', array_map( 'sanitize_html_class', $classes ) );
    $key_attr = ( $data_key !== null ) ? ' data-key="' . esc_attr( $data_key ) . '"' : '';
    echo '<div class="nb-d-seg ' . esc_attr( $cls ) . '"' . $key_attr . ' role="radiogroup" aria-label="' . esc_attr( $aria_label ) . '">';
    foreach ( $opts as $val => $label ) {
        $is = ( (string) $val === (string) $selected );
        echo '<button type="button" class="nb-d-seg-btn' . ( $is ? ' is-selected' : '' ) . '" role="radio" aria-checked="' . ( $is ? 'true' : 'false' ) . '" data-val="' . esc_attr( (string) $val ) . '">' . esc_html( $label ) . '</button>';
    }
    echo '</div>';
}

function nb_discovery_render_page( $instance ) {
	// Standalone page embeds a per-request nonce — never let a page cache (e.g. Hummingbird) store it.
	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( 'DONOTCACHEPAGE', true );
	}
	nocache_headers();

    $ver_css = newblood_asset_version( '/assets/css/discovery.css' );
    $ver_js  = newblood_asset_version( '/assets/js/discovery.js' );
    // Standalone page — wp_enqueue_* isn't available here, so cache-bust by filemtime() inline.
    $css_uri = get_template_directory_uri() . '/assets/css/discovery.css?v=' . $ver_css;
    $js_uri  = get_template_directory_uri() . '/assets/js/discovery.js?v=' . $ver_js;

    $cfg = wp_json_encode( array(
        'endpoint'  => esc_url_raw( rest_url( 'newblood/v1/discovery' ) ),
        'nonce'     => wp_create_nonce( 'wp_rest' ),
        'threshold' => 7,
        'instance'  => $instance['slug'],
    ) );

	if ( ! $cfg ) {
		$cfg = '{}';
	}

    $sc = $instance['section_copy'];
    ?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?php echo esc_html( 'Discovery — ' . $instance['client_name'] ); ?></title>
<link rel="stylesheet" href="<?php echo esc_url( $css_uri ); ?>">
</head>
<body class="nb-d-body">
<main class="nb-d-shell">
  <div class="nb-d-progress" aria-hidden="true"><span class="nb-d-progress-fill"></span></div>

  <header class="nb-d-welcome nb-d-section">
    <?php if ( $instance['logo'] ) : ?>
      <img class="nb-d-logo" src="<?php echo esc_url( $instance['logo'] ); ?>" alt="<?php echo esc_attr( $instance['client_name'] ); ?>">
    <?php endif; ?>
    <p class="nb-d-eyebrow">New Blood × <?php echo esc_html( $instance['client_name'] ); ?></p>
    <h1><?php echo esc_html( $instance['welcome']['title'] ); ?><span class="nb-d-dot">.</span></h1>
    <p class="nb-d-lede"><?php echo esc_html( $instance['welcome']['intro'] ); ?></p>
    <p class="nb-d-skip">Answer what you can — skip anything that doesn&#8217;t apply, and we&#8217;ll fill the gaps when we talk.</p>
  </header>

  <form id="nb-discovery-form" data-instance="<?php echo esc_attr( $instance['slug'] ); ?>" novalidate>

		<div class="nb-d-hp" aria-hidden="true" style="position:absolute!important;left:-9999px!important;top:auto;width:1px;height:1px;overflow:hidden;">
			<label>Company website (leave blank)
				<input type="text" name="hp_company" tabindex="-1" autocomplete="off">
			</label>
		</div>

    <!-- 1. Where you're headed (vision + direction vectors) -->
    <section class="nb-d-section">
      <p class="nb-d-step">Step 1 of 5</p>
      <h2><?php echo esc_html( $sc['goals'][0] ); ?><span class="nb-d-dot">.</span></h2>
      <p class="nb-d-sub"><?php echo esc_html( $sc['goals'][1] ); ?></p>
      <label class="nb-d-field">
        <span>In 3 years, what does winning look like?</span>
        <textarea name="vision" rows="4"></textarea>
      </label>
      <?php foreach ( $instance['goal_vectors'] as $v ) : ?>
      <div class="nb-d-vector-row">
        <span class="nb-d-vector-cap"><?php echo esc_html( $v['left'] ); ?></span>
        <?php nb_discovery_seg(
            array( 'nb-d-vector' ),
            array( '-50' => 'Strongly', '-25' => 'Lean', '0' => 'No pref', '25' => 'Lean', '50' => 'Strongly' ),
            $v['left'] . ' versus ' . $v['right'],
            '0',
            $v['key']
        ); ?>
        <span class="nb-d-vector-cap nb-d-vector-cap-r"><?php echo esc_html( $v['right'] ); ?></span>
      </div>
      <?php endforeach; ?>
    </section>

    <!-- 2. What matters most (clustered priorities) -->
    <section class="nb-d-section">
      <p class="nb-d-step">Step 2 of 5</p>
      <h2><?php echo esc_html( $sc['priorities'][0] ); ?><span class="nb-d-dot">.</span></h2>
      <p class="nb-d-sub"><?php echo esc_html( $sc['priorities'][1] ); ?></p>
      <?php
      $groups = nb_discovery_service_groups();
      foreach ( $groups as $gkey => $glabel ) : ?>
      <div class="nb-d-cluster">
        <h3 class="nb-d-cluster-label"><?php echo esc_html( $glabel ); ?></h3>
        <div class="nb-d-services">
          <?php foreach ( $instance['services'] as $s ) :
              if ( $s['group'] !== $gkey ) continue;
              $k = $s['key']; ?>
          <div class="nb-d-service" data-key="<?php echo esc_attr( $k ); ?>">
            <div class="nb-d-service-head">
              <span class="nb-d-service-label"><?php echo esc_html( $s['label'] ); ?></span>
              <span class="nb-d-service-hint"><?php echo esc_html( $s['hint'] ); ?></span>
            </div>
            <?php nb_discovery_seg(
                array( 'nb-d-importance' ),
                array( '0' => 'Not a priority', '3' => 'Nice to have', '7' => 'Important', '10' => 'Critical' ),
                $s['label'] . ' — importance',
                null,
                $k
            ); ?>
            <div class="nb-d-handling" hidden>
              <p class="nb-d-handling-q">How well is this handled today?</p>
              <?php nb_discovery_seg(
                  array( 'nb-d-handling-seg' ),
                  array( '2' => 'Poorly', '5' => 'OK', '7' => 'Well', '10' => 'Very well' ),
                  $s['label'] . ' — handled today'
              ); ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </section>

    <!-- 3. What's in place today (systems + leads baseline) -->
    <section class="nb-d-section" id="nb-d-systems">
      <p class="nb-d-step">Step 3 of 5</p>
      <h2><?php echo esc_html( $sc['systems'][0] ); ?><span class="nb-d-dot">.</span></h2>
      <p class="nb-d-sub"><?php echo esc_html( $sc['systems'][1] ); ?></p>
      <?php foreach ( $instance['systems_questions'] as $q ) : ?>
        <?php if ( $q['type'] === 'radio' ) : ?>
      <fieldset class="nb-d-field nb-d-radios">
        <legend><?php echo esc_html( $q['label'] ); ?></legend>
        <?php foreach ( $q['options'] as $oval => $olabel ) : ?>
        <label><input type="radio" name="<?php echo esc_attr( $q['key'] ); ?>" value="<?php echo esc_attr( $oval ); ?>"<?php echo ( $q['default'] === $oval ) ? ' checked' : ''; ?>> <?php echo esc_html( $olabel ); ?></label>
        <?php endforeach; ?>
      </fieldset>
        <?php elseif ( $q['type'] === 'textarea' ) : ?>
      <label class="nb-d-field"><span><?php echo esc_html( $q['label'] ); ?></span><textarea name="<?php echo esc_attr( $q['key'] ); ?>" rows="<?php echo (int) ( isset( $q['rows'] ) ? $q['rows'] : 3 ); ?>"></textarea></label>
        <?php else : ?>
      <label class="nb-d-field"><span><?php echo esc_html( $q['label'] ); ?></span><input type="text" name="<?php echo esc_attr( $q['key'] ); ?>"><?php if ( ! empty( $q['hint'] ) ) : ?><span class="nb-d-field-hint"><?php echo esc_html( $q['hint'] ); ?></span><?php endif; ?></label>
        <?php endif; ?>
      <?php endforeach; ?>
    </section>

    <!-- 4. Direction & timing -->
    <section class="nb-d-section">
      <p class="nb-d-step">Step 4 of 5</p>
      <h2><?php echo esc_html( $sc['direction'][0] ); ?><span class="nb-d-dot">.</span></h2>
      <p class="nb-d-sub"><?php echo esc_html( $sc['direction'][1] ); ?></p>
      <div class="nb-d-vector-row">
        <span class="nb-d-vector-cap">Fix what&#8217;s urgent</span>
        <?php nb_discovery_seg(
            array( 'nb-d-vector' ),
            array( '-50' => 'Strongly', '-25' => 'Lean', '0' => 'No pref', '25' => 'Lean', '50' => 'Strongly' ),
            'Fix what is urgent versus invest for long-term growth',
            '0',
            'fix_invest'
        ); ?>
        <span class="nb-d-vector-cap nb-d-vector-cap-r">Invest for long-term growth</span>
      </div>
      <label class="nb-d-field">
        <span>Ideal timeline to begin?</span>
        <select name="timeline">
          <option value="">Select…</option>
          <?php foreach ( $instance['timeline_options'] as $opt ) : ?>
          <option value="<?php echo esc_attr( $opt ); ?>"><?php echo esc_html( $opt ); ?></option>
          <?php endforeach; ?>
        </select>
      </label>
    </section>

    <!-- 5. Anything else -->
    <section class="nb-d-section">
      <p class="nb-d-step">Step 5 of 5</p>
      <h2><?php echo esc_html( $sc['open'][0] ); ?><span class="nb-d-dot">.</span></h2>
      <p class="nb-d-sub"><?php echo esc_html( $sc['open'][1] ); ?></p>
      <label class="nb-d-field"><span>Anything we haven&#8217;t asked?</span><textarea name="open" rows="4"></textarea></label>
      <label class="nb-d-field"><span>Your name</span><input type="text" name="respondent_name"></label>
      <label class="nb-d-field"><span>Your email</span><input type="email" name="respondent_email"></label>
    </section>

    <div class="nb-d-actions">
      <button type="submit" id="nb-d-submit">Send to New Blood</button>
      <p class="nb-d-error" id="nb-d-error" hidden></p>
    </div>
  </form>

  <div id="nb-d-thankyou" class="nb-d-section" hidden>
    <h2>Thank you<span class="nb-d-dot">.</span></h2>
    <p class="nb-d-lede">We&#8217;ll review your answers and prepare a plan built around your priorities. Jeremy will be in touch to walk through it with you.</p>
  </div>

</main>
<script>window.nbDiscovery = <?php echo $cfg; ?>;</script>
<script src="<?php echo esc_url( $js_uri ); ?>" defer></script>
</body>
</html><?php
}
